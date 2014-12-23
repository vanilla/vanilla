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
$PluginInfo['Voting'] = array(
   'Name' => 'Voting',
   'Description' => 'DEPRECATED. Upgrade to Reactions. Allows users to vote for comments and discussions. Administrators get unlimited votes.',
   'Version' => '1.1.4',
   'Author' => "Mark O'Sullivan",
   'AuthorEmail' => 'mark@vanillaforums.com',
   'AuthorUrl' => 'http://markosullivan.ca',
   'RequiredApplications' => array('Vanilla' => '2.0.17'),
   'SettingsUrl' => '/settings/voting',
   'SettingsPermission' => 'Garden.Settings.Manage'
);

class VotingPlugin extends Gdn_Plugin {
	/**
	 * Admin Toggle to turn Voting on/off
	 */
   public function Base_GetAppSettingsMenuItems_Handler(&$Sender) {
      $Menu = &$Sender->EventArguments['SideMenu'];
      $Menu->AddItem('Forum', T('Forum'));
      $Menu->AddLink('Forum', T('Voting'), 'settings/voting', 'Garden.Settings.Manage');
   }
   public function SettingsController_Voting_Create($Sender) {
      $Sender->Permission('Garden.Settings.Manage');
      $Conf = new ConfigurationModule($Sender);
		$Conf->Initialize(array(
			'Plugins.Voting.ModThreshold1' => array('Type' => 'int', 'Control' => 'TextBox', 'Default' => -10, 'Description' => 'The vote that will flag a post for moderation.'),
			'Plugins.Voting.ModThreshold2' => array('Type' => 'int', 'Control' => 'TextBox', 'Default' => -20, 'Description' => 'The vote that will remove a post to the moderation queue.')
		));

     $Sender->AddSideMenu('dashboard/settings/voting');
     $Sender->SetData('Title', T('Vote Settings'));
     $Sender->ConfigurationModule = $Conf;
     $Conf->RenderAll();
   }
   public function SettingsController_ToggleVoting_Create($Sender) {
      $Sender->Permission('Garden.Settings.Manage');
      if (Gdn::Session()->ValidateTransientKey(GetValue(0, $Sender->RequestArgs)))
         SaveToConfig('Plugins.Voting.Enabled', C('Plugins.Voting.Enabled') ? FALSE : TRUE);

      Redirect('settings/voting');
   }

	/**
	 * Add JS & CSS to the page.
	 */
   public function AddJsCss($Sender) {
//		if (!C('Plugins.Voting.Enabled'))
//			return;

      $Sender->AddCSSFile('voting.css', 'plugins/Voting');
		$Sender->AddJSFile('voting.js', 'plugins/Voting');
   }
	public function DiscussionsController_Render_Before($Sender) {
		$this->AddJsCss($Sender);
	}
   public function CategoriesController_Render_Before($Sender) {
      $this->AddJsCss($Sender);
   }

