<?php

function logger($filename) {
    $fileHandle = fopen($filename, 'a');
    while (true){
        fwrite($fileHandle, yield . "\n");
    }
}

function gen(){
    yield 'foo';
    yield 'bar';

}

$logger = logger(__DIR__ . '/log');
$logger->send('Foo');
$logger->send('Bar');

$gen = gen();
//$gen->rewind();
var_dump($gen->send('something'));
