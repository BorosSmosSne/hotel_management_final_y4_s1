<?php

namespace App\Exceptions;

use RuntimeException;

/** A housekeeping change that is not allowed right now (wrong room state, guest in the room…). */
class HousekeepingException extends RuntimeException
{
}
