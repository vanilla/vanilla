<?php if (!defined('APPLICATION')) exit();

$PluginInfo['MessageLink'] = array(
   'Name' => 'Message Link',
   'Description' => "Adds a link to message the author of each discussion and comment.",
   'Version' => '1.2',
   'RequiredApplications' => array('Vanilla' => '2.2', 'Conversations' => '2.2'),
   'MobileFriendly' => TRUE,
   'Author' => "Lincoln Russell",
   'AuthorEmail' => 'lincoln@vanillaforums.com',
   'AuthorUrl' => 'http://lincolnwebs.com'
);

class MessageLinkPlugin extends Gdn_Plugin {
   /**
    * Add 'Send Message' option to Discussion.
    */
   public function Base_AfterFlag_Handler($Sender, $Args) {
      if (CheckPermission('Conversations.Conversations.Add'))
         $this->AddSendMessageButton($Sender, $Args);
   }
   
   /**
    * Output Send Message link.
    */
   protected function AddSendMessageButton($Sender, $Args) {
      if (!Gdn::Session()->UserID) return;
      if (isset($Args['Comment'])) {
         $Object = $Args['Comment'];
         $ObjectID = 'Comment_'.$Args['Comment']->CommentID;
      } else if (isset($Args['Discussion'])) {
         $Object = $Args['Discussion'];
         $ObjectID = 'Discussion_'.$Args['Discussion']->DiscussionID;
      } else return;
      
      echo Anchor(Sprite('ReactMessage', 'ReactSprite').T('Send Message'), Url("/messages/add/{$Object->InsertName}",TRUE), 'ReactButton Visible SendMessage').' ';
   }
}