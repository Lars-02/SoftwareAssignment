<?php

namespace App\Domain\Enums;

enum ImportResult: string
{
    case SUCCESS = 'SUCCESS';
    case INCOMPLETE = 'INCOMPLETE';
    case INVALID = 'INVALID';
    case FAILED = 'FAILED';
}
