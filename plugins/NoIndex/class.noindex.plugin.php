<?php if (!defined('APPLICATION')) exit();

$PluginInfo['NoIndex'] = array(
   'Name' => 'No Index',
   'Description' => "Allows moderators & curators to mark a discussion as noindex/noarchive.",
   'Version' => '1.0',
   'RequiredApplications' => array('Vanilla' => '2.1'),
   'MobileFriendly' => TRUE,
   'Author' => "Lincoln Russell",
   'AuthorEmail' => 'lincolnwebs@gmail.com',
   'AuthorUrl' => 'http://lincolnwebs.com'
);

/**
 * Class NoIndexPlugin.
 *
 * Adds an optional 'NoIndex' property to discussions which removes the discussion from search indexes.
 * We do this as a DB column so we can easily add search & count functionality later if we want.
 * Removing these discussions from internal search would also be an interesting extension.
 */
class NoIndexPlugin extends Gdn_Plugin {
   /**
    * Allow mods to add/remove NoIndex via discussion options.
    */
   public function Base_DiscussionOptions_Handler($Sender, $Args) {
      if (CheckPermission(array('Garden.Moderation.Manage', 'Garden.Curation.Manage'), FALSE)) {
         $Discussion = $Args['Discussion'];
         $Label = (GetValue('NoIndex', $Discussion)) ? T('Remove NoIndex') : T('Add NoIndex');
         $Url = "/discussion/noindex?discussionid={$Discussion->DiscussionID}";
         // Deal with inconsistencies in how options are passed
         if (isset($Sender->Options)) {
            $Sender->Options .= Wrap(Anchor($Label, $Url, 'NoIndex'), 'li');
         }
         else {
            $Args['DiscussionOptions']['Bump'] = array(
               'Label' => $Label,
               'Url' => $Url,
               'Class' => 'NoIndex'
            );
         }
      }
   }

   /**
    * Handle discussion option menu NoIndex action (simple toggle).
    */
   public function DiscussionController_NoIndex_Create($Sender, $Args) {
      $Sender->Permission(array('Garden.Moderation.Manage', 'Garden.Curation.Manage'), FALSE);

      // Get discussion
      $DiscussionID = $Sender->Request->Get('discussionid');
      $Discussion = $Sender->DiscussionModel->GetID($DiscussionID);
      if (!$Discussion) {
         throw NotFoundException('Discussion');
      }

      // Toggle NoIndex
      $NoIndex = GetValue('NoIndex', $Discussion) ? 0 : 1;

      // Update DateLastComment & redirect
      $Sender->DiscussionModel->SetProperty($DiscussionID, 'NoIndex', $NoIndex);
      Redirect(DiscussionUrl($Discussion));
   }

    /**
     * Add a mod message to NoIndex discussions.
     */
    public function DiscussionController_BeforeDiscussionDisplay_Handler($Sender, $Args) {
        if (!CheckPermission(array('Garden.Moderation.Manage', 'Garden.Curation.Manage'), FALSE))
            return;

        if (GetValue('NoIndex', $Sender->Data('Discussion'))) {
            echo Wrap(T('Discussion marked as noindex'), 'div', array('class' => 'Warning'));
        }
    }

    /**
     * Add the noindex/noarchive meta tag.
     */
    public function DiscussionController_Render_Before($Sender, $Args) {
        if ($Sender->Head && GetValue('NoIndex', $Sender->Data('Discussion'))) {
            $Sender->Head->AddTag('meta', array('name' => 'robots', 'content' => 'noindex,noarchive'));
        }
    }

    /**
     * Show NoIndex meta tag on discussions list.
     */
    public function Base_BeforeDiscussionMeta_Handler($Sender, $Args) {
        $NoIndex = GetValue('NoIndex', GetValue('Discussion', $Args));
        if (CheckPermission(array('Garden.Moderation.Manage', 'Garden.Curation.Manage'), FALSE) && $NoIndex) {
            echo ' <span class="Tag Tag-NoIndex">'.T('NoIndex').'</span> ';
        }
    }

   /**
    * Invoke structure changes.
    */
   public function Setup() {
        $this->Structure();
   }

    /**
     * Add NoIndex property to discussions.
     */
    public function Structure() {
       Gdn::Structure()
          ->Table('Discussion')
          ->Column('NoIndex', 'int', '0')
          ->Set();
   }
}