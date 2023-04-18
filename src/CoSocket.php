<?php

declare(strict_types=1);

class CoSocket
{
    protected $socket;

    public function __construct($socket) {
        $this->socket = $socket;
    }

    public function accept() {
        yield SystemCallFactory::waitForRead($this->socket);
        yield new CoroutineReturnValue(new CoSocket(stream_socket_accept($this->socket, 0)));
    }

    public function read($size) {
        yield SystemCallFactory::waitForRead($this->socket);
        yield new CoroutineReturnValue(fread($this->socket, $size));
    }

    public function write($string) {
        yield SystemCallFactory::waitForWrite($this->socket);
        fwrite($this->socket, $string);
    }

    public function close() {
        @fclose($this->socket);
    }
}
