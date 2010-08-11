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
 * Allows views to implement slices, small asynchronously refreshable portions of the page
 *
 * @author Tim Gunter
 * @package Garden
 * @version @@GARDEN-VERSION@@
 * @namespace Garden.Core
 */
 
class Gdn_Slice {

   protected $Dispatcher;

   public function __construct() {
      $this->Dispatcher = new Gdn_Dispatcher();
      $EnabledApplications = Gdn::Config('EnabledApplications');
      $this->Dispatcher->EnabledApplicationFolders($EnabledApplications);
      $this->Dispatcher->PassProperty('EnabledApplications', $EnabledApplications);
   }

   public function Execute() {
      $SliceArgs = func_get_args();
      switch (count($SliceArgs)) {
         case 1:
            //die('slice request: '.$SliceArgs[0]);
            $Request = Gdn::Request()->Create()
               ->FromEnvironment()
               ->WithURI($SliceArgs[0])
               ->WithDeliveryType(DELIVERY_TYPE_VIEW);
            
            ob_start();
            $this->Dispatcher->Dispatch($Request, FALSE);
            return ob_get_clean();

         break;
         case 2:
         
         break;
      }
   }

}

