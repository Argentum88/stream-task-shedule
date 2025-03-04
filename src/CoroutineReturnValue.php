<?php

declare(strict_types=1);

class CoroutineReturnValue
{
    protected $value;

    public function __construct($value) {
        $this->value = $value;
    }

    public function getValue() {
        return $this->value;
    }
}
