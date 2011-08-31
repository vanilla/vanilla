<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/
/**
 * Notifications Controller
 * @package Dashboard
 */

/**
 * Creates and sends notifications to user.
 *
 * @since 2.0.18
 * @package Dashboard
 */
class NotificationsController extends Gdn_Controller {
   /**
    * CSS, JS and module includes.
    */
   public function Initialize() {
      $this->Head = new HeadModule($this);
      $this->AddJsFile('jquery.js');
      $this->AddJsFile('jquery.livequery.js');
      $this->AddJsFile('jquery.form.js');
      $this->AddJsFile('jquery.popup.js');
      $this->AddJsFile('jquery.gardenhandleajaxform.js');
      $this->AddJsFile('global.js');
      $this->AddCssFile('style.css');
      $this->AddModule('GuestModule');
      parent::Initialize();
   }   
   
   /**
    * Adds inform messages to response for inclusion in pages dynamically. 
    *
    * @since 2.0.18
    * @access public
    */
   public function Inform() {
      $this->DeliveryType(DELIVERY_TYPE_BOOL);
      $this->DeliveryMethod(DELIVERY_METHOD_JSON);
      
      // Retrieve all notifications and inform them.
      NotificationsController::InformNotifications($this);
      
      $this->Render();
   }
   
   /**
    * Grabs all new notifications and adds them to the sender's inform queue.
    *
    * This method gets called by dashboard's hooks file to display new
    * notifications on every pageload. 
    *
    * @since 2.0.18
    * @access public
    *
    * @param object $Sender The object calling this method.
    */
   public static function InformNotifications($Sender) {
      $Session = Gdn::Session();
      if (!$Session->IsValid())
         return;
		
      // Set the user's DateLastInform attribute to now. This value can be used
      // by addons to determine if their inform messages have already been sent.
      $InformLastActivityID = $Session->GetAttribute('Notifications.InformLastActivityID', 0);
      
      // Allow pluggability
      $Sender->EventArguments['InformLastActivityID'] = &$InformLastActivityID;
      $Sender->FireEvent('BeforeInformNotifications');
      
		// Retrieve default preferences
		$Preferences = array();
		$DefaultPreferences = C('Preferences.Popup', array());
		foreach ($DefaultPreferences as $Preference => $Val) {
			if ($Val)
				$Preferences[] = $Preference;
		}
		
//		$User = Gdn::Database()->SQL()->Select('Preferences')->From('User')->Where('UserID', $Session->UserID)->Get()->FirstRow();
//      if ($User) {
//         $PrefData = Gdn_Format::Unserialize($User->Preferences);
//			foreach ($PrefData as $Pref => $Val) {
//				if (substr($Pref, 0, 6) == 'Popup.') {
//					$Pref = substr($Pref, 6);
//					if ($Val) {
//						$Preferences[] = $Pref;
//					} else {
//						if (in_array($Pref, $Preferences))
//							unset($Preferences[array_search($Pref, $Preferences)]);
//					}
//				}
//			}
//		}
		
//		if (count($Preferences) > 0) {
			// Grab the activity type ids for the desired notification prefs.
			$ActivityTypeIDs = array();
//         $ActivityTypes = array();
			$Data = Gdn::Database()->SQL()->GetWhere('ActivityType', array('Notify' => TRUE))->ResultArray(); //  ->WhereIn('Name', $Preferences)->Get();
			foreach ($Data as $ActivityType) {
            if (Gdn::Session()->GetPreference("Popup.{$ActivityType['Name']}", C("Preferences.Popup.{$ActivityType['Name']}", TRUE))) {
               $ActivityTypeIDs[] = $ActivityType['ActivityTypeID'];
//               $ActivityTypes[] = $ActivityType['Name'];
            }
			}
			
			if (count($ActivityTypeIDs) > 0) {
				// Retrieve new notifications
				$ActivityModel = new ActivityModel();
				$NotificationData = $ActivityModel->GetNotificationsSince($Session->UserID, $InformLastActivityID, $ActivityTypeIDs);
				$InformLastActivityID = -1;
      
				// Add (no more than 5) notifications to the inform stack
				foreach ($NotificationData->Result() as $Notification) {
					// Make sure the user wants to be notified of this
   //					if (!in_array($Notification->ActivityType, $Preferences)) {
   //                  continue;
   //               }
               
               $UserPhoto = UserPhoto(UserBuilder($Notification, 'Activity'), 'Icon');

               $ActivityType = explode(' ', $Notification->ActivityType);
               $ActivityType = $ActivityType[0];
               $Excerpt = $Notification->Story;
               if (in_array($ActivityType, array('WallComment', 'AboutUpdate')))
                  $Excerpt = Gdn_Format::Display($Excerpt);

               // Inform the user of new messages
               $Sender->InformMessage(
                  $UserPhoto
                  .Wrap(Gdn_Format::ActivityHeadline($Notification, $Session->UserID), 'div', array('class' => 'Title'))
                  .Wrap($Excerpt, 'div', array('class' => 'Excerpt')),
                  'Dismissable AutoDismiss'.($UserPhoto == '' ? '' : ' HasIcon')
               );
               // Assign the most recent activity id
               if ($InformLastActivityID == -1)
                  $InformLastActivityID = $Notification->ActivityID;
				}
			}
//		}
		if ($InformLastActivityID > 0)
			Gdn::UserModel()->SaveAttribute($Session->UserID, 'Notifications.InformLastActivityID', $InformLastActivityID);
   }
}