	/**
	 * Add the "Stats" buttons to the discussion list.
	 */
	public function Base_BeforeDiscussionContent_Handler($Sender) {
//		if (!C('Plugins.Voting.Enabled'))
//			return;

		$Session = Gdn::Session();
		$Discussion = GetValue('Discussion', $Sender->EventArguments);
		// Answers
		$Css = 'StatBox AnswersBox';
		if ($Discussion->CountComments > 1)
			$Css .= ' HasAnswersBox';

		$CountVotes = 0;
		if (is_numeric($Discussion->Score)) // && $Discussion->Score > 0)
			$CountVotes = $Discussion->Score;

		if (!is_numeric($Discussion->CountBookmarks))
			$Discussion->CountBookmarks = 0;

		echo Wrap(
			// Anchor(
			Wrap(T('Comments')) . Gdn_Format::BigNumber($Discussion->CountComments)
			// ,'/discussion/'.$Discussion->DiscussionID.'/'.Gdn_Format::Url($Discussion->Name).($Discussion->CountCommentWatch > 0 ? '/#Item_'.$Discussion->CountCommentWatch : '')
			// )
			, 'div', array('class' => $Css));

		// Views
		echo Wrap(
			// Anchor(
			Wrap(T('Views')) . Gdn_Format::BigNumber($Discussion->CountViews)
			// , '/discussion/'.$Discussion->DiscussionID.'/'.Gdn_Format::Url($Discussion->Name).($Discussion->CountCommentWatch > 0 ? '/#Item_'.$Discussion->CountCommentWatch : '')
			// )
			, 'div', array('class' => 'StatBox ViewsBox'));

		// Follows
		$Title = T($Discussion->Bookmarked == '1' ? 'Unbookmark' : 'Bookmark');
		if ($Session->IsValid()) {
			echo Wrap(Anchor(
				Wrap(T('Follows')) . Gdn_Format::BigNumber($Discussion->CountBookmarks),
				'/vanilla/discussion/bookmark/'.$Discussion->DiscussionID.'/'.$Session->TransientKey().'?Target='.urlencode($Sender->SelfUrl),
				'',
				array('title' => $Title)
			), 'div', array('class' => 'StatBox FollowsBox'));
		} else {
			echo Wrap(Wrap(T('Follows')) . $Discussion->CountBookmarks, 'div', array('class' => 'StatBox FollowsBox'));
		}

		// Votes
		if ($Session->IsValid()) {
			echo Wrap(Anchor(
				Wrap(T('Votes')) . Gdn_Format::BigNumber($CountVotes),
				'/vanilla/discussion/votediscussion/'.$Discussion->DiscussionID.'/'.$Session->TransientKey().'?Target='.urlencode($Sender->SelfUrl),
				'',
				array('title' => T('Vote'))
			), 'div', array('class' => 'StatBox VotesBox'));
		} else {
			echo Wrap(Wrap(T('Votes')) . $CountVotes, 'div', array('class' => 'StatBox VotesBox'));
		}
	}

   /**
	 * Sort the comments by popularity if necessary
    * @param CommentModel $CommentModel
	 */
   public function CommentModel_AfterConstruct_Handler($CommentModel) {
//		if (!C('Plugins.Voting.Enabled'))
//			return;

      $Sort = self::CommentSort();

      switch (strtolower($Sort)) {
         case 'date':
            $CommentModel->OrderBy('c.DateInserted');
            break;
         case 'popular':
         default:
            $CommentModel->OrderBy(array('coalesce(c.Score, 0) desc', 'c.CommentID'));
            break;
      }
   }

   protected static $_CommentSort;
   public static function CommentSort() {
//		if (!C('Plugins.Voting.Enabled'))
//			return;

      if (self::$_CommentSort)
         return self::$_CommentSort;

      $Sort = GetIncomingValue('Sort', '');
      if (Gdn::Session()->IsValid()) {
         if ($Sort == '') {
            // No sort was specified so grab it from the user's preferences.
            $Sort = Gdn::Session()->GetPreference('Plugins.Voting.CommentSort', 'popular');
         } else {
            // Save the sort to the user's preferences.
            Gdn::Session()->SetPreference('Plugins.Voting.CommentSort', $Sort == 'popular' ? '' : $Sort);
         }
      }

      if (!in_array($Sort, array('popular', 'date')))
         $Sort = 'popular';
      self::$_CommentSort = $Sort;
      return $Sort;
   }

	/**
	 * Insert sorting tabs after first comment.
	 */
	public function DiscussionController_BeforeCommentDisplay_Handler($Sender) {
//		if (!C('Plugins.Voting.Enabled'))
//			return;

		$AnswerCount = $Sender->Discussion->CountComments - 1;
		$Type = GetValue('Type', $Sender->EventArguments, 'Comment');
		if ($Type == 'Comment' && !GetValue('VoteHeaderWritten', $Sender)) { //$Type != 'Comment' && $AnswerCount > 0) {
		?>
		<li class="Item">
			<div class="VotingSort">
			<?php
			echo
				Wrap($AnswerCount.' '.Plural($AnswerCount, 'Comment', 'Comments'), 'strong');
				echo ' sorted by '
               .Anchor('Votes', Url(DiscussionUrl($Sender->Discussion).'?Sort=popular', TRUE), '', array('rel' => 'nofollow', 'class' => self::CommentSort() == 'popular' ? 'Active' : ''))
               .' '
               .Anchor('Date Added', Url(DiscussionUrl($Sender->Discussion).'?Sort=date', TRUE), '', array('rel' => 'nofollow', 'class' => self::CommentSort() == 'date' ? 'Active' : ''));
			?>
			</div>
		</li>
		<?php
      $Sender->VoteHeaderWritten = TRUE;
		}
	}

