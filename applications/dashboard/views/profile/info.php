<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::session();
if (!is_array($this->Roles))
    $this->Roles = [];

$this->fireEvent('BeforeInfo');
if (Gdn::config('Garden.Profile.ShowAbout')) {
    ?>
    <div class="Info About">
        <h3><?php echo t('Basic Information'); ?></h3>
        <dl>
            <?php
            if ($this->User->Banned) {
                echo '<dt class="Value"><span class="Tag Tag-Banned">'.t('Banned').'</span></dt>';
            }

            if ($this->User->ShowEmail == 1 || $Session->checkPermission('Garden.Moderation.Manage')) {
                echo '<dt>'.t('Email').'</dt>
         <dd>'.Gdn_Format::email($this->User->Email).'</dd>';
            }
            ?>
            <dt class="Label Joined"><?php echo t('Joined'); ?>
            <dt>
            <dd class="Value Joined"><?php echo Gdn_Format::date($this->User->DateFirstVisit); ?></dd>
            <dt class="Label Visits"><?php echo t('Visits'); ?>
            <dt>
            <dd class="Value Visits"><?php echo $this->User->CountVisits; ?></dd>
            <dt class="Label LastActive"><?php echo t('Last Active'); ?>
            <dt>
            <dd class="Value LastActive"><?php echo Gdn_Format::date($this->User->DateLastActive); ?></dd>
            <dt class="Label Roles"><?php echo t('Roles'); ?>
            <dt>
            <dd class="Value Roles"><?php echo implode(', ', $this->Roles); ?></dd>
            <?php
            if ($this->User->InviteUserID > 0) {
                $Inviter = new stdClass();
                $Inviter->UserID = $this->User->InviteUserID;
                $Inviter->Name = $this->User->InviteName;
                echo '<dt class="Label InvitedBy">'.t('Invited by').'</dt>
         <dd class="Value InvitedBy">'.userAnchor($Inviter).'</dd>';
            }
            $this->fireEvent('OnBasicInfo');
            ?>
        </dl>
    </div>
<?php
}
$this->fireEvent('AfterInfo');
