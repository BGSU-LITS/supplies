<?php
/**
 * Application Settings
 * @author John Kloor <kloor@bgsu.edu>
 * @copyright 2016 Bowling Green State University Libraries
 * @license MIT
 * @package Supplies
 */

use M1\Env\Parser;

// Setup the default settings for the application.
$settings = [
    // Allows for debugging during application development.
    'debug' => false,

    // Full path to log file, if any.
    'log' => false,

    // Template configuration.
    'template' => [
        // Path to search for template files before package's templates.
        'path' => false,

        // Template file that defines a page. Defaults to: page.html.twig
        'page' => false
    ],

    // SMTP server settings.
    'smtp' => [
        // Host.
        'host' => false,

        // Port. Defaults to: 25
        'port' => 25
    ],

    // Locations, each child is an array with title, email and instructions.
    'mail' => [
        // Address email should be sent to.
        'to' => false,

        // Address email should be carbon copied to.
        'cc' => false
    ]
];

// Check if a .env file exists.
$file = dirname(__DIR__) . '/.env';

if (file_exists($file)) {
    // If so, load the settings from that file.
    $text = file_get_contents($file);

    // Parse settings into key/value pairs.
    foreach (Parser::parse($text) as $key => $value) {
        // If a value was specified, add it to the settings array.
        if (!empty($value)) {
            $target = &$settings;

            foreach (explode('_', strtolower($key)) as $part) {
                $target = &$target[$part];
            }

            $target = $value;
        }
    }
}

// Return the complete settings array.
return $settings;
