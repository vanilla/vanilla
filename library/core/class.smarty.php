<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

class Gdn_Smarty {
   /// Constructor ///

   /// Properties ///

   /**
    * @var Smarty The smarty object used for the template.
    */
   protected $_Smarty = NULL;

   /// Methods ///

   /**
    * Render the given view.
    *
    * @param string $Path The path to the view's file.
    * @param Controller $Controller The controller that is rendering the view.
    */
   public function Render($Path, $Controller) {
      $Smarty = $this->Smarty();

      // Get a friendly name for the controller.
      $ControllerName = get_class($Controller);
      if (StringEndsWith($ControllerName, 'Controller', TRUE)) {
         $ControllerName = substr($ControllerName, 0, -10);
      }

      // Get an ID for the body.
      $BodyIdentifier = strtolower($Controller->ApplicationFolder.'_'.$ControllerName.'_'.Gdn_Format::AlphaNumeric(strtolower($Controller->RequestMethod)));
      $Smarty->assign('BodyID', $BodyIdentifier);
      //$Smarty->assign('Config', Gdn::Config());

      // Assign some information about the user.
      $Session = Gdn::Session();
      if($Session->IsValid()) {
         $User = array(
            'Name' => $Session->User->Name,
            'CountNotifications' => (int)GetValue('CountNotifications', $Session->User->CountNotifications, 0),
            'CountUnreadConversations' => (int)GetValue('CountUnreadConversations', $Session->User, 0),
            'SignedIn' => TRUE);
      } else {
         $User = FALSE; /*array(
            'Name' => '',
            'CountNotifications' => 0,
            'SignedIn' => FALSE);*/
      }
      $Smarty->assign('User', $User);

      // Make sure that any datasets use arrays instead of objects.
      foreach($Controller->Data as $Key => $Value) {
         if($Value instanceof Gdn_DataSet) {
            $Controller->Data[$Key] = $Value->ResultArray();
         } elseif($Value instanceof stdClass) {
            $Controller->Data[$Key] = (array)$Value;
         }
      }
     
      $Controller->Data['BodyClass'] = GetValue('CssClass', $Controller->Data, '', TRUE);

      $Smarty->assign('Assets', (array)$Controller->Assets);
      $Smarty->assign('Path', Gdn::Request()->Path());

      // Assigign the controller data last so the controllers override any default data.
      $Smarty->assign($Controller->Data);

      $Smarty->Controller = $Controller; // for smarty plugins
      $Smarty->security = TRUE;
      $Smarty->security_settings['IF_FUNCS'] = array_merge($Smarty->security_settings['IF_FUNCS'],
         array('CheckPermission', 'GetValue', 'SetValue', 'Url'));
      $Smarty->secure_dir = array($Path);
      $Smarty->display($Path);
   }

   /**
    * @return Smarty The smarty object used for rendering.
    */
   public function Smarty() {
      if(is_null($this->_Smarty)) {
         $Smarty = Gdn::Factory('Smarty');

         $Smarty->cache_dir = PATH_CACHE . DS . 'Smarty' . DS . 'cache';
         $Smarty->compile_dir = PATH_CACHE . DS . 'Smarty' . DS . 'compile';
         $Smarty->plugins_dir[] = PATH_LIBRARY . DS . 'vendors' . DS . 'SmartyPlugins';

         $this->_Smarty = $Smarty;
      }
      return $this->_Smarty;
   }
}