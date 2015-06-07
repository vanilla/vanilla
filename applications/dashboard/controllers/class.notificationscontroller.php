<?php
/**
 * Creates and sends notifications to user.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

/**
 * Handle /notifications endpoint.
 */
class NotificationsController extends Gdn_Controller {

    /**
     * CSS, JS and module includes.
     */
    public function initialize() {
        $this->Head = new HeadModule($this);
        $this->addJsFile('jquery.js');
        $this->addJsFile('jquery.livequery.js');
        $this->addJsFile('jquery.form.js');
        $this->addJsFile('jquery.popup.js');
        $this->addJsFile('jquery.gardenhandleajaxform.js');
        $this->addJsFile('global.js');
        $this->addCssFile('style.css');
        $this->addCssFile('vanillicon.css', 'static');
        $this->addModule('GuestModule');
        parent::initialize();
    }

    /**
     * Adds inform messages to response for inclusion in pages dynamically.
     *
     * @since 2.0.18
     * @access public
     */
    public function inform() {
        $this->deliveryType(DELIVERY_TYPE_BOOL);
        $this->deliveryMethod(DELIVERY_METHOD_JSON);

        // Retrieve all notifications and inform them.
        NotificationsController::informNotifications($this);
        $this->fireEvent('BeforeInformNotifications');

        $this->render();
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
    public static function informNotifications($Sender) {
        $Session = Gdn::session();
        if (!$Session->isValid()) {
            return;
        }

        $ActivityModel = new ActivityModel();
        // Get five pending notifications.
        $Where = array(
            'NotifyUserID' => Gdn::session()->UserID,
            'Notified' => ActivityModel::SENT_PENDING);

        // If we're in the middle of a visit only get very recent notifications.
        $Where['DateUpdated >'] = Gdn_Format::toDateTime(strtotime('-5 minutes'));

        $Activities = $ActivityModel->getWhere($Where, 0, 5)->resultArray();

        $ActivityIDs = array_column($Activities, 'ActivityID');
        $ActivityModel->setNotified($ActivityIDs);

        $Sender->EventArguments['Activities'] = &$Activities;
        $Sender->fireEvent('InformNotifications');

        foreach ($Activities as $Activity) {
            if ($Activity['Photo']) {
                $UserPhoto = anchor(
                    img($Activity['Photo'], array('class' => 'ProfilePhotoMedium')),
                    $Activity['Url'],
                    'Icon'
                );
            } else {
                $UserPhoto = '';
            }
            $Excerpt = Gdn_Format::plainText($Activity['Story']);
            $ActivityClass = ' Activity-'.$Activity['ActivityType'];


            $Sender->informMessage(
                $UserPhoto
                .Wrap($Activity['Headline'], 'div', array('class' => 'Title'))
                .Wrap($Excerpt, 'div', array('class' => 'Excerpt')),
                'Dismissable AutoDismiss'.$ActivityClass.($UserPhoto == '' ? '' : ' HasIcon')
            );
        }
    }
}
