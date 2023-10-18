<?php

namespace App\Dtos\ResultDto;

class SuccessResultDto extends ResultDto
{
    /**
     * SuccessResultDto Constructor.
     *
     * @param mixed $value Result value.
     */
    public function __construct($value = null)
    {
        parent::__construct(true, '', '', $value);
    }
}
