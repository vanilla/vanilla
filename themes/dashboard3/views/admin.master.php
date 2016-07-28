<?php echo '<?xml version="1.0" encoding="utf-8"?>';
$dropdown = new DropdownModule('my-dropdown');
$dropdown->setView('dropdown-twbs');
$dropdown->setTrigger('A New Name')
    ->addLink('Link 1', '#') // automatically creates key: item1
    ->addDivider() // automatically creates key: item2
    ->addLink('Link 2', '#', 'link2', 'danger') // creates item with key: link2
    ->addLink('Link 3', '#') // automatically creates key: item3
    ->addLink('Link 4', '#') // automatically creates key: item4
    ->addGroup('', 'group1') // creates group with no header
    ->addGroup('Group 3', 'group3') // creates group with header: 'Group 3', empty so will not display
    ->addGroup('Group 2', 'group2') // creates group with header: 'Group 2'
    ->addLink('Link 5', '#', '', '', array('before', 'link2'), array('badge' => '4')) // automatically creates key: item5. Inserts before Link 2
    ->addLink('Link 6', '#') // automatically creates key: item6
    ->addLink('Link 7', '#') // automatically creates key: item7
    ->addLink('Link 8', '#', 'group2.link8', '', array(), array('icon' => 'flame')) // adds to Group 2
    ->addLink('Link 9', '#', 'group1.link9') // adds to Group 1
    ->addLink('Link 10', '#', 'group1.link10'); // adds to Group 1
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo htmlspecialchars(Gdn::locale()->Locale); ?>">
<head>
    <?php $this->renderAsset('Head'); ?>
    <!-- Robots should not see the dashboard, but tell them not to index it just in case. -->
    <meta name="robots" content="noindex,nofollow"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<?php
$BodyClass = htmlspecialchars($this->CssClass);
$Sections = Gdn_Theme::section(null, 'get');
if (is_array($Sections)) {
    foreach ($Sections as $Section) {
        $BodyClass .= ' Section-'.$Section;
    }
}
?>
<body id="<?php echo htmlspecialchars($BodyIdentifier); ?>" class="<?php echo $BodyClass; ?>">
<?php $this->renderAsset('Symbols');
Gdn_Theme::assetBegin('DashboardUserDropDown');
$user = Gdn::session()->User;
$rm = new RoleModel();
$roles = $rm->getByUserID(val('UserID', $user))->resultArray();
$roleTitlesArray = [];
foreach($roles as $role) {
    $roleTitlesArray[] = val('Name', $role);
}
$roleTitles = implode(', ', $roleTitlesArray);

/** var UserController $user */
?>
<div class="card card-user">
    <div class="card-block media-sm">
        <div class="media-sm-image-wrap">
            <?php echo userPhoto($user); ?>
        </div>
        <div class="media-sm-content">
            <div class="media-sm-title username">
                <?php echo userAnchor($user); ?>
            </div>
            <div class="media-sm-info user-roles">
                <?php echo $roleTitles; ?>
            </div>
            <a class="btn btn-media-sm" href="<?php echo url(userUrl($user)); ?>">
                <?php echo t('My Profile').' '.dashboardSymbol('external-link'); ?>
            </a>
        </div>
    </div>
    <div class="list-group list-group-flush">
        <a class="list-group-item" href="#"><?php echo t('Take The Tour').' '.dashboardSymbol('external-link'); ?></a>
        <a class="list-group-item" href="<?php echo url('/dashboard/settings/gettingstarted') ?>">
            <?php echo t('Help & Tutorials').' '.dashboardSymbol('external-link'); ?>
        </a>
        <a class="list-group-item" href="#"><?php echo t('Customer Support').' '.dashboardSymbol('external-link'); ?></a>
    </div>
    <div class="card-footer">
        <?php echo anchor(t('Sign Out'), SignOutUrl(), 'btn btn-secondary Leave'); ?>
    </div>
