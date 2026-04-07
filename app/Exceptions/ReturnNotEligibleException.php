<?php

namespace App\Exceptions;

use Exception;

class ReturnNotEligibleException extends Exception
{
    protected $message = 'Order is not eligible for return.';
}
