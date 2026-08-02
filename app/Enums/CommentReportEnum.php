<?php

namespace App\Enums;

enum CommentReportEnum:string
{
    case SPAM       = 'Spam';
    case HARASSMENT = 'Harassment';
    case ABUSE      = 'Abuse';
    case OTHER      = 'Other';
}