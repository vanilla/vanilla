<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 * @package Dashboard
 */

/**
 * Provides a way to widgetize modules.
 *
 * @package Dashboard
 */
class ModuleController extends Gdn_Controller {
   /**
    * Creates and renders an instance of a module.
    */
   public function Index($Module, $AppFolder = '', $DeliveryType = '') {
      if (!$DeliveryType)
         $this->DeliveryType(DELIVERY_TYPE_VIEW);
      
      $ModuleClassExists = class_exists($Module);

      if ($ModuleClassExists) {
         // Make sure that the class implements Gdn_IModule
         $ReflectionClass = new ReflectionClass($Module);
         if ($ReflectionClass->implementsInterface("Gdn_IModule")) {
            // Set the proper application folder on this controller so that things render properly.
            if ($AppFolder) {
               $this->ApplicationFolder = $AppFolder;
            } else {
               $Filename = str_replace('\\', '/', substr($ReflectionClass->getFileName(), strlen(PATH_ROOT)));
               // Figure our the application folder for the module.
               $Parts = explode('/', trim($Filename, '/'));
               if ($Parts[0] == 'applications') {
                  $this->ApplicationFolder = $Parts[1];
               }
            }


            $ModuleInstance = new $Module($this);
            $ModuleInstance->Visible = TRUE;
            
            $WhiteList = array('Limit', 'Help');
            foreach ($this->Request->Get() as $Key => $Value) {
               if (in_array($Key, $WhiteList)) {
                  $ModuleInstance->$Key = $Value;
               }
            }
            
            $this->SetData('_Module', $ModuleInstance);
            $this->Render('Index', FALSE, 'dashboard');
            return;
         }
      }
      throw NotFoundException($Module);
   }
}