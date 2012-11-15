<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

// Define the plugin:
$PluginInfo['SplitMerge'] = array(
   'Name' => 'Split / Merge',
   'Description' => 'Allows moderators with discussion edit permission to split & merge discussions.',
   'Version' => '1.1',
   'HasLocale' => TRUE,
   'Author' => "Mark O'Sullivan",
   'AuthorEmail' => 'mark@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.com'
);

class SplitMergePlugin extends Gdn_Plugin {

   /**
    * Add "split" action link.
    */
   public function Base_BeforeCheckComments_Handler($Sender) {
      $ActionMessage = &$Sender->EventArguments['ActionMessage'];
      $Discussion = $Sender->EventArguments['Discussion'];
      if (Gdn::Session()->CheckPermission('Vanilla.Discussions.Edit', TRUE, 'Category', $Discussion->PermissionCategoryID))
         $ActionMessage .= ' '.Anchor(T('Split'), 'vanilla/moderation/splitcomments/'.$Discussion->DiscussionID.'/', 'Split Popup');
   }
   
   /**
    * Add "merge" action link.
    */
   public function Base_BeforeCheckDiscussions_Handler($Sender) {
      $ActionMessage = &$Sender->EventArguments['ActionMessage'];
      if (Gdn::Session()->CheckPermission('Vanilla.Discussions.Edit', TRUE, 'Category', 'any'))
         $ActionMessage .= ' '.Anchor(T('Merge'), 'vanilla/moderation/mergediscussions/', 'Merge Popup');
   }

   /**
    * Add a method to the ModerationController to handle splitting comments out to a new discussion.
    */
   public function ModerationController_SplitComments_Create($Sender) {
      $Session = Gdn::Session();
      $Sender->Form = new Gdn_Form();
      $Sender->Title(T('Split Comments'));
      $Sender->Category = FALSE;

      $DiscussionID = GetValue('0', $Sender->RequestArgs, '');
      if (!is_numeric($DiscussionID))
         return;
      
      $DiscussionModel = new DiscussionModel();
      $Discussion = $DiscussionModel->GetID($DiscussionID);
      if (!$Discussion)
         return;
      
      // Verify that the user has permission to perform the split
      $Sender->Permission('Vanilla.Discussions.Edit', TRUE, 'Category', $Discussion->PermissionCategoryID);
      
      $CheckedComments = Gdn::UserModel()->GetAttribute($Session->User->UserID, 'CheckedComments', array());
      if (!is_array($CheckedComments))
         $CheckedComments = array();
       
      $CommentIDs = array();
      foreach ($CheckedComments as $DiscID => $Comments) {
         foreach ($Comments as $Comment) {
            if ($DiscID == $DiscussionID)
               $CommentIDs[] = str_replace('Comment_', '', $Comment);
         }
      }
      // Load category data.
      $Sender->ShowCategorySelector = (bool)C('Vanilla.Categories.Use');
      $CountCheckedComments = count($CommentIDs);
      $Sender->SetData('CountCheckedComments', $CountCheckedComments);
      // Perform the split
      if ($Sender->Form->AuthenticatedPostBack()) {
         // Create a new discussion record
         $Data = $Sender->Form->FormValues();
         $Data['Body'] = sprintf(T('This discussion was created from comments split from: %s.'), Anchor(Gdn_Format::Text($Discussion->Name), 'discussion/'.$Discussion->DiscussionID.'/'.Gdn_Format::Url($Discussion->Name).'/'));
         $Data['Format'] = 'Html';
         $Data['Type'] = GetValue('Type', $Discussion, NULL);
         $NewDiscussionID = $DiscussionModel->Save($Data);
         $Sender->Form->SetValidationResults($DiscussionModel->ValidationResults());
         
         if ($Sender->Form->ErrorCount() == 0 && $NewDiscussionID > 0) {
            // Re-assign the comments to the new discussion record
            $DiscussionModel->SQL
               ->Update('Comment')
               ->Set('DiscussionID', $NewDiscussionID)
               ->WhereIn('CommentID', $CommentIDs)
               ->Put();
					
            // Update counts on both discussions
            $CommentModel = new CommentModel();
            $CommentModel->UpdateCommentCount($DiscussionID);
//            $CommentModel->UpdateUserCommentCounts($DiscussionID);
            $CommentModel->UpdateCommentCount($NewDiscussionID);
            $CommentModel->RemovePageCache($DiscussionID, 1);
            
            
            // Clear selections
            unset($CheckedComments[$DiscussionID]);
            Gdn::UserModel()->SaveAttribute($Session->UserID, 'CheckedComments', $CheckedComments);
            ModerationController::InformCheckedComments($Sender);
            $Sender->RedirectUrl = Url('discussion/'.$NewDiscussionID.'/'.Gdn_Format::Url($Data['Name']));
         }
      } else {
         $Sender->Form->SetValue('CategoryID', GetValue('CategoryID', $Discussion));
      }
      
      $Sender->Render($this->GetView('splitcomments.php'));
   }

