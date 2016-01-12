<?php

/**
 * This file is part of the CMS package.
 *
 */

/**
 * Configuration options for CMS.
 */
return [

    'path' => [
        /*
        |--------------------------------------------------------------------------
        | Include paths
        |--------------------------------------------------------------------------
        |
        | Paths for CMS content partials.
        |
        */

        'layouts'   => 'layout',
        'pages'     => 'pages',
        'partials'  => 'partials',
        'menus'     => 'menus',
    ],

    'parsers' => [
        /*
        |--------------------------------------------------------------------------
        | Optional parsers
        |--------------------------------------------------------------------------
        |
        | Source parsers used when loading the template.
        | NOTE: The parsers are ran in order of declaration.
        |
        */

        'CMS\Parser\SyntaxParser',
        'CMS\Parser\RequestParser',
    ],

    'requests' => [
        'strict_syntax' => true,

        //
        'requestable' => [
            'domain',
            'user',
        ],

        'scopes' => [
            'all',
            '* = ?',
        ]
    ]
];
