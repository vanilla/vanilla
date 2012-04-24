<?php if (!defined('APPLICATION')) exit();

/**
 * Data Module base class
 *
 * A Gdn_Module that gets data from the database.
 * 
 * @author Todd Burry <todd@vanillaforums.com> 
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.1
 */

class Gdn_DataModule extends Gdn_Module {
   /// Properties ///
   
   /**
    * @var array 
    */
   public $Data = array();
   
   /// Methods ///
   
   /** Get a value out of the controller's data array.
    *
    * @param string $Path The path to the data.
    * @param mixed $Default The default value if the data array doesn't contain the path.
    * @return mixed
    * @see GetValueR()
    * @since 2.1
    */
   public function Data($Path, $Default = '' ) {
      $Result = GetValueR($Path, $this->Data, $Default);
      return $Result;
   }
   
   /**
    * Set data from a method call.
    *
    * @param string $Key The key that identifies the data.
    * @param mixed $Value The data.
    * @param mixed $AddProperty Whether or not to also set the data as a property of this object.
    * @return mixed The $Value that was set.
    * @since 2.1 
    */
   public function SetData($Key, $Value = NULL, $AddProperty = FALSE) {
      if (is_array($Key)) {
         $this->Data = array_merge($this->Data, $Key);

         if ($AddProperty === TRUE) {
            foreach ($Key as $Name => $Value) {
               $this->$Name = $Value;
            }
         }
         return;
      }

      $this->Data[$Key] = $Value;
      if($AddProperty === TRUE) {
         $this->$Key = $Value;
      }
      return $Value;
   }
}