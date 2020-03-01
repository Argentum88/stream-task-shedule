<?php


class Scheduler
{
    protected $maxTaskId = 0;
    protected $taskMap = []; // taskId => task
    protected $taskQueue;
    protected $waitingForRead = [];
    protected $waitingForWrite = [];

    public function __construct() {
        $this->taskQueue = new SplQueue();
    }

    /**
     * Метод создает новую задачу (используя следующий свободный идентификатор задачи)
     * и помещает его в карте задач.
     * Кроме того, он планирует задачу, помещая ее в очередь задач.
     *
     * @param Generator $coroutine
     * @return int
     */
    public function newTask(Generator $coroutine) {
        $tid = ++$this->maxTaskId;
        $task = new Task($tid, $coroutine);
        $this->taskMap[$tid] = $task;
        $this->schedule($task);
        return $tid;
    }

    /**
     * Метод планирует задачу, помещая ее в очередь задач.
     *
     * @param Task $task
     */
    public function schedule(Task $task) {
        $this->taskQueue->enqueue($task);
    }

    /**
     * Метод обходит эту очередь задач и запускает задачи. Если задача завершена,
     * она удаляется, в противном случае она переносится в конец очереди.
     */
    public function run() {
        $this->newTask($this->ioPollTask());
        while (!$this->taskQueue->isEmpty()) {
            $task = $this->taskQueue->dequeue();
            $retval = $task->run();

            if ($retval instanceof SystemCall){
                $retval($task, $this);
                continue;
            }

            if ($task->isFinished()) {
                unset($this->taskMap[$task->getTaskId()]);
            } else {
                $this->schedule($task);
            }
        }
    }

    public function killTask($tid)
    {
        if (!isset($this->taskMap[$tid])){
            return false;
        }

        unset($this->taskMap[$tid]);

        foreach ($this->taskQueue as $i => $task) {
            if ($task->getTaskId() === $tid){
                unset($this->taskQueue[$i]);
                break;
            }
        }

        return true;
    }

    public function waitForRead($socket, Task $task) {
        if (isset($this->waitingForRead[(int) $socket])) {
            $this->waitingForRead[(int) $socket][1][] = $task;
        } else {
            $this->waitingForRead[(int) $socket] = [$socket, [$task]];
        }
    }

    public function waitForWrite($socket, Task $task) {
        if (isset($this->waitingForWrite[(int) $socket])) {
            $this->waitingForWrite[(int) $socket][1][] = $task;
        } else {
            $this->waitingForWrite[(int) $socket] = [$socket, [$task]];
        }
    }

    /**
     * Метод проверяет готовы ли сокеты и нужно ли перепланировать соответствующие
     * задачи.
     *
     * stream_select функция принимает массивы чтения, записи.
     * Массивы передаются по ссылке, и функция будет оставлять только те
     * элементы в массивах, которые изменили состояние.
     * Затем мы можем пройтись по этим массивам и перенести все задачи,
     * связанные с ними.
     *
     * @param $timeout
     */
    protected function ioPoll($timeout) {
        $rSocks = [];
        foreach ($this->waitingForRead as list($socket)) {
            $rSocks[] = $socket;
        }

        $wSocks = [];
        foreach ($this->waitingForWrite as list($socket)) {
            $wSocks[] = $socket;
        }

        $eSocks = []; // dummy

        // stream_select функция принимает массивы чтения, записи.
        // Массивы передаются по ссылке, и функция будет оставлять только те
        // элементы в массивах, которые изменили состояние.
        if (!stream_select($rSocks, $wSocks, $eSocks, $timeout)) {
            return;
        }

        // Затем мы можем пройтись по этим массивам и перенести все задачи,
        // связанные с ними.
        foreach ($rSocks as $socket) {
            list(, $tasks) = $this->waitingForRead[(int) $socket];
            unset($this->waitingForRead[(int) $socket]);

            foreach ($tasks as $task) {
                $this->schedule($task);
            }
        }

        foreach ($wSocks as $socket) {
            list(, $tasks) = $this->waitingForWrite[(int) $socket];
            unset($this->waitingForWrite[(int) $socket]);

            foreach ($tasks as $task) {
                $this->schedule($task);
            }
        }
    }

    protected function ioPollTask() {
        while (true) {
            if ($this->taskQueue->isEmpty()) {
                $this->ioPoll(null);
            } else {
                $this->ioPoll(0);
            }
            yield;
        }
    }
}