   /**
    * Add a method to the ModerationController to handle merging discussions.
    * @param Gdn_Controller $Sender
    */
   public function ModerationController_MergeDiscussions_Create($Sender) {
      $Session = Gdn::Session();
      $Sender->Form = new Gdn_Form();
      $Sender->Title(T('Merge Discussions'));

      $DiscussionModel = new DiscussionModel();
      $CheckedDiscussions = Gdn::UserModel()->GetAttribute($Session->User->UserID, 'CheckedDiscussions', array());
      if (!is_array($CheckedDiscussions))
         $CheckedDiscussions = array();
       
      $DiscussionIDs = $CheckedDiscussions;
      $Sender->SetData('DiscussionIDs', $DiscussionIDs);
      $CountCheckedDiscussions = count($DiscussionIDs);
      $Sender->SetData('CountCheckedDiscussions', $CountCheckedDiscussions);
      $Discussions = $DiscussionModel->SQL->WhereIn('DiscussionID', $DiscussionIDs)->Get('Discussion')->ResultArray();
      $Sender->SetData('Discussions', $Discussions);
      
      // Perform the merge
      if ($Sender->Form->AuthenticatedPostBack()) {
         // Create a new discussion record
         $MergeDiscussion = FALSE;
         $MergeDiscussionID = $Sender->Form->GetFormValue('MergeDiscussionID');
         foreach ($Discussions as $Discussion) {
            if ($Discussion['DiscussionID'] == $MergeDiscussionID) {
               $MergeDiscussion = $Discussion;
               break;
            }
         }
         if ($MergeDiscussion) {
            $ErrorCount = 0;
            
            // Verify that the user has permission to perform the merge.
            $Category = CategoryModel::Categories($MergeDiscussion['CategoryID']);
            if ($Category && !$Category['PermsDiscussionsEdit'])
               throw PermissionException('Vanilla.Discussions.Edit');
            
            // Assign the comments to the new discussion record
            $DiscussionModel->SQL
               ->Update('Comment')
               ->Set('DiscussionID', $MergeDiscussionID)
               ->WhereIn('DiscussionID', $DiscussionIDs)
               ->Put();
               
            $CommentModel = new CommentModel();
            foreach ($Discussions as $Discussion) {
               if ($Discussion['DiscussionID'] == $MergeDiscussionID)
                  continue;
               
               // Create a comment out of the discussion.
               $Comment = ArrayTranslate($Discussion, array('Body', 'Format', 'DateInserted', 'InsertUserID', 'InsertIPAddress', 'DateUpdated', 'UpdateUserID', 'UpdateIPAddress', 'Attributes', 'Spam', 'Likes', 'Abuse'));
               $Comment['DiscussionID'] = $MergeDiscussionID;
               
               $CommentModel->Validation->Results(TRUE);
               $CommentID = $CommentModel->Save($Comment);
               if ($CommentID) {
                  // Move any attachments (FileUpload plugin awareness)
                  if (class_exists('MediaModel')) {
                     $MediaModel = new MediaModel();
                     $MediaModel->Reassign($Discussion['DiscussionID'], 'discussion', $CommentID, 'comment');
                  }
                  
                  // Delete discussion that was merged
                  $DiscussionModel->Delete($Discussion['DiscussionID']);                  
               } else {
                  $Sender->InformMessage($CommentModel->Validation->ResultsText());
                  $ErrorCount++;
               }
            }
            // Update counts on all affected discussions.
            $CommentModel->UpdateCommentCount($MergeDiscussionID);
            $CommentModel->RemovePageCache($MergeDiscussionID);
   
            // Clear selections
            Gdn::UserModel()->SaveAttribute($Session->UserID, 'CheckedDiscussions', FALSE);
            ModerationController::InformCheckedDiscussions($Sender);
            if ($ErrorCount == 0)
               $Sender->RedirectUrl = Url("/discussion/$MergeDiscussionID/".Gdn_Format::Url($MergeDiscussion['Name']));
         }
      }
      
      $Sender->Render('MergeDiscussions', '', 'plugins/SplitMerge');
   }

   public function Setup() {
      SaveToConfig('Vanilla.AdminCheckboxes.Use', TRUE);
   }
   
}