<?php

namespace App\Dtos\ResultDto;

abstract class ResultDto
{
    /**
     * Set true for successful result.
     *
     * @var boolean
     */
    public $success;

    /**
     * Short error message.
     *
     * @var string
     */
    public $error;

    /**
     * Error code.
     *
     * @var string
     */
    public $errorCode;

    /**
     * Result value
     *
     * @var mixed
     */
    public $value;

    /**
     * Constructor
     *
     * @param boolean $success
     * @param string $error
     * @param string $errorCode
     * @param mixed $value
     */
    public function __construct(bool $success, string $error, string $errorCode, $value)
    {
        $this->success = $success;
        $this->error = $error;
        $this->errorCode = $errorCode;
        $this->value = $value;
    }
}
