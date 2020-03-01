<?php


class Scheduler
{
    protected $maxTaskId = 0;
    protected $taskMap = []; // taskId => task
    protected $taskQueue;

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
}