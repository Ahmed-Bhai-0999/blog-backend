<?php

namespace App\Enums;

enum ActivityLogEnum:string
{
    case SUCCESS     = 'Success';
    case INFO        = 'Info';
    case WARNING     = 'Warning';
    case ERROR       = 'Error';
}