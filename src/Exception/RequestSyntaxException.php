<?php
namespace CMS\Exception;


use Exception;

class RequestSyntaxException extends \Exception
{
    public function __construct($message, $line = -1, Exception $previous = null)
    {
        parent::__construct($message, $line, $previous);
    }

} 