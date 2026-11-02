<?php


declare (strict_types = 1);

 // PHP < 8.3 undefined
if (! class_exists(DateException::class)) {
    class DateException extends Exception
    {}
}
if (! class_exists(DateMalformedStringException::class)) {
    class DateMalformedStringException extends DateException
    {}
}
