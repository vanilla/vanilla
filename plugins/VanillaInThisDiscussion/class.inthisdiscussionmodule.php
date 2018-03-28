<?php
/**
 * InThisDiscussion module.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package InThisDiscussion
 */

/**
 * Renders a list of users who are taking part in a particular discussion.
 */
class InThisDiscussionModule extends Gdn_Module {

    /** @var array */
    protected $_UserData;

    /**
     *
     *
     * @param string $sender
     */
    public function __construct($sender = '') {
        $this->_UserData = false;
        parent::__construct($sender);
    }

    /**
     *
     *
     * @param $discussionID
     * @param int $limit
     * @throws Exception
     */
    public function getData($discussionID, $limit = 50) {
        $sQL = Gdn::sql();
        $this->_UserData = $sQL
            ->select('u.UserID, u.Name, u.Photo')
            ->select('c.DateInserted', 'max', 'DateLastActive')
            ->from('User u')
            ->join('Comment c', 'u.UserID = c.InsertUserID')
            ->where('c.DiscussionID', $discussionID)
            ->groupBy('u.UserID, u.Name, u.Photo')
            ->orderBy('c.DateInserted', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Default render location.
     *
     * @return string
     */
    public function assetTarget() {
        return 'Panel';
    }

    /**
     * Build HTML.
     *
     * @return string HTML.
     */
    public function toString() {
        if ($this->_UserData->numRows() == 0) {
            return '';
        }

        $string = '';
        ob_start();
        ?>
        <div class="Box BoxInThisDiscussion">
            <?php echo panelHeading(t('In this Discussion')); ?>
            <ul class="PanelInfo PanelInThisDiscussion">
                <?php foreach ($this->_UserData->result() as $user) :
?>
                    <li>
                        <?php
                        echo anchor(
                            wrap(wrap(Gdn_Format::date($user->DateLastActive, 'html')), 'span', ['class' => 'Aside']).' '.
                            wrap(wrap(val('Name', $user), 'span', ['class' => 'Username']), 'span'),
                            userUrl($user)
                        )
                        ?>
                    </li>
                <?php
endforeach; ?>
            </ul>
        </div>
        <?php
        $string = ob_get_clean();
        return $string;
    }
}