	public function DiscussionController_AfterCommentMeta_Handler($Sender) {
//		if (!C('Plugins.Voting.Enabled'))
//			return;
/*
		echo '<span class="Votes">';
			$Session = Gdn::Session();
			$Object = GetValue('Object', $Sender->EventArguments);
			$VoteType = $Sender->EventArguments['Type'] == 'Discussion' ? 'votediscussion' : 'votecomment';
			$ID = $Sender->EventArguments['Type'] == 'Discussion' ? $Object->DiscussionID : $Object->CommentID;
			$CssClass = '';
			$VoteUpUrl = '/discussion/'.$VoteType.'/'.$ID.'/voteup/'.$Session->TransientKey().'/';
			$VoteDownUrl = '/discussion/'.$VoteType.'/'.$ID.'/votedown/'.$Session->TransientKey().'/';
			if (!$Session->IsValid()) {
				$VoteUpUrl = Gdn::Authenticator()->SignInUrl($Sender->SelfUrl);
				$VoteDownUrl = $VoteUpUrl;
				$CssClass = ' SignInPopup';
			}
			echo Anchor(Wrap(Wrap('Vote Up', 'i'), 'i', array('class' => 'ArrowSprite SpriteUp', 'rel' => 'nofollow')), $VoteUpUrl, 'VoteUp'.$CssClass);
			echo Wrap(StringIsNullOrEmpty($Object->Score) ? '0' : Gdn_Format::BigNumber($Object->Score));
			echo Anchor(Wrap(Wrap('Vote Down', 'i'), 'i', array('class' => 'ArrowSprite SpriteDown', 'rel' => 'nofollow')), $VoteDownUrl, 'VoteDown'.$CssClass);
		echo '</span>';

 */

      $Session = Gdn::Session();
      $Object = GetValue('Object', $Sender->EventArguments);
      $VoteType = $Sender->EventArguments['Type'] == 'Discussion' ? 'votediscussion' : 'votecomment';
      $ID = $Sender->EventArguments['Type'] == 'Discussion' ? $Object->DiscussionID : $Object->CommentID;
      $CssClass = '';
      $VoteUpUrl = '/discussion/'.$VoteType.'/'.$ID.'/voteup/'.$Session->TransientKey().'/';
      $VoteDownUrl = '/discussion/'.$VoteType.'/'.$ID.'/votedown/'.$Session->TransientKey().'/';
      if (!$Session->IsValid()) {
         $VoteUpUrl = Gdn::Authenticator()->SignInUrl($Sender->SelfUrl);
         $VoteDownUrl = $VoteUpUrl;
         $CssClass = ' SignInPopup';
      }

      echo '<span class="Voter">';
			echo Anchor(Wrap(Wrap('Vote Up', 'i'), 'i', array('class' => 'ArrowSprite SpriteUp', 'rel' => 'nofollow')), $VoteUpUrl, 'VoteUp'.$CssClass);
			echo Wrap(StringIsNullOrEmpty($Object->Score) ? '0' : Gdn_Format::BigNumber($Object->Score));
			echo Anchor(Wrap(Wrap('Vote Down', 'i'), 'i', array('class' => 'ArrowSprite SpriteDown', 'rel' => 'nofollow')), $VoteDownUrl, 'VoteDown'.$CssClass);
		echo '</span>';
 }


   /**
	 * Add the vote.js file to discussions page, and handle sorting of answers.
	 */
   public function DiscussionController_Render_Before($Sender) {
//		if (!C('Plugins.Voting.Enabled'))
//			return;

      $this->AddJsCss($Sender);
   }


