<?php if (!defined('APPLICATION')) exit();

/**
 * Garden.Modules
 */

/**
 * Renders the "Start a New Discussion" button.
 */
class NewDiscussionModule extends Gdn_Module {

   public function AssetTarget() {
      return 'Panel';
   }
   
   public $CategoryID = NULL;
   public $DefaultButton;
   public $CssClass = 'Button Action Big Primary';
   public $QueryString = '';
   
   public function __construct($Sender = '', $ApplicationFolder = FALSE) {
      parent::__construct($Sender, 'Vanilla');
      // Customize main button by setting Vanilla.DefaultNewButton to URL code. Example: "post/question"
      $this->DefaultButton = C('Vanilla.DefaultNewButton', FALSE);
   }
   
   public function ToString() {
      if ($this->CategoryID === NULL)
         $this->CategoryID = Gdn::Controller()->Data('Category.CategoryID', FALSE);
      
      Gdn::Controller()->EventArguments['NewDiscussionModule'] = &$this;
      Gdn::Controller()->FireEvent('BeforeNewDiscussionButton');
      
      // Make sure the user has the most basic of permissions first.
      $PermissionCategory = CategoryModel::PermissionCategory($this->CategoryID);
      if ($this->CategoryID) {
         $Category = CategoryModel::Categories($this->CategoryID);
         $HasPermission = Gdn::Session()->CheckPermission('Vanilla.Discussions.Add', TRUE, 'Category', GetValue('CategoryID', $PermissionCategory));
      } else {
         $HasPermission = Gdn::Session()->CheckPermission('Vanilla.Discussions.Add', TRUE, 'Category', 'any');
      }
      if (!$HasPermission)
         return '';
      
      // Grab the allowed discussion types.
      $DiscussionTypes = CategoryModel::AllowedDiscussionTypes($PermissionCategory);
      
      foreach ($DiscussionTypes as $Key => $Type) {
         if (isset($Type['AddPermission']) && !Gdn::Session()->CheckPermission($Type['AddPermission'])) {
            unset($DiscussionTypes[$Key]);
            continue;
         }
         
         $Url = GetValue('AddUrl', $Type);
         if (!$Url)
            continue;
         
         if (isset($Category)) {
            $Url .= '/'.rawurlencode(GetValue('UrlCode', $Category));
         }
         
         $this->AddButton(GetValue('AddText', $Type), $Url);
      }
      
      if ($this->QueryString) {
         foreach ($this->Buttons as &$Row) {
            $Row['Url'] .= (strpos($Row['Url'], '?') !== FALSE ? '&' : '?').$this->QueryString;
         }
      }
      
      return parent::ToString();
   }
   
   public $Buttons = array();
   public function AddButton($Text, $Url) {
      $this->Buttons[] = array('Text' => $Text, 'Url' => $Url);
   }
}