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
$PluginInfo['VanillaCommentReplies'] = array(
   'Name' => 'Vanilla Replies',
   'Description' => "Adds one-level-deep replies to comments in Vanilla discussions.",
   'Version' => '1',
   'Author' => "Mark O'Sullivan",
   'AuthorEmail' => 'mark@vanillaforums.com',
   'AuthorUrl' => 'http://markosullivan.ca',
   'RegisterPermissions' => FALSE,
   'SettingsPermission' => FALSE
);

Gdn::FactoryInstall(
   'ReplyModel',
   'ReplyModel',
   PATH_PLUGINS.DS.'VanillaCommentReplies'.DS.'class.replymodel.php',
   Gdn::FactoryInstance,
   NULL,
   FALSE
);

class VanillaCommentRepliesPlugin implements Gdn_IPlugin {
   
   public $ReplyModel;
   
   public function DiscussionController_BeforeCommentRender_Handler(&$Sender) {
      $this->DiscussionController_BeforeDiscussionRender_Handler($Sender);
   }
   
   public function DiscussionController_BeforeDiscussionRender_Handler(&$Sender) {
      $Sender->AddJsFile('/plugins/VanillaCommentReplies/replies.js');
      $Sender->AddCssFile('/plugins/VanillaCommentReplies/style.css');
      $this->ReplyModel = Gdn::Factory('ReplyModel');
      $RequestMethod = strtolower($Sender->RequestMethod);
      if (in_array($RequestMethod, array('index', 'comment'))) {
         // Load the replies
         if ($Sender->CommentData->NumRows() > 0) {
            $FirstCommentID = $Sender->CommentData->FirstRow()->CommentID;
            $LastCommentID = $Sender->CommentData->LastRow()->CommentID;
            $Sender->ReplyData = $this->ReplyModel->Get($Sender->Discussion->DiscussionID, $FirstCommentID, $LastCommentID);
            
            $LastID = 0;
            foreach($Sender->ReplyData->Result() as $Row) {
               if($Row->CommentID > $LastID)
                  $LastID = $Row->CommentID;
            }
            
            if($LastID > $LastCommentID) {
               $Sender->AddDefinition('LastCommentID', $LastID);
            }
         } else {
            $Sender->ReplyData = FALSE;
         }
         if ($Sender->DeliveryType() == DELIVERY_TYPE_ALL) {
            // Add some definitions for javascript
            $Sender->AddDefinition('Reply', T('Show 1 more reply...'));
            $Sender->AddDefinition('Replies', T('Show %s more replies...'));
         }
      }
   }
   
   public function DiscussionController_BeforeCommentsRender_Handler(&$Sender) {
      $ReplyFormAction = Url('vanilla/post/reply');
      if(isset($Sender->ReplyData) && is_object($Sender->ReplyData))
         $Sender->CurrentReply = $Sender->ReplyData->NextRow();
      else
         $Sender->CurrentReply = FALSE;
   }
   
   public function DiscussionController_AfterCommentMeta_Handler(&$Sender) {
      $Session = Gdn::Session();
      $Comment = $Sender->EventArguments['Comment'];
      if ($Session->IsValid()) {
         ?>
         <li class="ReplyCount">
         <?php
         $ReplyText = 'Reply';
         if ($Sender->Discussion->Closed == '1')
            $ReplyText = '&nbsp;';
            
         $ReplyText = $Comment->CountReplies > 0 ? sprintf(T(Plural($Comment->CountReplies, '%s Reply', '%s Replies')), $Comment->CountReplies) : $ReplyText;
         echo $Sender->Discussion->Closed == '1' ? T($ReplyText) : Anchor(T($ReplyText), '/post/reply/'.$Comment->CommentID, "ReplyLink");
         ?>
      </li>
      <?php
      } 
   }
   
