<?php if (!defined('APPLICATION')) exit();

/**
 * TODO:
 * Verify that activity feed works properly with/without troll content. Added new event to core to get it working.
 * Add additional troll management options:
 *  - Disemvoweler
 *  - Troll Annoyances (slow page loading times, random over capacity errors, form submission failures, etc).
 *  - Trolls' posts don't bump the thread.
 *  - Admin page that shows all trolls and their punishments
 *  - Sink troll comments by default
 *  - Custom per-troll punishments
 *  - Speed optimizations (add troll state to user attributes, and return from troll specific functions quickly when possible).
 */

$PluginInfo['TrollManagement'] = array(
   'Name' => 'Troll Management',
   'Description' => "Allows you to mark users as trolls, making it so that only they can see their comments & discussions. They essentially become invisible to other users and eventually just leave because no-one responds to them.",
   'Version' => '1.2',
   'RequiredApplications' => array('Vanilla' => '2.0.17'),
   'MobileFriendly' => TRUE,
   'Author' => "Mark O'Sullivan",
   'AuthorEmail' => 'mark@vanillaforums.com',
   'AuthorUrl' => 'http://vanillaforums.com'
);

class TrollManagementPlugin extends Gdn_Plugin {

   public function Setup() {
      $this->Structure();
   }

   public function Structure() {
      Gdn::Structure()
         ->Table('User')
         ->Column('Troll', 'int', '0')
			->Column('Fingerprint', 'varchar(50)', null)
         ->Set();
	}

	/**
	 * Validates the current user's permissions & transientkey and then marks a user as a troll.
    * @param Gdn_Controller $Sender
	 */
	public function UserController_MarkTroll_Create($Sender, $UserID, $Troll = TRUE) {
      $Sender->Permission('Garden.Moderation.Manage');

		$TrollUserID = $UserID;

      // Validate the transient key && permissions
      // Make sure we are posting back.
      if (!$Sender->Request->IsAuthenticatedPostBack()) {
         throw PermissionException('Javascript');
      }

      $Trolls = C('Plugins.TrollManagement.Cache');
      if (!is_array($Trolls)) {
         $Trolls = array();
      }

      // Toggle troll value in DB
      if (in_array($TrollUserID, $Trolls)) {
         Gdn::SQL()->Update('User', array('Troll' => 0), array('UserID' => $TrollUserID))->Put();
         unset($Trolls[array_search($TrollUserID, $Trolls)]);
      } else {
         Gdn::SQL()->Update('User', array('Troll' => 1), array('UserID' => $TrollUserID))->Put();
         $Trolls[] = $TrollUserID;
      }

      SaveToConfig('Plugins.TrollManagement.Cache', $Trolls);

      $Sender->JsonTarget('', '', 'Refresh');
      $Sender->Render('Blank', 'Utility', 'Dashboard');
	}

	/**
	 * Fingerprint the user.
	 */
	public function Base_Render_Before($Sender) {
		// Don't do anything if the user isn't signed in.
		if (!Gdn::Session()->IsValid())
			return;

		$CookieValue = GetValue('__vnf', $_COOKIE, '');
		$FingerprintValue = GetValue('Fingerprint', Gdn::Session()->User, '');
		$Expires = time()+60*60*24*256; // Expire one year from now
		if ($CookieValue != '') {
			// Update the user fingerprint in the db if it does not match
			if ($FingerprintValue != $CookieValue)
				Gdn::SQL()->Update('User', array('Fingerprint' => $CookieValue), array('UserID' => Gdn::Session()->UserID))->Put();
		} else if ($FingerprintValue != '') {
			if ($FingerprintValue != $CookieValue)
				setcookie('__vnf', $FingerprintValue, $Expires, C('Garden.Cookie.Path', '/'), C('Garden.Cookie.Domain', ''));
		} else if ($CookieValue == '' && $FingerprintValue == '') {
			// Neither were set, so set them both.
			$FingerprintValue = uniqid();
			Gdn::SQL()->Update('User', array('Fingerprint' => $FingerprintValue), array('UserID' => Gdn::Session()->UserID))->Put();
			setcookie('__vnf', $FingerprintValue, $Expires, C('Garden.Cookie.Path', '/'), C('Garden.Cookie.Domain', ''));
		}
	}

	/**
	 * Display shared accounts on the user profiles for admins.
	 */
	public function ProfileController_Render_Before($Sender) {
		if (!Gdn::Session()->CheckPermission('Garden.Moderation.Manage'))
			return;

		if (!property_exists($Sender, 'User'))
			return;

		// Get the current user's fingerprint value
		$FingerprintValue = GetValue('Fingerprint', $Sender->User);
		if (!$FingerprintValue)
			return;

		// Display all accounts that share that fingerprint value
      $SharedFingerprintModule = new SharedFingerprintModule($Sender);
      $SharedFingerprintModule->GetData(GetValueR('User.UserID', $Sender), $FingerprintValue);
      $Sender->AddModule($SharedFingerprintModule);
	}

	/**
	 * Attach to the Discussion model and remove all records by trolls (unless the current user is a troll)
	 */
	public function DiscussionModel_AfterAddColumns_Handler($Sender) {
		$this->_CleanDataSet($Sender, 'Data');
	}

	/**
	 * Attach to the Comment model and remove all records by trolls (unless the current user is a troll)
	 */
	public function CommentModel_AfterGet_Handler($Sender) {
		$this->_CleanDataSet($Sender, 'Comments');
	}

	/**
	 * Attach to the Activity model and remove all records by trolls (unless the current user is a troll)
	 */
	public function ActivityModel_AfterGet_Handler($Sender) {
		$this->_CleanDataSet($Sender, 'Data');
	}

