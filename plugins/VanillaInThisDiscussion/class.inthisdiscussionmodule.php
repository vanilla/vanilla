<?php
/**
 * InThisDiscussion module.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package InThisDiscussion
 */

/**
 * Renders a list of users who are taking part in a particular discussion.
 */
class InThisDiscussionModule extends Gdn_Module {

    protected $_UserData;

    public function __construct($Sender = '') {
        $this->_UserData = false;
        parent::__construct($Sender);
    }

    public function GetData($DiscussionID, $Limit = 50) {
        $SQL = Gdn::sql();
        $this->_UserData = $SQL
            ->select('u.UserID, u.Name, u.Photo')
            ->select('c.DateInserted', 'max', 'DateLastActive')
            ->from('User u')
            ->join('Comment c', 'u.UserID = c.InsertUserID')
            ->where('c.DiscussionID', $DiscussionID)
            ->groupBy('u.UserID, u.Name')
            ->orderBy('c.DateInserted', 'desc')
            ->limit($Limit)
            ->get();
    }

    public function AssetTarget() {
        return 'Panel';
    }

    public function ToString() {
        if ($this->_UserData->numRows() == 0) {
            return '';
        }

        $String = '';
        ob_start();
        ?>
        <div class="Box">
            <?php echo panelHeading(T('In this Discussion')); ?>
            <ul class="PanelInfo">
                <?php foreach ($this->_UserData->Result() as $User) :
?>
                    <li>
                        <?php
                        echo anchor(
                            wrap(Wrap(Gdn_Format::date($User->DateLastActive, 'html')), 'span', array('class' => 'Aside')).' '.
                            wrap(Wrap(val('Name', $User), 'span', array('class' => 'Username')), 'span'),
                            userUrl($User)
                        )
                        ?>
                    </li>
                <?php
endforeach; ?>
            </ul>
        </div>
        <?php
        $String = ob_get_contents();
        @ob_end_clean();
        return $String;
    }
}
