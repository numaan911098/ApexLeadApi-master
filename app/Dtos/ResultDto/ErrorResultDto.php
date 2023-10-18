<?php

namespace App\Dtos\ResultDto;

class ErrorResultDto extends ResultDto
{
    /**
     * ErrorResultDto Constructor.
     *
     * @param string $error Short error message.
     * @param string $errorCode Error code.
     * @param mixed $value Result value.
     */
    public function __construct(string $error = '', string $errorCode = '', $value = null)
    {
        parent::__construct(false, $error, $errorCode, $value);
    }
}
