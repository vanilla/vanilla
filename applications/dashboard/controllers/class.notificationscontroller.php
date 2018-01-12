<?php
/**
 * Creates and sends notifications to user.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
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
     * @param Gdn_Controller $sender The object calling this method.
     */
    public static function informNotifications($sender) {
        $session = Gdn::session();
        if (!$session->isValid()) {
            return;
        }

        $activityModel = new ActivityModel();
        // Get five pending notifications.
        $where = [
            'NotifyUserID' => Gdn::session()->UserID,
            'Notified' => ActivityModel::SENT_PENDING];

        // If we're in the middle of a visit only get very recent notifications.
        $where['DateUpdated >'] = Gdn_Format::toDateTime(strtotime('-5 minutes'));

        $activities = $activityModel->getWhere($where, '', '', 5, 0)->resultArray();

        $activityIDs = array_column($activities, 'ActivityID');
        $activityModel->setNotified($activityIDs);

        $sender->EventArguments['Activities'] = &$activities;
        $sender->fireEvent('InformNotifications');

        foreach ($activities as $activity) {
            if ($activity['Photo']) {
                $userPhoto = anchor(
                    img($activity['Photo'], ['class' => 'ProfilePhotoMedium']),
                    $activity['Url'],
                    'Icon'
                );
            } else {
                $userPhoto = '';
            }
            $excerpt = htmlspecialchars(Gdn_Format::plainText($activity['Story']));
            $activityClass = ' Activity-'.$activity['ActivityType'];


            $sender->informMessage(
                $userPhoto
                .wrap($activity['Headline'], 'div', ['class' => 'Title'])
                .wrap($excerpt, 'div', ['class' => 'Excerpt']),
                'Dismissable AutoDismiss'.$activityClass.($userPhoto == '' ? '' : ' HasIcon')
            );
        }
    }
}