   public function DiscussionController_AfterCommentBody_Handler(&$Sender) {
      $Session = Gdn::Session();
      $Comment = $Sender->EventArguments['Comment'];
      if (is_object($Sender->CurrentReply) && $Sender->CurrentReply->ReplyCommentID == $Comment->CommentID) {
         echo '<ul class="Replies">';
         while (is_object($Sender->CurrentReply) && $Sender->CurrentReply->ReplyCommentID == $Comment->CommentID) {
            VanillaCommentRepliesPlugin::WriteReply($Sender, $Session);
            $Sender->CurrentReply = $Sender->ReplyData->NextRow();
         }
      } else {
         echo '<ul class="Replies Hidden">';
      }
      if ($Session->IsValid() && $Sender->Discussion->Closed == '0') {
         echo '<li class="ReplyForm">';
            echo Anchor(T('Write a reply'), '/vanilla/post/reply/'.$Comment->CommentID, 'ReplyLink Hidden');
            $ReplyForm = Gdn::Factory('Form');
            $ReplyForm->SetModel($this->ReplyModel);
            $ReplyForm->AddHidden('ReplyCommentID', $Comment->CommentID);
            echo $ReplyForm->Open(array('action' => Url('/vanilla/post/reply'), 'class' => 'Hidden'));
            echo $ReplyForm->TextBox('Body', array('MultiLine' => TRUE, 'value' => ''));
            echo $ReplyForm->Close('Reply');
         echo '</li>';
      }
      echo '</ul>';
   }
   
   static public function WriteReply(&$Sender, &$Session) {
      ?>
         <li class="Reply" id="Comment_<?php echo $Sender->CurrentReply->CommentID; ?>">
            <?php
            // Delete comment
            if ($Session->CheckPermission('Vanilla.Comments.Delete', $Sender->Discussion->CategoryID))
               echo Anchor(T('Delete'), 'vanilla/discussion/deletecomment/'.$Sender->CurrentReply->CommentID.'/'.$Session->TransientKey(), 'DeleteReply');
         
            ?>
            <ul class="Info<?php echo ($Sender->CurrentReply->InsertUserID == $Session->UserID ? ' Author' : '') ?>">
               <li class="Author"><?php
                  $Author = UserBuilder($Sender->CurrentReply, 'Insert');
                  echo UserPhoto($Author);
                  echo UserAnchor($Author);
               ?></li>
               <li class="Created"><?php echo Format::Date($Sender->CurrentReply->DateInserted); ?></li>
               <li class="Permalink"><?php echo Anchor(T('Permalink'), '/discussion/comment/'.(isset($Sender->CurrentComment) ? $Sender->CurrentComment->CommentID : $Sender->ReplyCommentID).'/#Comment_'.$Sender->CurrentReply->CommentID, T('Permalink')); ?></li>
            </ul>
            <div class="Body"><?php echo Format::To($Sender->CurrentReply->Body, $Sender->CurrentReply->Format); ?></div>
         </li>
      <?php
   }
   
   public function DiscussionController_GetNew_Before(&$Sender, $EventArguments) {
      $DiscussionID = $EventArguments[0];
      $LastCommentID = $EventArguments[1];
      
      // Get all of the new replies.
      $this->ReplyModel = Gdn::Factory('ReplyModel');
      $Data = $this->ReplyModel->GetAllNew($DiscussionID, $LastCommentID)->Result();
      $MaxCommentID = 0;
      
      if(count($Data) == 0)
         return;
      
      $Discussion = $Sender->DiscussionModel->GetID($DiscussionID);
      
      // Loop through the replies and add them to the targets.
      $LastReplyCommentID = FALSE;
      $Replies = array();
      for($i = 0; $i < count($Data); $i++) {
         $Row = $Data[$i];
         if($Row->CommentID > $MaxCommentID)
            $MaxCommentID = $Row->CommentID;
         
         if($LastReplyCommentID != $Row->ReplyCommentID) {
            $this->_AddReplyTargets($Discussion, $Replies, $Sender);
            $Replies = array();
            $LastReplyCommentID = $Row->ReplyCommentID;
         }
         $Replies[] = $Row;
      }
      $this->_AddReplyTargets($Discussion, $Replies, $Sender);
      
      $LastCommentID = $Sender->Json('LastCommentID');
      if(is_null($LastCommentID) || $MaxCommentID > $LastCommentID) {
         $Sender->Json('LastCommentID', $MaxCommentID);
      }
   }
   
