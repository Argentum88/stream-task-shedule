<?php

require_once __DIR__ . '/vendor/autoload.php';

function task($max) {
    $tid = (yield SystemCallFactory::getTaskId());
    for ($i = 1; $i <= $max; ++$i) {
        echo "This is task $tid iteration $i.\n";
        yield;
    }
}

$scheduler = new Scheduler;

$scheduler->newTask(task(10));
$scheduler->newTask(task(5));

$scheduler->run();
