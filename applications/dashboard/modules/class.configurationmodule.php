<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 *
 * This class gives a simple way to load/save configuration settings.
 * To use this module you must:
 *  1. Call Schema() to set the config fields you are using.
 *  2. Call Initialize() within the controller to load/save the data.
 *  3. Do one of the following:
 *   a) Call the controller's Render() method and call Render() somewhere inside of the view.
 *   b) Call this object's RenderAll() method within the view if you don't want to customize the view any further.
 */

class ConfigurationModule extends Gdn_Module {
   /// PROPERTIES ///

   /**
    * Whether or not the view is rendering the entire page.
    * @var bool
    */
   public $RenderAll = FALSE;

   /**
    *
    * @var array A definition of the data that this will manage.
    */
   protected $_Schema;

   /**
    * @var ConfigurationModule
    */
   public $ConfigurationModule = NULL;

   /// METHODS ///

   /**
    * @param Gdn_Controller $Controller The controller using this model.
    */
   public function __construct($Sender = NULL) {
      parent::__construct($Sender);

      if (property_exists($Sender, 'Form'))
         $this->Form($Sender->Form);

      $this->ConfigurationModule = $this;
   }

   /**
    * @return Gdn_Controller
    */
   public function Controller() {
      return $this->_Sender;
   }

   /**
    * 
    * @param Gdn_Form $NewValue
    * @return Gdn_Form
    */
   public function Form($NewValue = NULL) {
      static $Form = NULL;

      if ($NewValue !== NULL)
         $Form = $NewValue;
      elseif ($Form === NULL)
         $Form = new Gdn_Form();

      return $Form;
   }
   
   public function HasFiles() {
      static $HasFiles = NULL;
      
      if ($HasFiles === NULL) {
         $HasFiles = FALSE;
         foreach ($this->Schema() as $K => $Row) {
            if (strtolower(GetValue('Control', $Row)) == 'imageupload') {
               $HasFiles = TRUE;
               break;
            }
         }
      }
      return $HasFiles;
   }

   public function Initialize($Schema = NULL) {
      if ($Schema !== NULL)
         $this->Schema($Schema);
      
      $Form = $this->Form();

      if ($Form->AuthenticatedPostBack()) {
         // Grab the data from the form.
         $Data = array();

         foreach ($this->_Schema as $Row) {
            $Name = $Row['Name'];
            
            if (strtolower(GetValue('Control', $Row)) == 'imageupload') {
               $Form->SaveImage($Name, ArrayTranslate($Row, array('Prefix', 'Size')));
            }
            
            $Value = $Form->GetFormValue($Name);

            if ($Value == GetValue('Default', $Value, ''))
               $Value = '';

            $Data[$Name] = $Value;
         }

         // Save it to the config.
         SaveToConfig($Data, array('RemoveEmpty' => TRUE));
         $this->_Sender->InformMessage(T('Saved'));
      } else {
         // Load the form data from the config.
         $Data = array();
         foreach ($this->_Schema as $Row) {
            $Data[$Row['Name']] = C($Row['Name'], GetValue('Default', $Row, ''));
         }
         $Form->SetData($Data);
      }
   }

   public function LabelCode($SchemaRow) {
      if (isset($SchemaRow['LabelCode']))
         return $SchemaRow['LabelCode'];

      $LabelCode = trim(strrchr($SchemaRow['Name'], '.'), '.');

      // Split camel case labels into seperate words.
      $LabelCode = preg_replace('`(?<![A-Z0-9])([A-Z0-9])`', ' $1', $LabelCode);
      $LabelCode = preg_replace('`([A-Z0-9])(?=[a-z])`', ' $1', $LabelCode);
      $LabelCode = trim($LabelCode);
      
      $LabelCode = StringEndsWith($LabelCode, " ID", TRUE, TRUE);

      return $LabelCode;
   }

   public function RenderAll() {
      $this->RenderAll = TRUE;
      $Controller = $this->Controller();
      $Controller->ConfigurationModule = $this;

      $Controller->Render($this->FetchViewLocation());
      $this->RenderAll = FALSE;
   }

   /**
    * Set the data definition to load/save from the config.
    * @param array $Def A list of fields from the config that this form will use.
    */
   public function Schema($Def = NULL) {
      if ($Def !== NULL) {
         $Schema = array();

         foreach ($Def as $Key => $Value) {
            $Row = array('Name' => '', 'Type' => 'string', 'Control' => 'TextBox', 'Options' => array());

            if (is_numeric($Key)) {
               $Row['Name'] = $Value;
            } elseif (is_string($Value)) {
               $Row['Name'] = $Key;
               $Row['Type'] = $Value;
            } elseif (is_array($Value)) {
               $Row['Name'] = $Key;
               $Row = array_merge($Row, $Value);
            } else {
               $Row['Name'] = $Key;
            }
            $Schema[] = $Row;
         }
         $this->_Schema = $Schema;
      }
      return $this->_Schema;
   }
}