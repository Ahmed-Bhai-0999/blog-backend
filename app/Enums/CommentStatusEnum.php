<?php

namespace App\Enums;

enum CommentStatusEnum:string
{
    case PENDING    = 'Pending';
    case APPROVED   = 'Approved';
    case REJECTED   = 'Rejected';
}