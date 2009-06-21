<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Mark O'Sullivan
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Mark O'Sullivan at mark [at] lussumo [dot] com
*/


// Define the plugin:
$PluginInfo['CommentScore'] = array(
   'Description' => 'The comment score plugin allows users to assign scores to comments.',
   'Version' => '1.0',
   'RequiredApplications' => array('Vanilla' => '>=2'),
   'RequiredTheme' => FALSE, 
   'RequiredPlugins' => FALSE,
   'HasLocale' => TRUE,
   //'RegisterPermissions' => array('Plugins.MetaData.Manage'),
   //'SettingsUrl' => '/garden/plugin/metadata', // Url of the plugin's settings page.
   //'SettingsPermission' => 'Plugins.MetaData.Manage', // The permission required to view the SettingsUrl.
   'Author' => "Todd Burry",
   'AuthorEmail' => 'todd@lussumo.com',
   'AuthorUrl' => 'http://toddburry.com'
);

class Gdn_CommentScorePlugin implements IPlugin {
   
   public function DiscussionController_Render_Before($Sender) {
      $Sender->Head->AddCss('/plugins/CommentScore/commentscore.css');
      $Sender->Head->AddScript('/plugins/CommentScore/commentscore.js');
   }
   
   public function DiscussionController_CommentOptions_Handler($Sender) {
      $Comment = $Sender->CurrentComment;
      $Session = Gdn::Session();
      
      $Inc = 1;
      $Signs = array(-$Inc => 'Neg', $Inc => 'Pos');
      
      // Create a container for the score.
      echo '<div class="CommentScore">';
      
      // Write the negative button.
      $Inc = -$Inc;
      echo Anchor(
         ($Inc < 0 ? '-' : '+') . abs($Inc),
         '/vanilla/discussion/score/' . $Comment->CommentID . '/' . $Signs[$Inc] . abs($Inc) . '/' . $Session->TransientKey() . '?Target=' . urlencode($Sender->SelfUrl),
         $Signs[$Inc] . ' Inc',
         array('title' => ($Inc < 0 ? '-' : '+') . abs($Inc))
      );
      
      // Write the current score.
      echo '<span class="Score">' . $Comment->SumScore . '</span>';
      
      // Write the positive button.
      $Inc = -$Inc;
      echo Anchor(
         ($Inc < 0 ? '-' : '+') . abs($Inc),
         '/vanilla/discussion/score/' . $Comment->CommentID . '/' . $Signs[$Inc] . abs($Inc) . '/' . $Session->TransientKey() . '?Target=' . urlencode($Sender->SelfUrl),
         $Signs[$Inc] . ' Inc',
         array('title' => ($Inc < 0 ? '-' : '+') . abs($Inc))
      );
      
      echo '</div>';
   }
   
   /**
    * Add or subtract a value from a comment's score.
    * @param DiscussionController $Sender The controller that is implementing this method.
    * @param array $Args The arguments for the operation.
    */
   public function DiscussionController_Score_Create($Sender, $Args) {
      $CommentID = $Args[0];
      $Score = substr($Args[1], 3) * (substr($Args[1], 0, 3) == 'Neg' ? -1 : 1);
      //$TransientKey = $Args[2];
      
      $SQL = Gdn::SQL();
      $Session = Gdn::Session();
      
      // Get the current score for this user.
      $Data = $SQL
         ->Select('uc.Score')
         ->From('UserComment uc')
         ->Where('uc.CommentID', $CommentID)
         ->Where('uc.UserID', $Session->UserID)
         ->Get()
         ->FirstRow();
      $CurrentScore = $Data ? $Data->Score : 0;
      
      if($Data) {
         // Update the score on an existing comment.
         $SQL
            ->Update('UserComment')
            ->Set('Score', $CurrentScore + $Score)
            ->Set('DateUpdated', Format::ToDateTime())
            ->Set('UpdateUserID', $Session->UserID)
            ->Where('UserID', $Session->UserID)
            ->Where('CommentID', $CommentID)
            ->Put();
      } else {
         // Insert a new score.
         $SQL
            ->Insert('UserComment', array(
            'CommentID' => $CommentID,
            'UserID' => $Session->UserID,
            'Score'=> $Score,
            'DateInserted' => Format::ToDateTime(),
            'InsertUserID' => $Session->UserID,
            'DateUpdated' => Format::ToDateTime(),
            'UpdateUserID' => $Session->UserID)
            );
      }

      // Update the comment table with the sum of the scores.
      $Data = $SQL
         ->Select('uc.Score', 'sum', 'SumScore')
         ->From('UserComment uc')
         ->Where('uc.CommentID', $CommentID)
         ->Get()
         ->FirstRow();
      $SumScore = $Data ? $Data->SumScore : 0;
      
      $SQL
         ->Update('Comment')
         ->Set('SumScore', $SumScore)
         ->Where('CommentID', $CommentID)
         ->Put();
         
      // Redirect back where the user came from if necessary
      if ($Sender->DeliveryType() != DELIVERY_TYPE_BOOL) {
         $Target = GetIncomingValue('Target', '/vanilla/discussions/scored');
         Redirect($Target);
      }
      
      // Send the current information back to be dealt with on the client side.
      $Sender->SetJson('SumScore', $SumScore);
      $Sender->SetJson('Enabled', TRUE);
      $Sender->Render();   
      break;
   }
   
   public function Setup() {
      $Structure = Gdn::Structure();
      
      // Construct the user comment table.
      $Structure->Table('UserComment')
         ->Column('CommentID', 'int', 4, FALSE, NULL, 'primary', FALSE)
         ->Column('UserID', 'int', 4, FALSE, NULL, 'primary', FALSE)
         ->Column('Score', 'int', 4, TRUE)
         ->Column('InsertUserID', 'int', 10, FALSE, NULL, 'key')
         ->Column('UpdateUserID', 'int', 10, TRUE)
         ->Column('DateInserted', 'datetime')
         ->Column('DateUpdated', 'datetime')
         ->Set(FALSE, FALSE);
         
      // Add the total score to the comment table.
      $Structure->Table('Comment')
         ->Column('SumScore', 'int', 4, TRUE)
         ->Set(FALSE, FALSE);
   }
}