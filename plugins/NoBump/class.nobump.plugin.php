<?php if (!defined('APPLICATION')) exit();

$PluginInfo['NoBump'] = array(
   'Name' => 'No Bump',
   'Description' => "Allows moderators to add a comment without bumping a discussion.",
   'Version' => '1.0',
   'RequiredApplications' => array('Vanilla' => '2.1'),
   'MobileFriendly' => TRUE,
   'Author' => "Lincoln Russell",
   'AuthorEmail' => 'lincolnwebs@gmail.com',
   'AuthorUrl' => 'http://lincolnwebs.com'
);

class NoBumpPlugin extends Gdn_Plugin {
   /**
    * Add 'No Bump' option to new discussion form.
    */
   public function DiscussionController_AfterBodyField_Handler($Sender) {
      if (Gdn::Session()->CheckPermission('Garden.Moderation.Manage'))
         echo $Sender->Form->CheckBox('NoBump', T('No Bump'), array('value' => '1'));
   }

   /**
    * Set Comment's DateInserted to Discussion's DateLastComment so there's no change.
    */
   public function CommentModel_BeforeUpdateCommentCount_Handler($Sender) {
      if (Gdn::Session()->CheckPermission('Garden.Moderation.Manage')) {
         if (Gdn::Controller()->Form->GetFormValue('NoBump'))
            $Sender->EventArguments['Discussion']['Sink'] = 1;
      }
   }

   /** No setup. */
   public function Setup() { }
}