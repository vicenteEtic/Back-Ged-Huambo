<?php

namespace App\Enum;

enum TypeAssessment: int
{
    case NORMAL = 1;
    case IMPORT = 2;
    case AUTOMATIC_UPDATE_SCORE = 3;
    case AUTOMATIC = 4;
}
