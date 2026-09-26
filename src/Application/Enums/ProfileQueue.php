<?php

namespace Src\Application\Enums;

enum ProfileQueue: string
{
    case Profiles = 'profiles';
    case Heavy = 'profiles-heavy';
}
