<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

/**
 * Utility class that helps to render theme elements.
 *
 * @author Mark O'Sullivan
 * @copyright 2009 Mark O'Sullivan
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @version @@GARDEN-VERSION@@
 * @namespace Garden.Core
 */
class Gdn_Theme {

   /**
    * Renders the banner logo, or just the banner title if the logo is not defined.
    */
   public static function Logo() {
      $Logo = C('Garden.Logo');
      $Title = C('Garden.Title', 'Title');
      echo $Logo ? Img($Logo, array('alt' => $Title)) : $Title;
   }
   
   public static function Pagename() {
      $Application = Gdn::Dispatcher()->Application();
      $Controller = Gdn::Dispatcher()->Controller();
      switch ($Controller) {
         case 'discussions':
         case 'discussion':
         case 'post':
            return 'discussions';
            
         case 'inbox':
            return 'inbox';
            
         case 'activity':
            return 'activity';
            
         case 'profile':
            $Args = Gdn::Dispatcher()->ControllerArguments();
            if (!sizeof($Args) || ( sizeof($Args) && $Args[0] == Gdn::Authenticator()->GetIdentity()))
               return 'profile';
            break;
      }
      
      return 'unknown';
   }
   
}