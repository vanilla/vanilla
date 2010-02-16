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
$PluginInfo['VanillaCommentScore'] = array(
   'Description' => 'The comment score plugin allows users to assign scores to comments.',
   'Version' => '1.0',
   'RequiredApplications' => array('Vanilla' => '>=2'),
   'RequiredTheme' => FALSE, 
   'RequiredPlugins' => FALSE,
   'HasLocale' => TRUE,
   'RegisterPermissions' => array('Plugins.Vanilla.CommentScore.Single', 'Plugins.Vanilla.CommentScore.Unlimited'),
   //'SettingsUrl' => '/garden/plugin/metadata', // Url of the plugin's settings page.
   //'SettingsPermission' => 'Plugins.MetaData.Manage', // The permission required to view the SettingsUrl.
   'Author' => "Todd Burry",
   'AuthorEmail' => 'todd@vanillaforums.com',
   'AuthorUrl' => 'http://toddburry.com'
);

class Gdn_VanillaCommentScorePlugin implements Gdn_IPlugin {
   
   public function DiscussionController_Render_Before($Sender) {
      $Sender->AddCssFile('/plugins/VanillaCommentScore/commentscore.css');
      $Sender->AddJsFile('/plugins/VanillaCommentScore/commentscore.js');
   }
   
   public function DiscussionController_CommentOptions_Handler($Sender) {
      $Comment = $Sender->CurrentComment;
      $Session = Gdn::Session();
      
      $Inc = $this->GetScoreIncrements($Comment->CommentID);
      
      $Signs = array(-1 => 'Neg', +1 => 'Pos');
      
      // Create a container for the score.
      echo '<div class="CommentScore">';
      
      $SumScore = (is_null($Comment->SumScore) ? 0 : $Comment->SumScore);
      
      // Write the current score.
      echo '<span class="Score">' . sprintf(Plural($SumScore, '%s point', '%s points'), $SumScore) . '</span>';
      
      // Write the buttons.
      foreach($Inc as $Key => $IncAmount) {
         $Button = '<span>'.($Key < 0 ? '-' : '+').'</span>';
         
         $Attributes = array();
         $CssClass = $Signs[$Key] . ' Inc';
         $Href = '/vanilla/discussion/score/' . $Comment->CommentID . '/' . $Signs[$Key] . '/' . $Session->TransientKey() . '?Target=' . urlencode($Sender->SelfUrl);
         
         if($IncAmount == 0) {
            $Attributes['href2'] = Url($Href);
            $CssClass .= ' Disabled';
            $Href = '';
         } else {
            $Attributes['title'] = ($Key > 0 ? '+' : '') . $Inc[$Key];
         }
         
         // Display an increment button.
         $Foo = Anchor($Button, $Href, $CssClass, $Attributes, TRUE);
         echo $Foo;
      }
      
      echo '</div>';
   }

   // This is necessary for AJAX comments because it appears that they route through
   // a different controller (the Post Controller).
   public function PostController_CommentOptions_Handler($Sender) {
     $this->DiscussionController_CommentOptions_Handler($Sender);
   }

   /**
    * Add or subtract a value from a comment's score.
    * @param DiscussionController $Sender The controller that is implementing this method.
    * @param array $Args The arguments for the operation.
    */
   public function DiscussionController_Score_Create($Sender, $Args) {
      $CommentID = $Args[0];
      $ScoreKey = (substr($Args[1], 0, 3) == 'Neg' ? -1 : 1);
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
      $UserScore = $Data ? $Data->Score : 0;
      
      // Get the score increments.
      $Inc = $this->GetScoreIncrements($CommentID, $UserScore);
      $Score = $Inc[$ScoreKey];
      $UserScore += $Score;
      
      if($Score != 0) {
         if($Data) {
            // Update the score on an existing comment.
            $SQL
               ->Update('UserComment')
               ->Set('Score', $UserScore)
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
               'Score'=> $UserScore,
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
            
         $Inc = $this->GetScoreIncrements($CommentID, $UserScore);
      }
         
      // Redirect back where the user came from if necessary
      if ($Sender->DeliveryType() != DELIVERY_TYPE_BOOL) {
         $Target = GetIncomingValue('Target', '/vanilla/discussions/scored');
         Redirect($Target);
      }
      
      // Send the current information back to be dealt with on the client side.
      $Sender->SetJson('SumScore', isset($SumScore) ? sprintf(Plural($SumScore, '%s point', '%s points'), $SumScore) : NULL);
      $Sender->SetJson('Inc', $Inc);
      $Sender->Render();   
      break;
   }
   
   public function GetScoreIncrements($CommentID, $UserScore = NULL) {
      $Session = Gdn::Session();
      
      // Figure out how much the user can increment the score by depending on permissions.
      $SinglePermission = $Session->CheckPermission('Plugins.Vanilla.CommentScore.Single');
      $UnlimitPermission = $Session->CheckPermission('Plugins.Vanilla.CommentScore.Unlimited');
      
      $Inc = array(-1 => 0, +1 => 0);
      if($SinglePermission || $UnlimitPermission) {
         if(is_null($UserScore)) {
            $UserScore = Gdn::SQL()
               ->Select('uc.Score')
               ->From('UserComment uc')
               ->Where('uc.CommentID', $CommentID)
               ->Where('uc.UserID', $Session->UserID)
               ->Get()
               ->FirstRow();
            $UserScore = $UserScore ? $UserScore->Score : 0;
         }
            
         if($UnlimitPermission) {
            // A user with unlimit permission can sway the score by any number of points.
            if(abs($UserScore) <= 15) {
               $Inc = array(-1 => -5, +1 => +5);
            } else {
               $Inc = array(-1 => -10, +1 => +10);
            }
         } elseif($SinglePermission) {
            // A user with the single permission can only sway the score by one point.
            switch($UserScore) {
               case -1; $Inc[1] = 1; break;
               case 0; $Inc[-1] = -1; $Inc[1] = 1; break;
               case 1; $Inc[-1] = -1; break;
            }
         }
      }
      
      return $Inc;
   }
   
   public function Setup() {
      $Structure = Gdn::Structure();
      
      // Construct the user comment table.
      $Structure->Table('UserComment')
         ->PrimaryKey('CommentID', 'int', FALSE, 'primary')
         ->Column('UserID', 'int', FALSE, 'primary')
         ->Column('Score', 'int', TRUE)
         ->Column('InsertUserID', 'int', FALSE, 'key')
         ->Column('UpdateUserID', 'int', TRUE)
         ->Column('DateInserted', 'datetime')
         ->Column('DateUpdated', 'datetime')
         ->Set(FALSE, FALSE);
         
      // Add the total score to the comment table.
      $Structure->Table('Comment')
         ->Column('Score', 'int', TRUE)
         ->Set(FALSE, FALSE);
   }
}