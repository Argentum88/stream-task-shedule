<?php


class SystemCall
{
    /**
     * @var callable
     */
    protected $callback;

    /**
     * SystemCall constructor.
     * @param $callback
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    public function __invoke(Task $task, Scheduler $scheduler)
    {
        $callback = $this->callback;
        return $callback($task, $scheduler);
    }


}