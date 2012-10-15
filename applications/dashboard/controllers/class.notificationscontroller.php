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
      $this->FireEvent('BeforeInformNotifications');
      
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
    * @param Gdn_Controller $Sender The object calling this method.
    */
   public static function InformNotifications($Sender) {
      $Session = Gdn::Session();
      if (!$Session->IsValid())
         return;
      
      $ActivityModel = new ActivityModel();
      // Get five pending notifications.
      $Where = array(
          'NotifyUserID' => Gdn::Session()->UserID, 
          'Notified' => ActivityModel::SENT_PENDING);
      
      // If we're in the middle of a visit only get very recent notifications.
      $Where['DateUpdated >'] = Gdn_Format::ToDateTime(strtotime('-5 minutes'));
      
      $Activities = $ActivityModel->GetWhere($Where, 0, 5)->ResultArray();
      
      $ActivityIDs = ConsolidateArrayValuesByKey($Activities, 'ActivityID');
      $ActivityModel->SetNotified($ActivityIDs);
      
      foreach ($Activities as $Activity) {
         if ($Activity['Photo'])
            $UserPhoto = Anchor(
               Img($Activity['Photo'], array('class' => 'ProfilePhotoMedium')),
               $Activity['Url'],
               'Icon');
         else
            $UserPhoto = '';
         $Excerpt = Gdn_Format::Display($Activity['Story']);
         $ActivityClass = ' Activity-'.$Activity['ActivityType'];
         
         
         $Sender->InformMessage(
            $UserPhoto
            .Wrap($Activity['Headline'], 'div', array('class' => 'Title'))
            .Wrap($Excerpt, 'div', array('class' => 'Excerpt')),
            'Dismissable AutoDismiss'.$ActivityClass.($UserPhoto == '' ? '' : ' HasIcon')
         );
      }
   }
}