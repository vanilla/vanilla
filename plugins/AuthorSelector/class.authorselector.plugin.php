<?php if (!defined('APPLICATION')) exit();

$PluginInfo['AuthorSelector'] = array(
   'Name' => 'Author Selector',
   'Description' => "Allows administrators to change the author of a discussion.",
   'Version' => '1.1',
   'RequiredApplications' => array('Vanilla' => '2.1'),
   'MobileFriendly' => TRUE,
   'Author' => "Matt Lincoln Russell",
   'AuthorEmail' => 'lincolnwebs@gmail.com',
   'AuthorUrl' => 'http://lincolnwebs.com'
);

class AuthorSelectorPlugin extends Gdn_Plugin {
   /** None. */
   public function Setup() { }
   
   /**
    * Allow admin to Change Author via discussion options.
    */
   public function Base_DiscussionOptions_Handler($Sender, $Args) {
      $Discussion = $Args['Discussion'];
      if (Gdn::Session()->CheckPermission('Vanilla.Discussions.Edit', TRUE, 'Category', $Discussion->PermissionCategoryID)) {
         $Label = T('Change Author');
         $Url = "/discussion/author?discussionid={$Discussion->DiscussionID}";
         // Deal with inconsistencies in how options are passed
         if (isset($Sender->Options)) {
            $Sender->Options .= Wrap(Anchor($Label, $Url, 'ChangeAuthor'), 'li');
         }
         else {
            $Args['DiscussionOptions']['ChangeAuthor'] = array(
               'Label' => $Label,
               'Url' => $Url,
               'Class' => 'ChangeAuthor'
            );
         }
      }
   }
   
   /**
    * Handle discussion option menu Change Author action.
    */
   public function DiscussionController_Author_Create($Sender, $Args) {
      $DiscussionID = $Sender->Request->Get('discussionid');
      $Discussion = $Sender->DiscussionModel->GetID($DiscussionID);
      if (!$Discussion)
         throw NotFoundException('Discussion');

      // Check edit permission
      $Sender->Permission('Vanilla.Discussions.Edit', TRUE, 'Category', $Discussion->PermissionCategoryID);

      if ($Sender->Form->AuthenticatedPostBack()) {
         // Change the author
         $Name = $Sender->Form->GetFormValue('Author', '');
         $UserModel = new UserModel();
         if (trim($Name) != '') {
            $User = $UserModel->GetByUsername(trim($Name));
            if (is_object($User)) {
               if ($Discussion->InsertUserID == $User->UserID)
                  $Sender->Form->AddError('That user is already the discussion author.');
               else {
                  // Change discussion InsertUserID
                  $Sender->DiscussionModel->SetField($DiscussionID, 'InsertUserID', $User->UserID);
                  // Update users' discussion counts
                  $Sender->DiscussionModel->UpdateUserDiscussionCount($Discussion->InsertUserID);
                  $Sender->DiscussionModel->UpdateUserDiscussionCount($User->UserID, TRUE); // Increment
                  // Go to the updated discussion
                  Redirect(DiscussionUrl($Discussion));
               }
            }
         }
         $Sender->Form->AddError('No user with that name was found.');
      }
      else {
         // Form to change the author
         $Sender->SetData('Title', $Discussion->Name);
      }

      $Sender->Render('changeauthor', '', 'plugins/AuthorSelector');
   }

   /**
    * Add Javascript files required for autocomplete / username token.
    *
    * @param $Sender
    */
   protected function AddJsFiles($Sender) {
      $Sender->AddJsFile('jquery.tokeninput.js');
      $Sender->AddJsFile('authorselector.js', 'plugins/AuthorSelector');
   }

   public function DiscussionsController_Render_Before($Sender) {
      $this->AddJsFiles($Sender);
   }

   public function DiscussionController_Render_Before($Sender) {
      $this->AddJsFiles($Sender);
   }

   public function CategoriesController_Render_Before($Sender) {
      $this->AddJsFiles($Sender);
   }
}