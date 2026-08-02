<?php

namespace App\Enums;

enum MenuLocationEnum:string
{
    case HEADER     = 'Header';
    case FOOTER     = 'Footer';
    case SIDEBAR    = 'Sidebar';
    case MOBILE     = 'Mobile';
}