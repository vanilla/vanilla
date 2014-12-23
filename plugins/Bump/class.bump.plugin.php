<?php if (!defined('APPLICATION')) exit();

$PluginInfo['Bump'] = array(
   'Name' => 'Bump',
   'Description' => "Allows moderators to bump a discussion without commenting.",
   'Version' => '1.0',
   'RequiredApplications' => array('Vanilla' => '2.1'),
   'MobileFriendly' => TRUE,
   'Author' => "Lincoln Russell",
   'AuthorEmail' => 'lincolnwebs@gmail.com',
   'AuthorUrl' => 'http://lincolnwebs.com'
);

class BumpPlugin extends Gdn_Plugin {
   /**
    * Allow mods to bump via discussion options.
    */
   public function Base_DiscussionOptions_Handler($Sender, $Args) {
      $Discussion = $Args['Discussion'];
      if (CheckPermission('Garden.Moderation.Manage')) {
         $Label = T('Bump');
         $Url = "/discussion/bump?discussionid={$Discussion->DiscussionID}";
         // Deal with inconsistencies in how options are passed
         if (isset($Sender->Options)) {
            $Sender->Options .= Wrap(Anchor($Label, $Url, 'Bump'), 'li');
         }
         else {
            $Args['DiscussionOptions']['Bump'] = array(
               'Label' => $Label,
               'Url' => $Url,
               'Class' => 'Bump'
            );
         }
      }
   }

   /**
    * Handle discussion option menu bump action.
    */
   public function DiscussionController_Bump_Create($Sender, $Args) {
      $Sender->Permission('Garden.Moderation.Manage');

      // Get discussion
      $DiscussionID = $Sender->Request->Get('discussionid');
      $Discussion = $Sender->DiscussionModel->GetID($DiscussionID);
      if (!$Discussion)
         throw NotFoundException('Discussion');

      // Update DateLastComment & redirect
      $Sender->DiscussionModel->SetProperty($DiscussionID, 'DateLastComment', Gdn_Format::ToDateTime());
      Redirect(DiscussionUrl($Discussion));
   }

   /** No setup. */
   public function Setup() { }
}