   /**
    * Increment/decrement comment scores
    */
   public function DiscussionController_VoteComment_Create($Sender) {
//		if (!C('Plugins.Voting.Enabled'))
//			return;

      $CommentID = GetValue(0, $Sender->RequestArgs, 0);
      $VoteType = GetValue(1, $Sender->RequestArgs);
      $TransientKey = GetValue(2, $Sender->RequestArgs);
      $Session = Gdn::Session();
      $FinalVote = 0;
      $Total = 0;
      if ($Session->IsValid() && $Session->ValidateTransientKey($TransientKey) && $CommentID > 0) {
         $CommentModel = new CommentModel();
         $OldUserVote = $CommentModel->GetUserScore($CommentID, $Session->UserID);
         $NewUserVote = $VoteType == 'voteup' ? 1 : -1;
         $FinalVote = intval($OldUserVote) + intval($NewUserVote);
         // Allow admins to vote unlimited.
         $AllowVote = $Session->CheckPermission('Garden.Moderation.Manage');
         // Only allow users to vote up or down by 1.
         if (!$AllowVote)
            $AllowVote = $FinalVote > -2 && $FinalVote < 2;

         if ($AllowVote)
            $Total = $CommentModel->SetUserScore($CommentID, $Session->UserID, $FinalVote);

         // Move the comment into or out of moderation.
         if (class_exists('LogModel')) {
            $Moderate = FALSE;

            if ($Total <= C('Plugins.Voting.ModThreshold1', -10)) {
               $LogOptions = array('GroupBy' => array('RecordID'));
               // Get the comment row.
               $Data = $CommentModel->GetID($CommentID, DATASET_TYPE_ARRAY);
               if ($Data) {
                  // Get the users that voted the comment down.
                  $OtherUserIDs = $CommentModel->SQL
                     ->Select('UserID')
                     ->From('UserComment')
                     ->Where('CommentID', $CommentID)
                     ->Where('Score <', 0)
                     ->Get()->ResultArray();
                  $OtherUserIDs = ConsolidateArrayValuesByKey($OtherUserIDs, 'UserID');
                  $LogOptions['OtherUserIDs'] = $OtherUserIDs;

                  // Add the comment to moderation.
                  if ($Total > C('Plugins.Voting.ModThreshold2', -20))
                     LogModel::Insert('Moderate', 'Comment', $Data, $LogOptions);
               }
               $Moderate = TRUE;
            }
            if ($Total <= C('Plugins.Voting.ModThreshold2', -20)) {
               // Remove the comment.
               $CommentModel->Delete($CommentID, array('Log' => 'Moderate'));

               $Sender->InformMessage(sprintf(T('The %s has been removed for moderation.'), T('comment')));
            } elseif ($Moderate) {
               $Sender->InformMessage(sprintf(T('The %s has been flagged for moderation.'), T('comment')));
            }
         }
      }
      $Sender->DeliveryType(DELIVERY_TYPE_BOOL);
      $Sender->SetJson('TotalScore', $Total);
      $Sender->SetJson('FinalVote', $FinalVote);
      $Sender->Render();
   }

