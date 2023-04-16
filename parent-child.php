<?php

require_once __DIR__ . '/vendor/autoload.php';

function childTask() {
    $tid = (yield SystemCallFactory::getTaskId());
    while (true) {
        echo "Child task $tid still alive!\n";
        yield;
    }
}

function parentTask(){
    $tid = (yield SystemCallFactory::getTaskId());
    $childTid = (yield SystemCallFactory::newTask(childTask()));

    for ($i = 1; $i <= 6; ++$i) {
        echo "Parent task $tid iteration $i.\n";
        yield;

        if ($i == 3) yield SystemCallFactory::killTask($childTid);
    }
}

$scheduler = new Scheduler;

$scheduler->newTask(parentTask());
$scheduler->run();
