<?php
/*
|--------------------------------------------------------------------------
| String functions
|--------------------------------------------------------------------------
|
| Extended string functionalities
|
*/

/**
 * Secures the string for PHP and HTML
 *
 * @param string $string
 * @return string
 */
function string_secure($string)
{
    if (!is_string($string)) {
        throw new InvalidArgumentException;
    }

    $string = htmlspecialchars($string);
    $string = addslashes($string);

    return $string;
}

function secure_string($string)
{
    return string_secure($string);
}

/*
|--------------------------------------------------------------------------
| View helpers
|--------------------------------------------------------------------------
|
| Shortcuts for view types
|
*/

function view_error($code)
{
    if (!is_int($code)) {
        throw new \InvalidArgumentException;
    }

    return view('errors.' . $code);
}
