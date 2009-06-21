<?php
/// <summary>
/// An associative array of information about this application.
/// </summary>
$ApplicationInfo['Vanilla'] = array(
   'Description' => "Vanilla is the Lussumo's flagship application built with the Garden framework. Vanilla is a standards-compliant, open-source discussion forum for the web. Vanilla does a lot more with a lot less.",
   'Version' => '2.0',
   // 'RequiredApplications' => array('Garden' => '1.0'),
   'RegisterPermissions' => FALSE, // Permissions that should be added to the application when it is installed.
   'SetupController' => 'setup',
   'Url' => 'http://getvanilla.com',
   'Author' => "Mark O'Sullivan",
   'AuthorEmail' => 'mark@lussumo.com',
   'AuthorUrl' => 'http://lussumo.com',
   'License' => 'MIT License (http://lussumo.com/license)'
);