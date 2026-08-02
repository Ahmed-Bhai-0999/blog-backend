<?php

namespace App\Enums;

enum CommentNotificationTypeEnum:string
{
    case REPLY = 'reply';
    case MENTION = 'mention';
    case NEW_COMMENT = 'new_comment';
}