</div>
<?php
Gdn_Theme::assetEnd();
?>
<div class="main-container">
    <div class="navbar">
        <button class="js-panel-left-toggle panel-left-toggle btn btn-link" type="button">
            &#9776;
        </button>
        <div class="navbar-brand">
            <?php $title = c('Garden.Title'); ?>
    <!--        --><?php //if ($logo = c('Garden.Logo', false)) { ?>
    <!--        <div class="navbar-image logo">--><?php //echo img(Gdn_Upload::url($logo), array('alt' => $title));?><!--</div>-->
    <!--        --><?php //} else { ?>
    <!--        <div class="title">--><?php //echo anchor($title, '/'); ?><!--</div>-->
    <!--        --><?php //} ?>
            <div class="navbar-image logo"><?php echo anchor('Vanilla Forums', c('Garden.VanillaUrl'), 'vanilla-logo vanilla-logo-white'); ?></div>
            <?php echo anchor(t('Visit Site').' '.dashboardSymbol('external-link'), '/', 'btn btn-navbar'); ?>
        </div>
        <?php
        /** @var DashboardNavModule $dashboardNav */
        $dashboardNav = $this->Assets['Panel']['DashboardNavModule'];
        $dashboardNav->addGroupToGlobals('', 'globals', 'globals');
        $dashboardNav->addLinkToGlobals(t('Sign Out'), SignOutUrl(), 'globals.sign-out');
        ?>
        <nav class="nav nav-pills">
            <?php
            foreach ($dashboardNav->getSectionsInfo() as $section) { ?>
                <div class="nav-item">
                    <a class="nav-link <?php echo val('active', $section); ?>" href="<?php echo val('url', $section); ?>">
                        <div class="nav-link-heading"><?php echo val('title', $section); ?></div>
                        <div class="nav-link-description"><?php echo val('description', $section, '&nbsp;'); ?></div>
                    </a>
                </div>
            <?php } ?>
        </nav>
        <div class="navbar-memenu">
            <nav class="nav nav-pills nav-icons">
            <?php
                foreach ($dashboardNav->getSectionsInfo(true) as $section) { ?>
                    <div class="nav-item">
                        <a class="nav-link <?php echo val('active', $section); ?>" href="<?php echo val('url', $section); ?>">
                            <div class="nav-link-heading"><?php echo val('title', $section); ?></div>
                        </a>
                    </div>
                <?php
                } ?>
            </nav>
            <?php
            if (Gdn::session()->isValid()) {
                $this->fireEvent('BeforeUserOptionsMenu');
                $photo = '<img src="'.userPhotoUrl($user).'">';
                $CountNotifications = Gdn::session()->User->CountNotifications;
                if (is_numeric($CountNotifications) && $CountNotifications > 0) {
                    $photo .= wrap($CountNotifications);
                }
                echo '<div class="navbar-profile js-card-user">'.$photo.' <span class="icon icon-caret-down"></span></div>';
    //            echo anchor(t('Sign Out'), SignOutUrl(), 'btn btn-navbar Leave');
            }
            ?>
        </div>
    </div>
    <div class="main-row pusher" id="main-row">
        <div class="panel panel-left drawer">
            <div class="panel-content panel-nav">
                <div class="js-scroll-to-fixed">
                    <?php echo anchor($title.' '.dashboardSymbol('external-link'), '/', 'title icon-text'); ?>
                    <?php echo $dashboardNav; ?>
                </div>
            </div>
        </div>
        <div class="main">
            <div class="content">
<!--                --><?php //echo $dropdown; ?>
    <!--            <a href="/user/edit/53" class="js-ajax-modal btn btn-icon" aria-label="Edit"><svg class="icon  icon-edit" viewBox="0 0 17 17"><use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#edit"></use></svg></a>-->
                <?php $this->renderAsset('Content'); ?>
            </div>
            <div class="footer">
                <?php $this->renderAsset('Foot'); ?>
                <div class="footer-logo logo-wrap">
                    <?php echo anchor('Vanilla Forums', c('Garden.VanillaUrl'), 'vanilla-logo'); ?>
                    <div class="footer-logo-powered">
                        <div class="footer-logo-powered-text">— <?php echo t('%s Powered', 'Powered'); ?> —</div>
                    </div>
                </div>
                <div class="footer-nav nav">
                    <div class="vanilla-version footer-nav-item nav-item"><?php echo t('Version').' '.APPLICATION_VERSION ?></div>
                </div>
            </div>
        </div>
        <div class="panel panel-help panel-right">
            <?php if (!inSection('DashboardHome')) { ?>
            <div class="panel-content">
                <div class="js-scroll-to-fixed">
                    <?php $this->renderAsset('Help'); ?>
                </div>
            </div>
            <?php } ?>
        </div>
    </div>
</div>
<div class="hidden js-dashboard-user-dropdown">
    <?php $this->renderAsset('DashboardUserDropDown'); ?>
</div>
<?php $this->fireEvent('AfterBody'); ?>
</body>
</html>
