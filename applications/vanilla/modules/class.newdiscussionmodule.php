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

   /** @var int Which category we are viewing (if any). */
   public $CategoryID = NULL;

   /** @var string Which button will be used as the default. */
   public $DefaultButton;

   /** @var string CSS classes to apply to ButtonGroup. */
   public $CssClass = 'Button Action Big Primary';

   /** @var string Query string to append to button URL. */
   public $QueryString = '';

   /** @var array Collection of buttons to display. */
   public $Buttons = array();

   /** @var bool Whether to show button to all users & guests regardless of permissions. */
   public $ShowGuests = false;

   /** @var string Where to send users without permission when $SkipPermissions is enabled. */
   public $GuestUrl = '/entry/register';

   /**
    * Set default button.
    *
    * @param string $Sender
    * @param bool $ApplicationFolder Unused.
    */
   public function __construct($Sender = '', $ApplicationFolder = FALSE) {
      parent::__construct($Sender, 'Vanilla');
      // Customize main button by setting Vanilla.DefaultNewButton to URL code. Example: "post/question"
      $this->DefaultButton = C('Vanilla.DefaultNewButton', FALSE);
   }

   /**
    * Add a button to the collection.
    *
    * @param $Text
    * @param $Url
    */
   public function AddButton($Text, $Url) {
      $this->Buttons[] = array('Text' => $Text, 'Url' => $Url);
   }

   /**
    * Render the module.
    *
    * @return string
    */
   public function ToString() {
      // Set CategoryID if we have one.
      if ($this->CategoryID === NULL) {
         $this->CategoryID = Gdn::Controller()->Data('Category.CategoryID', FALSE);
      }

      // Allow plugins and themes to modify parameters.
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

      // Determine if this is a guest & we're using "New Discussion" button as call to action.
      $PrivilegedGuest = ($this->ShowGuests && !Gdn::Session()->IsValid());

      // No module for you!
      if (!$HasPermission && !$PrivilegedGuest) {
         return '';
      }

      // Grab the allowed discussion types.
      $DiscussionTypes = CategoryModel::AllowedDiscussionTypes($PermissionCategory);

      foreach ($DiscussionTypes as $Key => $Type) {
         if (isset($Type['AddPermission']) && !Gdn::Session()->CheckPermission($Type['AddPermission'])) {
            unset($DiscussionTypes[$Key]);
            continue;
         }

         // If user !$HasPermission, they are $PrivilegedGuest so redirect to $GuestUrl.
         $Url = ($HasPermission) ? GetValue('AddUrl', $Type) : $this->GuestUrl;
         if (!$Url) {
            continue;
         }

         if (isset($Category) && $HasPermission) {
            $Url .= '/'.rawurlencode(GetValue('UrlCode', $Category));
         }

         $this->AddButton(T(GetValue('AddText', $Type)), $Url);
      }

      // Add QueryString to URL if one is defined.
      if ($this->QueryString && $HasPermission) {
         foreach ($this->Buttons as &$Row) {
            $Row['Url'] .= (strpos($Row['Url'], '?') !== FALSE ? '&' : '?').$this->QueryString;
         }
      }

      return parent::ToString();
   }
}