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
        $SQL = Gdn::SQL();
        $this->_UserData = $SQL
            ->Select('u.UserID, u.Name, u.Photo')
            ->Select('c.DateInserted', 'max', 'DateLastActive')
            ->From('User u')
            ->Join('Comment c', 'u.UserID = c.InsertUserID')
            ->Where('c.DiscussionID', $DiscussionID)
            ->GroupBy('u.UserID, u.Name')
            ->OrderBy('c.DateInserted', 'desc')
            ->Limit($Limit)
            ->Get();
    }

    public function AssetTarget() {
        return 'Panel';
    }

    public function ToString() {
        if ($this->_UserData->NumRows() == 0) {
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
                        echo Anchor(
                            Wrap(Wrap(Gdn_Format::Date($User->DateLastActive, 'html')), 'span', array('class' => 'Aside')).' '.
                            Wrap(Wrap(GetValue('Name', $User), 'span', array('class' => 'Username')), 'span'),
                            UserUrl($User)
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