   /**
    * Increment/decrement discussion scores
    */
   public function DiscussionController_VoteDiscussion_Create($Sender) {
//		if (!C('Plugins.Voting.Enabled'))
//			return;

      $DiscussionID = GetValue(0, $Sender->RequestArgs, 0);
      $TransientKey = GetValue(1, $Sender->RequestArgs);
      $VoteType = FALSE;
      if ($TransientKey == 'voteup' || $TransientKey == 'votedown') {
         $VoteType = $TransientKey;
         $TransientKey = GetValue(2, $Sender->RequestArgs);
      }
      $Session = Gdn::Session();
      $NewUserVote = 0;
      $Total = 0;
      if ($Session->IsValid() && $Session->ValidateTransientKey($TransientKey) && $DiscussionID > 0) {
         $DiscussionModel = new DiscussionModel();
         $OldUserVote = $DiscussionModel->GetUserScore($DiscussionID, $Session->UserID);

         if ($VoteType == 'voteup')
            $NewUserVote = 1;
         else if ($VoteType == 'votedown')
            $NewUserVote = -1;
         else
            $NewUserVote = $OldUserVote == 1 ? -1 : 1;

         $FinalVote = intval($OldUserVote) + intval($NewUserVote);
         // Allow admins to vote unlimited.
         $AllowVote = $Session->CheckPermission('Garden.Moderation.Manage');
         // Only allow users to vote up or down by 1.
         if (!$AllowVote)
            $AllowVote = $FinalVote > -2 && $FinalVote < 2;

         if ($AllowVote) {
            $Total = $DiscussionModel->SetUserScore($DiscussionID, $Session->UserID, $FinalVote);
         } else {
				$Discussion = $DiscussionModel->GetID($DiscussionID);
				$Total = GetValue('Score', $Discussion, 0);
				$FinalVote = $OldUserVote;
			}

         // Move the comment into or out of moderation.
         if (class_exists('LogModel')) {
            $Moderate = FALSE;

            if ($Total <= C('Plugins.Voting.ModThreshold1', -10)) {
               $LogOptions = array('GroupBy' => array('RecordID'));
               // Get the comment row.
               if (isset($Discussion))
                  $Data = (array)$Discussion;
               else
                  $Data = (array)$DiscussionModel->GetID($DiscussionID);
               if ($Data) {
                  // Get the users that voted the comment down.
                  $OtherUserIDs = $DiscussionModel->SQL
                     ->Select('UserID')
                     ->From('UserComment')
                     ->Where('CommentID', $DiscussionID)
                     ->Where('Score <', 0)
                     ->Get()->ResultArray();
                  $OtherUserIDs = ConsolidateArrayValuesByKey($OtherUserIDs, 'UserID');
                  $LogOptions['OtherUserIDs'] = $OtherUserIDs;

                  // Add the comment to moderation.
                  if ($Total > C('Plugins.Voting.ModThreshold2', -20))
                     LogModel::Insert('Moderate', 'Discussion', $Data, $LogOptions);
               }
               $Moderate = TRUE;
            }
            if ($Total <= C('Plugins.Voting.ModThreshold2', -20)) {
               // Remove the comment.
               $DiscussionModel->Delete($DiscussionID, array('Log' => 'Moderate'));

               $Sender->InformMessage(sprintf(T('The %s has been removed for moderation.'), T('discussion')));
            } elseif ($Moderate) {
               $Sender->InformMessage(sprintf(T('The %s has been flagged for moderation.'), T('discussion')));
            }
         }
      }
      $Sender->DeliveryType(DELIVERY_TYPE_BOOL);
      $Sender->SetJson('TotalScore', $Total);
      $Sender->SetJson('FinalVote', $FinalVote);
      $Sender->Render();
   }

   /**
    * Grab the score field whenever the discussions are queried.
    */
   public function DiscussionModel_AfterDiscussionSummaryQuery_Handler($Sender) {
//		if (!C('Plugins.Voting.Enabled'))
//			return;

      $Sender->SQL->Select('d.Score');
   }

   public function DiscussionsController_AfterDiscussionFilters_Handler($Sender) {
		echo '<li class="PopularDiscussions '.($Sender->RequestMethod == 'popular' ? ' Active' : '').'">'
			.Anchor(Sprite('SpPopularDiscussions').' '.T('Popular'), '/discussions/popular', 'PopularDiscussions')
		.'</li>';
   }

	/**
	 * Add the "Popular Questions" tab.
    */
	public function Base_BeforeDiscussionTabs_Handler($Sender) {
//		if (!C('Plugins.Voting.Enabled'))
//			return;

		echo '<li'.($Sender->RequestMethod == 'popular' ? ' class="Active"' : '').'>'
			.Anchor(T('Popular'), '/discussions/popular', 'PopularDiscussions TabLink')
		.'</li>';
	}

//   public function CategoriesController_BeforeDiscussionContent_Handler($Sender) {
//      $this->DiscussionsController_BeforeDiscussionContent_Handler($Sender);
//   }

