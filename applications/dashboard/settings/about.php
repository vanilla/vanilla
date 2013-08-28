<?php
/**
 * Dashboard Application
 * 
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.0
 */

$ApplicationInfo['Dashboard'] = array(
   'Description' => "Garden is a php-based web platform, and \"Dashboard\" is the core Garden application that handles user, role, permission, plugin, theme, and application management.",
   'Version' => APPLICATION_VERSION,
   'RegisterPermissions' => FALSE,
   'AllowDisable' => FALSE, // Dashboard should never get disabled once it has been installed.
   'Url' => 'http://vanillaforums.org',
   'Author' => "Mark O'Sullivan",
   'AuthorEmail' => 'mark@vanillaforums.com',
   'AuthorUrl' => 'http://markosullivan.ca',
   'License' => 'GPL v2'
);