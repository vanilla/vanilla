<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

/*
 * Bluehost has very specific email requirements.
 * It blocks most outgoing ports so an external smtp server won't work.
 * It requires a specific email header or else php's mail() function won't work.
 * Check the following discussion for the solution implemented here: http://forum.joomla.org/viewtopic.php?p=246445
 *
 * This plugin modifies the core PhpMailer file and will get used instead of it when the plugin is enabled.
 * 
 */

// Define the plugin:
$PluginInfo['BluehostFix'] = array(
   'Name' => 'Bluehost Fix',
   'Description' => "Provides a workaround for Bluehost's email restrictions so that Vanilla can send emails properly when installed on bluehost.",
   'Version' => '1.0',
   'RequiredApplications' => array('Vanilla' => '2.0.14'),
   'Author' => 'Todd Burry',
   'AuthorEmail' => 'todd@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.org/profile/todd'
);

class BluehostFixPlugin extends Gdn_Plugin {
   public function Setup() {
      // Remove the current library map so that the core file won't be grabbed.
      @unlink(PATH_CACHE.'/library_map.ini');
   }
}