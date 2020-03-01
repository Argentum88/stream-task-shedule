<?php

require_once __DIR__ . '/vendor/autoload.php';

/**
 * @param $port
 * @return Generator
 * @throws Exception
 */
function server($port) {
    echo "Starting server at port $port...\n";

    $socket = stream_socket_server("tcp://localhost:$port", $errNo, $errStr);
    if (!$socket) throw new Exception($errStr, $errNo);

    stream_set_blocking($socket, false);

    while (true) {
        yield SystemCallFactory::waitForRead($socket);
        $clientSocket = stream_socket_accept($socket, 0);
        yield SystemCallFactory::newTask(handleClient($clientSocket));
    }
}

/**
 * @param $socket
 * @return Generator
 */
function handleClient($socket) {
    yield SystemCallFactory::waitForRead($socket);
    $data = fread($socket, 8192);

    $msg = "Received following request:\n\n$data";
    $msgLength = strlen($msg);

    $response = <<<RES
HTTP/1.1 200 OK\r\n
Content-Type: text/plain\r\n
Content-Length: $msgLength\r\n
Connection: close\r\n
\r\n
$msg
RES;

    yield SystemCallFactory::waitForWrite($socket);
    fwrite($socket, $response);

    fclose($socket);
}

$scheduler = new Scheduler;
try {
    $scheduler->newTask(server(8000));
} catch (Exception $e) {
    echo $e->getMessage();
}
$scheduler->run();