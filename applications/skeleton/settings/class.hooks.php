<?php if (!defined('APPLICATION')) exit(); // Make sure this file can't get accessed directly
/**
 * A special function that is automatically run upon enabling your application.
 *
 * Remember to rename this to FooHooks, where 'Foo' is you app's short name.
 */
class SkeletonHooks implements Gdn_IPlugin {
   
   /**
    * Example hook. You should delete this.
    *
    * @param object $Sender The object that fired the event. All hooks must accept this single parameter.
    */
   public function ControllerName_EventName_Handler($Sender) {
      // You can find existing hooks by searching for 'FireEvent'
      // Request new hooks on the VanillaForums.org community forum!
   }
   
   /**
    * Special function automatically run upon clicking 'Enable' on your application.
    * Change the word 'skeleton' anywhere you see it.
    */
   public function Setup() {
      // You need to manually include structure.php here for it to get run at install.
      include(PATH_APPLICATIONS . DS . 'skeleton' . DS . 'settings' . DS . 'structure.php');

      // This just gets the version number and stores it in the config file. Good practice but optional.
      $ApplicationInfo = array();
      include(CombinePaths(array(PATH_APPLICATIONS . DS . 'skeleton' . DS . 'settings' . DS . 'about.php')));
      $Version = ArrayValue('Version', ArrayValue('Skeleton', $ApplicationInfo, array()), 'Undefined');
      SaveToConfig('Skeleton.Version', $Version);
   }
   
   /**
    * Special function automatically run upon clicking 'Disable' on your application.
    */
   public function OnDisable() {
      // Optional. Delete this if you don't need it.
   }
   
   /**
    * Special function automatically run upon clicking 'Remove' on your application.
    */
   public function CleanUp() {
      // Optional. Delete this if you don't need it.
   }
}