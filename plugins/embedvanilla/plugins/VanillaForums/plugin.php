<?php
/*
Plugin Name: Vanilla Forums
Plugin URI: http://vanillaforums.org/addons/
Description: Adds deep integration for Vanilla Forums to Wordpress, including: the ability to embed the entire forum into a WordPress page; Discussion, category, and activity widgets.
Version: 1.0.4
Author: Mark O'Sullivan
Author URI: http://www.vanillaforums.org/

ChangeLog:
1.0.4
- Fixed validation of Vanilla Url to correct when users incorrectly enter the path to their discussion instead of the actual root of the forum.
- Fixed a bug that caused Vanilla Admin JS & CSS to be included on all wp dashboard pages.
- Fixed a bug that caused the copy of the embed template to fail and throw a fatal PHP error.


Copyright 2010 Vanilla Forums Inc
This file is part of the Vanilla Forums plugin for WordPress.
The Vanilla Forums plugin is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
The Vanilla Forums plugin is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with the Vanilla Forums plugin.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc at support [at] vanillaforums [dot] com
*/

define('VF_OPTIONS_NAME', 'vf-options');
define('VF_PLUGIN_PATH', dirname(__FILE__));
define('VF_PLUGIN_URL', WP_PLUGIN_URL.'/'.plugin_basename(dirname(__FILE__)));

include_once(VF_PLUGIN_PATH.'/functions.php'); 
include_once(VF_PLUGIN_PATH.'/admin.php');
include_once(VF_PLUGIN_PATH.'/embed.php');
include_once(VF_PLUGIN_PATH.'/widgets.php');
// include_once(VF_PLUGIN_PATH.'/sso.php');
include_once(VF_PLUGIN_PATH.'/hooks.php');
