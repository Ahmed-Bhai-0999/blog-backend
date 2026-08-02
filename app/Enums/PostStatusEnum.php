<?php

namespace App\Enums;

enum PostStatusEnum:string
{
    case DRAFT      = 'Draft';
    case PUBLISHED  = 'Published';
    case SCHEDULED  = 'Scheduled';
    case ARCHIVED   = 'Archived';
}