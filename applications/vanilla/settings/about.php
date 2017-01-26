<?php
/**
 * An associative array of information about this application.
 *
 * @package Vanilla
 */
$ApplicationInfo['Vanilla'] = array(
    'AllowDisable' => false, // Vanilla should never get disabled once it has been installed.
    'Description' => "Vanilla is the sweetest discussion forum on the web.",
    'Version' => APPLICATION_VERSION,
    'SetupController' => 'setup',
    'Url' => 'https://open.vanillaforums.com',
    'Author' => "Vanilla Staff",
    'AuthorEmail' => 'support@vanillaforums.com',
    'AuthorUrl' => 'https://open.vanillaforums.com',
    'License' => 'GPL v2',
    'Hidden' => true,
    'Icon' => 'vanilla.png'
);