	/**
	 * Look in the sender eventarguments for a dataset to clean of troll content.
	 */
	private function _CleanDataSet($Sender, $DataEventArgument) {
		// Don't do anything if there are no trolls
		$Trolls = C('Plugins.TrollManagement.Cache');
		if (!is_array($Trolls))
			return;

		if (!array_key_exists($DataEventArgument, $Sender->EventArguments))
			return;

		// Examine the data, and remove any rows that belong to the trolls
		$Data = &$Sender->EventArguments[$DataEventArgument];
		$Result = &$Data->Result();
		$IsAdmin = Gdn::Session()->CheckPermission('Garden.Moderation.Manage');
		foreach ($Result as $Index => $Row) {
			if (in_array(GetValue('InsertUserID', $Row), $Trolls)) {
				if ($IsAdmin) {
					if (is_array($Row))
						$Result[$Index]['IsTroll'] = TRUE;
					else {
						$Row->IsTroll = TRUE;
						$Result[$Index] = $Row;
					}
				} else {
                    // Remove all troll posts for all non-trolls, but if current
                    // user is troll, remove other troll posts, but keep own.
                    if (Gdn::Session()->UserID != GetValue('InsertUserID', $Row)) {
					    unset($Result[$Index]);
                    }
                }
			}
		}
	}

	/**
	 * Identify troll discussions for admins.
	 */
	public function Base_BeforeDiscussionContent_Handler($Sender) {
      // $this->_ShowAdmin($Sender, 'Discussion');
	}

	/**
	 * Identify troll comments for admins.
	 */
	public function Base_BeforeCommentBody_Handler($Sender) {
      // Note that for DiscussionController, the IsTroll var is not being set.
		$this->_ShowAdmin($Sender, 'Object');
	}

   public function Base_BeforeDiscussionMeta_Handler($Sender, $Args) {
      $this->_ShowAdmin($Sender, 'Discussion', 'tag');
   }

	private function _ShowAdmin($Sender, $EventArgumentName, $Style = 'message') {
		// Don't do anything if there are no trolls
		$Trolls = C('Plugins.TrollManagement.Cache');
		if (!is_array($Trolls)) {
			return;
      }

      $Object = $Sender->EventArguments[$EventArgumentName];
      $ViewingUserID = Gdn::Session()->UserID;
      $InsertUserID = GetValue('InsertUserID', $Object);

		// Don't do anything if this is a troll
		if (in_array($ViewingUserID, $Trolls))
			return;

		// Don't do anything if the user is not admin (sanity check).
		if (!Gdn::Session()->CheckPermission('Garden.Moderation.Manage'))
			return;

		if (in_array($InsertUserID, $Trolls)) {
         if ($Style === 'message')
            echo '<div style="display: block; line-height: 1.2; padding: 8px; margin: -4px 0 8px; background: rgba(0, 0, 0, 0.05); color: #d00; font-size: 11px;">'.T('Troll.Content', '<b>Troll</b> <ul> <li>This user has been marked as a troll.</li> <li>Their content is only visible to moderators and the troll.</li> <li>This message does not appear for the troll.</li></ul>').'</div>';
         else
            echo '<span class="Tag Tag-Troll" title="'. T('This user has been marked as a troll.') .'">Troll</span>';
      }
	}

	/**
	 * Do not let troll comments bump discussions.
	 */
	public function CommentModel_BeforeUpdateCommentCount_Handler($Sender) {
		$Trolls = C('Plugins.TrollManagement.Cache');
		if (!is_array($Trolls))
			return;

		if (in_array(Gdn::Session()->UserID, $Trolls))
			$Sender->EventArguments['Discussion']['Sink'] = TRUE;
	}

	/**
	 * Auto-sink troll discussions.
	 */
	public function DiscussionModel_BeforeSaveDiscussion_Handler($Sender) {
		$Trolls = C('Plugins.TrollManagement.Cache');
		if (!is_array($Trolls))
			return;

		if (in_array(Gdn::Session()->UserID, $Trolls))
			$Sender->EventArguments['FormPostValues']['Sink'] = 1;
	}

   /**
    * If user has been marked as troll, write a message at the top of their
    * profile for only site admins to read.
    *
    * @param ProfileController $Sender
    */
   public function ProfileController_BeforeUserInfo_Handler($Sender) {
      if (Gdn::Session()->CheckPermission('Garden.Moderation.Manage')) {
         $UserID        = $Sender->User->UserID;
         $Trolls        = C('Plugins.TrollManagement.Cache');
         $ViewingUserID = Gdn::Session()->UserID;

         if (!is_array($Trolls)) {
            $Trolls = array();
         }

         if (is_array($Trolls)
         && $ViewingUserID != $UserID
         && in_array($UserID, $Trolls)) {
            echo '
            <div class="Hero Warning">
               <h3>'. T('Troll') .'</h3>
               '. T('This user has been marked as a troll.') .'
            </div>';
         }
      }
   }

   public function ProfileController_BeforeProfileOptions_Handler($Sender) {
      if (Gdn::Session()->CheckPermission('Garden.Moderation.Manage')) {
         $UserID        = $Sender->User->UserID;
         $Trolls        = C('Plugins.TrollManagement.Cache');

         if (!is_array($Trolls)) {
            $Trolls = array();
         }

         $troll = in_array($UserID, $Trolls) ? 1 : 0;

         $Sender->EventArguments['ProfileOptions']['TrollToggle'] = array(
            'Text' => T(($troll) ? 'Unmark as Troll' : 'Mark as Troll'),
            'Url' => '/user/marktroll?userid='.$UserID.'&troll='.(int)(!$troll),
            'CssClass' => 'Hijack Button-Troll'
         );
      }
   }
}
