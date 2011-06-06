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
   'Version' => '1',
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
      if (Gdn::Session()->CheckPermission('Vanilla.Discussion.Edit', TRUE, 'Category', $Discussion->PermissionCategoryID))
         $ActionMessage .= ' '.Anchor(T('Split'), 'vanilla/moderation/splitcomments/'.$Discussion->DiscussionID.'/', 'Split Popup');
   }
   
   /**
    * Add "merge" action link.
    */
   public function Base_BeforeCheckDiscussions_Handler($Sender) {
      $ActionMessage = &$Sender->EventArguments['ActionMessage'];
      if (Gdn::Session()->CheckPermission('Vanilla.Discussion.Edit', TRUE, 'Category', 'any'))
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
      $Sender->Permission('Vanilla.Discussion.Edit', TRUE, 'Category', $Discussion->CategoryID);
      
      $CheckedComments = Gdn::UserModel()->GetAttribute($Session->User->UserID, 'CheckedComments', array());
      if (!is_array($CheckedComments))
         $CheckedComments = array();
       
      $CommentIDs = array();
      foreach ($CheckedComments as $DiscID => $Comments) {
         foreach ($Comments as $Comment) {
            if (substr($Comment, 0, 8) == 'Comment_' && $DiscID == $DiscussionID)
               $CommentIDs[] = str_replace('Comment_', '', $Comment);
         }
      }
      // Load category data
      $Sender->ShowCategorySelector = (bool)C('Vanilla.Categories.Use');
      if ($Sender->ShowCategorySelector) {
         $CategoryModel = new CategoryModel();
         $CategoryData = $CategoryModel->GetFull('', 'Vanilla.Discussions.Add');
         $aCategoryData = array();
         foreach ($CategoryData->Result() as $Category) {
            if ($Category->CategoryID <= 0)
               continue;
            
            if ($Discussion->CategoryID == $Category->CategoryID)
               $Sender->Category = $Category;
            
            $CategoryName = $Category->Name;   
            if ($Category->Depth > 1) {
               $CategoryName = '↳ '.$CategoryName;
               $CategoryName = str_pad($CategoryName, strlen($CategoryName) + $Category->Depth - 2, ' ', STR_PAD_LEFT);
               $CategoryName = str_replace(' ', '&#160;', $CategoryName);
            }
            $aCategoryData[$Category->CategoryID] = $CategoryName;
            $Sender->EventArguments['aCategoryData'] = &$aCategoryData;
				$Sender->EventArguments['Category'] = &$Category;
				$Sender->FireEvent('AfterCategoryItem');
         }
         $Sender->CategoryData = $aCategoryData;
      }
      
      $CountCheckedComments = count($CommentIDs);
      $Sender->SetData('CountCheckedComments', $CountCheckedComments);
      // Perform the split
      if ($Sender->Form->AuthenticatedPostBack()) {
         // Create a new discussion record
         $Data = $Sender->Form->FormValues();
         $Data['Body'] = sprintf(T('This discussion was created from comments split from: %s.'), Anchor(Gdn_Format::Text($Discussion->Name), 'discussion/'.$Discussion->DiscussionID.'/'.Gdn_Format::Url($Discussion->Name).'/'));
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
            $CommentModel->UpdateUserCommentCounts($DiscussionID);
            $CommentModel->UpdateCommentCount($NewDiscussionID);
   
            // Clear selections
            unset($CheckedComments[$DiscussionID]);
            Gdn::UserModel()->SaveAttribute($Session->UserID, 'CheckedComments', $CheckedComments);
            ModerationController::InformCheckedComments($Sender);
            $Sender->RedirectUrl = Url('discussion/'.$NewDiscussionID.'/'.Gdn_Format::Url($Data['Name']));
         }
      }
      
      $Sender->Render($this->GetView('splitcomments.php'));
   }

   /**
    * Add a method to the ModerationController to handle merging discussions.
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
      $DiscussionData = $DiscussionModel->GetIn($DiscussionIDs);
      $Sender->SetData('DiscussionData', $DiscussionData);
      
      // Perform the merge
      if ($Sender->Form->AuthenticatedPostBack()) {
         // Create a new discussion record
         $MergeDiscussion = FALSE;
         $MergeDiscussionID = $Sender->Form->GetFormValue('MergeDiscussionID');
         foreach ($DiscussionData->Result() as $Discussion) {
            if ($Discussion->DiscussionID == $MergeDiscussionID) {
               $MergeDiscussion = $Discussion;
               break;
            }
         }
         if ($MergeDiscussion) {
            // Verify that the user has permission to perform the merge
            $Sender->Permission('Vanilla.Discussion.Edit', TRUE, 'Category', $MergeDiscussion->CategoryID);
            
            // Assign the comments to the new discussion record
            $DiscussionModel->SQL
               ->Update('Comment')
               ->Set('DiscussionID', $MergeDiscussionID)
               ->WhereIn('DiscussionID', $DiscussionIDs)
               ->Put();
               
            $CommentModel = new CommentModel();
            foreach ($DiscussionIDs as $DiscussionID) {
               
               // Add a new comment to each empty discussion
               if ($DiscussionID != $MergeDiscussionID) {
                  // Add a comment to each one explaining the merge
                  $DiscussionAnchor = Anchor(
                     Gdn_Format::Text($MergeDiscussion->Name),
                     'discussion/'.$MergeDiscussionID.'/'.Gdn_Format::Url($MergeDiscussion->Name)
                  );
                  $CommentModel->Save(array(
                     'DiscussionID' => $DiscussionID,
                     'Body' => sprintf(T('This discussion was merged into %s'), $DiscussionAnchor)
                  ));
                  // Close non-merge discussions
                  $CommentModel->SQL->Update('Discussion')->Set('Closed', '1')->Where('DiscussionID', $DiscussionID)->Put();
               }
   
               // Update counts on all affected discussions
               $CommentModel->UpdateCommentCount($DiscussionID);
               $CommentModel->UpdateUserCommentCounts($DiscussionID);
            }
   
            // Clear selections
            Gdn::UserModel()->SaveAttribute($Session->UserID, 'CheckedDiscussions', FALSE);
            ModerationController::InformCheckedDiscussions($Sender);
            $Sender->RedirectUrl = Url('discussion/'.$MergeDiscussionID.'/'.Gdn_Format::Url($MergeDiscussion->Name));
         }
      }
      
      $Sender->Render($this->GetView('mergediscussions.php'));
   }

   public function Setup() {
      SaveToConfig('Vanilla.AdminCheckboxes.Use', TRUE);
   }
   
}