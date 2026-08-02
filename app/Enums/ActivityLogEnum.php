<?php

namespace App\Enums;

enum ActivityLogEnum:string
{
    case CREATE         = 'Create';
    case UPDATE         = 'Update';
    case DELETE         = 'Delete';
    case RESTORE        = 'Restore';
    case FORCE_DELETE   = 'Force Delete';
    case LOGIN          = 'Login';
    case LOGOUT         = 'Logout';
    case STATUS_CHANGE  = 'Status Change';
}