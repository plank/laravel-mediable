<?php

namespace Plank\Mediable\Exceptions;

use Exception;

/**
 * @author Sean Fraser <sean@plankdesign.com>
 */
class BadCallableReturnException extends Exception
{
    public static function shouldBe($callback, $expected, $given)
    {
        return new static("Return of {$callback} should be {$expected}, {$given} given");
    }
}
