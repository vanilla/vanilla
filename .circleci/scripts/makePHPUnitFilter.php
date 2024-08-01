<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

// First argument is paths from circleci glob. Basically a space separated list of file paths.

$in = $argv[1];

if (!is_string($in)) {
    die('Only a string is allowed');
}

// Break out the individual paths.
$paths = explode(' ', $in);

$regexContents = '';
foreach ($paths as $i => $path) {
    // Use just the test name. Full file paths don't work in a filter expression.
    $name = pathinfo($path, PATHINFO_FILENAME);
    $regexContents .= preg_quote($name);

    if ($i !== count($paths) - 1) {
        $regexContents .= '|';
    }
}

$regex = "/^(.*\\\)?($regexContents)/";

// Output the regex.
echo $regex;
