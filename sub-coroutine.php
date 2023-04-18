<?php

require_once __DIR__ . '/vendor/autoload.php';

function echoTimes($msg, $max) {
    for ($i = 1; $i <= $max; ++$i) {
        echo "$msg iteration $i\n";
        yield;
    }

    yield new CoroutineReturnValue("iterated $max times");
}

function task() {
    $count = yield echoTimes('foo', 10); // print foo ten times
    echo $count.PHP_EOL;

    echo "---\n";

    $count = yield echoTimes('bar', 5); // print bar five times
    echo $count.PHP_EOL;
}

$scheduler = new Scheduler;
$scheduler->newTask(task());
$scheduler->run();
