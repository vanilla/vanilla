<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

class NotificationsController extends Gdn_Controller {
   
   public function Initialize() {
      $this->Head = new HeadModule($this);
      $this->AddJsFile('jquery.js');
      $this->AddJsFile('jquery.livequery.js');
      $this->AddJsFile('jquery.form.js');
      $this->AddJsFile('jquery.popup.js');
      $this->AddJsFile('jquery.gardenhandleajaxform.js');
      $this->AddJsFile('global.js');
      $this->AddCssFile('style.css');
      $GuestModule = new GuestModule($this);
      $this->AddModule($GuestModule);
      parent::Initialize();
   }   
   
	/**
	 * Adds inform messages to response for inclusion in pages dynamically.
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
	 * Note: This method gets called by dashboard's hooks file to display new
	 * notifications on every pageload.
	 */
	public static function InformNotifications($Sender) {
      $Session = Gdn::Session();
		if (!$Session->IsValid())
			return;
		
		// Set the user's DateLastInform attribute to now. This value can be used
		// by addons to determine if their inform messages have already been sent.
		$DateLastInform = $Session->GetAttribute('Notifications.DateLastInform', Gdn_Format::ToDateTime());
		Gdn::UserModel()->SaveAttribute($Session->UserID, 'Notifications.DateLastInform', Gdn_Format::ToDateTime());
		
		// Allow pluggability
		$Sender->EventArguments['DateLastInform'] = &$DateLastInform;
		$Sender->FireEvent('BeforeInformNotifications');
		
		// Retrieve new notifications
      $ActivityModel = new ActivityModel();
      $NotificationData = $ActivityModel->GetNotificationsSince($Session->UserID, $DateLastInform);
		
		// Add notifications to the inform stack
		foreach ($NotificationData->Result() as $Notification) {
			$UserPhoto = UserPhoto(UserBuilder($Notification, 'Activity'), 'Icon');
		   // Inform the user of new messages
         $Sender->InformMessage(
				$UserPhoto
				.Wrap(Gdn_Format::ActivityHeadline($Notification, $Session->UserID), 'div', array('class' => 'Title'))
				.Wrap(Gdn_Format::Display($Notification->Story), 'div', array('class' => 'Excerpt')),
            'Dismissable'.($UserPhoto == '' ? '' : ' HasIcon')
         );
		}
	}
   
}