   protected function _AddReplyTargets($Discussion, $Replies, $Sender) {
      if(count($Replies) > 0) {
         $ReplySender = new stdClass();
         $ReplySender->ReplyCommentID = $Replies[0]->ReplyCommentID;
         $ReplySender->Discussion = $Discussion;
         
         // Add a target to render the replies.
         ob_start();
         foreach($Replies as $Reply) {
            $ReplySender->CurrentReply = $Reply;
            self::WriteReply($ReplySender, Gdn::Session());
         }
         $Html = ob_get_clean();
         $Sender->JsonTarget(
            '#Comment_'.$ReplySender->ReplyCommentID.' li.ReplyForm',
            $Html,
            'Before');
      }
   }
   
   /*
    * Add a Reply method to the DiscussionController to handle linking directly to a reply
    */
   public function DiscussionController_Reply_Create(&$Sender, $ReplyCommentID) {
      // Get the discussionID and parent CommentID
      $ReplyModel = Gdn::Factory('ReplyModel');
      $Reply = $ReplyModel->GetID($ReplyCommentID);
      $CommentID = $Reply->ReplyCommentID;
      $DiscussionID = $Reply->DiscussionID;
      
      // Figure out how many comments are before this one
      $Offset = $Sender->CommentModel->GetOffset($CommentID);
      $Limit = Gdn::Config('Vanilla.Comments.PerPage', 50);
      
      // (((67 comments / 10 perpage) = 6.7) rounded down = 6) * 10 perpage = offset 60;
      $Offset = floor($Offset / $Limit) * $Limit;
      
      $Sender->View = 'index';
      $Sender->Index($DiscussionID, $Offset, $Limit);
   }
   
   public function PostController_BeforeCommentRender_Handler(&$Sender, $DiscussionID = '') {
      //$Draft = $Sender->EventArguments['Draft'];
      $Editing = $Sender->EventArguments['Editing'];
      $DiscussionID = $Sender->DiscussionID;
      $CommentID = $Sender->EventArguments['CommentID'];
      if ($Sender->Form->AuthenticatedPostBack() !== FALSE
         && $Sender->DeliveryType() == DELIVERY_TYPE_ALL
         && $Sender->Form->Errors() == 0) {
         //&& !$Draft) {
         $Sender->ReplyModel = Gdn::Factory('ReplyModel');
         if ($Editing) {
            $Sender->ReplyData = $Sender->ReplyModel->Get($DiscussionID, $CommentID, $CommentID);
         } else {
            $FirstCommentID = $Sender->CommentData->FirstRow()->CommentID;
            $LastCommentID = $Sender->CommentData->LastRow()->CommentID;
            $Sender->ReplyData = $Sender->ReplyModel->Get($DiscussionID, $FirstCommentID, $LastCommentID);
         }
      }
   }
   
