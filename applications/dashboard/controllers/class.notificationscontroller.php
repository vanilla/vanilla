<?php if (!defined('APPLICATION')) exit();

/**
 * Creates and sends notifications to user.
 * 
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.0
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