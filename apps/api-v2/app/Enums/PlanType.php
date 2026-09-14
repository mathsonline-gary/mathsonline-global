<?php

namespace App\Enums;

/**
 * Who a plan is offered to.
 *
 * The integers are membership's plans.type and must not drift. Standard, Homeschool and Testing
 * are the three showPricing serves; Custom and Deprecated are declared so a stored value always
 * casts, never because this API offers them.
 */
enum PlanType: int
{
    case Standard = 1;
    case Homeschool = 2;
    case Custom = 3;
    case Deprecated = 4;
    case Testing = 5;
}
