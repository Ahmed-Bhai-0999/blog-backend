<?php

namespace App\Enums;

enum TemplatePagesEnum:string
{
    case DEFAULT        = 'Default';
    case FULLWIDTH      = 'Full-Width';
    case CONTACT        = 'Contact';
    case LANDING        = 'Landing';
}