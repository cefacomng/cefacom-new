<?php

namespace App\Services\Access;

use RuntimeException;

/** A staff-management request that is permitted in principle but breaks a safety rule. */
class StaffRuleViolation extends RuntimeException {}
