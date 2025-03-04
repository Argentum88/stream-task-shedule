<?php


class Task
{
    /**
     * @var int
     */
    protected $taskId;

    /**
     * @var Generator
     */
    protected $coroutine;

    protected $sendValue = null;

    /**
     * @var bool
     */
    protected $beforeFirstYield = true;

    /**
     * Task constructor.
     * @param int $taskId
     * @param Generator $coroutine
     */
    public function __construct(int $taskId, Generator $coroutine)
    {
        $this->taskId = $taskId;
        $this->coroutine = $this->stackedCoroutine($coroutine);
    }

    /**
     * @return int
     */
    public function getTaskId(): int
    {
        return $this->taskId;
    }

    /**
     * @param $sendValue
     */
    public function setSendValue($sendValue): void
    {
        $this->sendValue = $sendValue;
    }

    /**
     * Добавляя дополнительное beforeFirstYield условие, мы можем гарантировать,
     * что значение первого дохода также будет возвращено.
     *
     * @return mixed
     */
    public function run()
    {
        if ($this->beforeFirstYield) {
            $this->beforeFirstYield = false;
            return $this->coroutine->current();
        } else {
            $retval = $this->coroutine->send($this->sendValue);
            $this->sendValue = null;
            return $retval;
        }
    }

    /**
     * @return bool
     */
    public function isFinished(): bool
    {
        return !$this->coroutine->valid();
    }

    private function stackedCoroutine(Generator $gen) {
        $stack = new SplStack;

        for (;;) {
            $value = $gen->current();

            if ($value instanceof Generator) {
                $stack->push($gen);
                $gen = $value;
                continue;
            }

            $isReturnValue = $value instanceof CoroutineReturnValue;
            if (!$gen->valid() || $isReturnValue) {
                if ($stack->isEmpty()) {
                    return;
                }

                $gen = $stack->pop();
                $gen->send($isReturnValue ? $value->getValue() : NULL);
                continue;
            }

            $gen->send(yield $gen->key() => $value);
        }
    }
}
