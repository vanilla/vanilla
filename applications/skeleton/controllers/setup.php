<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Mark O'Sullivan
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Mark O'Sullivan at mark [at] lussumo [dot] com
*/

/// <summary>
/// Setup Controller
/// </summary>
class SetupController extends Controller {
   
   public $Uses = array('Form');
   
   /// <summary>
   /// The methods in setup controllers should not call "Render". Rendering will
   /// be handled by the controller that initiated the setup. This method should
   /// return a boolean value indicating success.
   /// </summary>
   public function Index() {
      $Database = Gdn::Database();
      $Config = Gdn::Factory(Gdn::AliasConfig);
      $Drop = Gdn::Config('Skeleton.Version') === FALSE ? TRUE : FALSE;
      $Explicit = TRUE;
      $Construct = $Database->Structure();
      $Validation = new Validation(); // This is going to be needed by structure.php to validate permission names
      try {
         include(PATH_APPLICATIONS . DS . 'skeleton' . DS . 'settings' . DS . 'structure.php');
      } catch (Exception $ex) {
         $this->Form->AddError(strip_tags($ex->getMessage()));
      }
      
      if ($this->Form->ErrorCount() == 0) {
         $ApplicationInfo = array();
         include(CombinePaths(array(PATH_APPLICATIONS . DS . 'skeleton' . DS . 'settings' . DS . 'about.php')));
         $Version = ArrayValue('Version', ArrayValue('Skeleton', $ApplicationInfo, array()), 'Undefined');
         $Config->Load(PATH_CONF . DS . 'config.php', 'Save');
         $Config->Set('Skeleton.Version', $Version);
         $Config->Save();
      }
      
      return $this->Form->ErrorCount() > 0 ? FALSE : TRUE;
   }  
}