   /*
    * Add a Reply method to Vanilla's Post controller
    */
   public function PostController_Reply_Create(&$Sender, $EventArguments = '') {
      $Sender->View = PATH_PLUGINS.DS.'VanillaCommentReplies'.DS.'views'.DS.'vanilla_post_reply.php';
      $ReplyCommentID = 0;
      if (is_array($EventArguments) && array_key_exists(0, $EventArguments))
         $ReplyCommentID = is_numeric($EventArguments[0]) ? $EventArguments[0] : 0;
      
      $ReplyModel = Gdn::Factory('ReplyModel');
      $Sender->ReplyCommentID = $ReplyCommentID;
      
      // Set the model on the form.
      $Sender->Form->SetModel($ReplyModel);
      
      // Make sure the form knows which comment we're replying to
      $Sender->Form->AddHidden('ReplyCommentID', $ReplyCommentID);
      $Sender->ReplyComment = $Sender->CommentModel->GetID($ReplyCommentID);
      $Discussion = $Sender->DiscussionModel->GetID($Sender->ReplyComment->DiscussionID);
      $Sender->Permission('Vanilla.Comments.Add', $Discussion->CategoryID);
      
      if ($Sender->Form->AuthenticatedPostBack()) {
         $CommentID = $Sender->Form->Save();
         if ($Sender->Form->ErrorCount() == 0) {
            // Redirect if this is not an ajax request
            if ($Sender->DeliveryType() == DELIVERY_TYPE_ALL) {
               $Discussion = $ReplyModel->GetDiscussion($CommentID);
               Redirect('/vanilla/discussion/'.$Discussion->DiscussionID.'/'.Format::Url($Discussion->Name).'#Comment_'.$CommentID);
            }
            
            // Load all new replies that the user hasn't seen yet
            $LastCommentID = $Sender->Form->GetFormValue('LastCommentID');
            if (!is_numeric($LastCommentID))
               $LastCommentID = $CommentID - 1;
               
            $Sender->ReplyData = $ReplyModel->GetNew($ReplyCommentID, $LastCommentID);
            $Sender->CurrentReply = is_object($Sender->ReplyData) ? $Sender->ReplyData->NextRow() : FALSE;
            $Replies = $Sender->ReplyComment->CountReplies + 1;
            $Sender->SetJson('Replies', sprintf(T(Plural($Replies, '%s Reply', '%s Replies')), $Replies));
            $Sender->SetJson('CommentID', $CommentID);
            $Sender->Discussion = $Sender->DiscussionModel->GetID($Sender->ReplyComment->DiscussionID);
            $Sender->ControllerName = 'discussion';
            $Sender->View = PATH_PLUGINS.DS.'VanillaCommentReplies'.DS.'views'.DS.'replies.php';
         } else if ($Sender->DeliveryType() !== DELIVERY_TYPE_ALL) {
            // Handle ajax-based errors
            $Sender->StatusMessage = $Sender->Form->Errors();            
         }
      }
      $Sender->Render();
   }
   
   public function Gdn_CommentModel_AfterCommentQuery_Handler(&$Sender) {
      $Sender->SQL->Where('c.ReplyCommentID is null');
   }
   
   public function Gdn_CommentModel_BeforeGetCount_Handler(&$Sender) {
      $Sender->SQL->Where('ReplyCommentID is null');
   }
   
   public function Gdn_CommentModel_BeforeGetOffset_Handler(&$Sender) {
      $Sender->SQL->Where('c2.ReplyCommentID is null');
   }
   
   public function Gdn_CommentModel_BeforeUpdateCommentCount_Handler(&$Sender) {
      $Sender->SQL->Where('c2.ReplyCommentID is null');
   }
   
   public function Gdn_CommentModel_DeleteComment_Handler(&$Sender) {
      $CommentID = $Sender->EventArguments['CommentID'];
      $Sender->SQL->Delete('Comment', array('ReplyCommentID' => $CommentID));
   }
   
   public function Setup() {
      $Structure = Gdn::Structure();
      
      // Make sure to add the ReplyCommentID field if it is not there already.
      $Structure->Table('Discussion')
         ->Column('CountReplies', 'int', '0')
         ->Set(FALSE, FALSE);
      
      $Structure->Table('Comment')
         ->Column('ReplyCommentID', 'int', TRUE, 'key')
         ->Column('CountReplies', 'int', '0')
         ->Set(FALSE, FALSE);
         
      $Structure->Table('CommentWatch')
         ->Column('CountReplies', 'int', '0')
         ->Set(FALSE, FALSE);

      // Add the activities & activity types for replies
      $SQL = Gdn::SQL();
      if ($SQL->GetWhere('ActivityType', array('Name' => 'CommentReply'))->NumRows() == 0)
         $SQL->Insert('ActivityType', array('AllowComments' => '0', 'Name' => 'CommentReply', 'FullHeadline' => '%1$s replied to %4$s %8$s.', 'ProfileHeadline' => '%1$s replied to %4$s %8$s.', 'RouteCode' => 'comment', 'Notify' => '1', 'Public' => '0'));      
   }
}