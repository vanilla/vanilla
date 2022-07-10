<?php use Vanilla\Theme\BoxThemeShim;

if (!defined('APPLICATION')) exit();
$Session = Gdn::session();
if (Gdn::config('Garden.Profile.ShowAbout')) {
    require_once Gdn::controller()->fetchViewLocation('helper_functions', 'Profile', 'Dashboard');
    echo '<div class="About P widget-dontUseCssOnMe">';
        BoxThemeShim::startHeading();
        echo '<h2 class="H">' . t('About') . '</h2>';
        BoxThemeShim::endHeading();
        BoxThemeShim::startBox('userInfoBox About');
        $this->fireEvent('BeforeAboutList');
    ?>
        <dl class="About">
            <?php
            if ($this->User->Banned) {
                echo '<dd class="Value"><span class="Tag Tag-Banned">'.t('Banned').'</span></dd>';
            }
            ?>
            <dt class="Name"><?php echo t('Username'); ?></dt>
            <dd class="Name" itemprop="name"><?php echo htmlspecialchars($this->User->Name); ?></dd>

            <?php if ($Loc = val('Location', $this->User)): ?>
                <dt class="Location"><?php echo t('Location'); ?></dt>
                <dd class="Location"><?php echo htmlspecialchars($Loc); ?></dd>
            <?php endif; ?>

            <?php
            if ($this->User->Email && ($this->User->ShowEmail || $this->data('_canViewPersonalInfo'))) {
                echo '<dt class="Email">'.t('Email').'</dt>
         <dd class="Email" itemprop="email">'.Gdn_Format::email($this->User->Email).'</dd>';
            }
            ?>
            <dt class="Joined"><?php echo t('Joined'); ?></dt>
            <dd class="Joined"><?php echo Gdn_Format::date($this->User->DateFirstVisit, 'html'); ?></dd>
            <dt class="Visits"><?php echo t('Visits'); ?></dt>
            <dd class="Visits"><?php echo number_format($this->User->CountVisits); ?></dd>
            <dt class="LastActive"><?php echo t('Last Active'); ?></dt>
            <dd class="LastActive"><?php echo Gdn_Format::date($this->User->DateLastActive, 'html'); ?></dd>
            <dt class="Roles"><?php echo t('Roles'); ?></dt>
            <dd class="Roles"><?php
                if (Gdn::session()->checkPermission('Garden.Moderation.Manage')) {
                    echo userVerified($this->User).', ';
                }

                if (empty($this->Roles))
                    echo t('No Roles');
                else
                    echo htmlspecialchars(implode(', ', array_column($this->Roles, 'Name')));

                ?></dd>
            <?php if ($Points = valr('User.Points', $this, 0)) : // Only show positive point totals ?>
                <dt class="Points"><?php echo t('Points'); ?></dt>
                <dd class="Points"><?php echo number_format($Points); ?></dd>
            <?php
            endif;

            if ($Session->checkPermission('Garden.PersonalInfo.View')): ?>
                <dt class="IP"><?php echo t('Register IP'); ?></dt>
                <dd class="IP"><?php
                    $IP = iPAnchor($this->User->InsertIPAddress);
                    echo $IP ? $IP : t('n/a');
                    ?></dd>
                <dt class="IP"><?php echo t('Last IP'); ?></dt>
                <dd class="IP"><?php
                    $IP = iPAnchor($this->User->LastIPAddress);
                    echo $IP ? $IP : t('n/a');
                    ?></dd>
            <?php
            endif;

            if ($this->User->InviteUserID > 0) {
                $Inviter = Gdn::userModel()->getID($this->User->InviteUserID);
                if ($Inviter) {
                    echo '<dt class="Invited">'.t('Invited by').'</dt>
            <dd class="Invited">'.userAnchor($Inviter).'</dd>';
                }
            }
            $this->fireEvent('OnBasicInfo');
            ?>
        </dl>
        <?php  $this->fireEvent('AfterAboutList');  ?>
        <?php
            BoxThemeShim::endBox();
            echo '</div>';
        ?>
<?php
}