   /**
    * Load popular discussions.
    */
   public function DiscussionsController_Popular_Create($Sender) {
//		if (!C('Plugins.Voting.Enabled'))
//			return;

      $Sender->AddModule('DiscussionFilterModule');
      $Sender->Title(T('Popular'));
      $Sender->Head->Title($Sender->Head->Title());

      $Offset = GetValue('0', $Sender->RequestArgs, '0');

      // Get rid of announcements from this view
      if ($Sender->Head) {
         $Sender->AddJsFile('discussions.js');
         $Sender->Head->AddRss($Sender->SelfUrl.'/feed.rss', $Sender->Head->Title());
      }
      if (!is_numeric($Offset) || $Offset < 0)
         $Offset = 0;

      // Add Modules
      $Sender->AddModule('NewDiscussionModule');
      $BookmarkedModule = new BookmarkedModule($Sender);
      $BookmarkedModule->GetData();
      $Sender->AddModule($BookmarkedModule);

      $Sender->SetData('Category', FALSE, TRUE);
      $Limit = C('Vanilla.Discussions.PerPage', 30);
      $DiscussionModel = new DiscussionModel();
      $CountDiscussions = $DiscussionModel->GetCount();
      $Sender->SetData('CountDiscussions', $CountDiscussions);
      $Sender->AnnounceData = FALSE;
		$Sender->SetData('Announcements', array(), TRUE);
      $DiscussionModel->SQL->OrderBy('d.CountViews', 'desc');
      $Sender->DiscussionData = $DiscussionModel->Get($Offset, $Limit);
      $Sender->SetData('Discussions', $Sender->DiscussionData, TRUE);
      $Sender->SetJson('Loading', $Offset . ' to ' . $Limit);

      // Build a pager.
      $PagerFactory = new Gdn_PagerFactory();
      $Sender->Pager = $PagerFactory->GetPager('Pager', $Sender);
      $Sender->Pager->ClientID = 'Pager';
      $Sender->Pager->Configure(
         $Offset,
         $Limit,
         $CountDiscussions,
         'discussions/popular/%1$s'
      );

      // Deliver json data if necessary
      if ($Sender->DeliveryType() != DELIVERY_TYPE_ALL) {
         $Sender->SetJson('LessRow', $Sender->Pager->ToString('less'));
         $Sender->SetJson('MoreRow', $Sender->Pager->ToString('more'));
         $Sender->View = 'discussions';
      }

      // Render the controller
      $Sender->View = 'index';
      $Sender->Render();
   }

	/**
	 * If turning off scoring, make the forum go back to the traditional "jump
	 * to what I last read" functionality.
	 */
   public function OnDisable() {
		SaveToConfig('Vanilla.Comments.AutoOffset', TRUE);
   }

   /**
   * Don't let the users access the category management screens.
   public function SettingsController_Render_Before(&$Sender) {
      if (strpos(strtolower($Sender->RequestMethod), 'categor') > 0)
         Redirect($Sender->Routes['DefaultPermission']);
   }
   */


	/**
	 * Insert the voting html on comments in a discussion.
	 */
	public function PostController_AfterCommentMeta_Handler($Sender) {
//		if (!C('Plugins.Voting.Enabled'))
//			return;

		$this->DiscussionController_AfterCommentMeta_Handler($Sender);
	}

	/**
	 * Add voting css to post controller.
	 */
	public function PostController_Render_Before($Sender) {
//		if (!C('Plugins.Voting.Enabled'))
//			return;

      $this->AddJsCss($Sender);
	}

   public function ProfileController_Render_Before($Sender) {
//		if (!C('Plugins.Voting.Enabled'))
//			return;

      $this->AddJsCss($Sender);
   }

	/**
	 * Add a field to the db for storing the "State" of a question.
	 */
   public function Setup() {
      // Add some fields to the database
      $Structure = Gdn::Structure();

      // "Unanswered" or "Answered"
      $Structure->Table('Discussion')
         ->Column('State', 'varchar(30)', TRUE)
         ->Set(FALSE, FALSE);

//    SaveToConfig('Vanilla.Categories.Use', FALSE);
//      SaveToConfig('Vanilla.Comments.AutoOffset', FALSE);
   }
}