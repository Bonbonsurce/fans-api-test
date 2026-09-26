<?php

namespace Src\Application\Enums;

enum ProfileRefreshStatus: string
{
    case Ok = 'ok';
    case Failed = 'failed';
    case Pending = 'pending';
}
