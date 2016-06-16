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
    <div class="card-block user-block">
        <div class="user-image-wrap">
            <?php echo userPhoto($user); ?>
        </div>
        <div class="user-info">
            <div class="username">
                <?php echo userAnchor($user); ?>
            </div>
            <div class="info">
                <?php echo $roleTitles; ?>
            </div>
            <a class="btn btn-userblock" href="<?php echo url(userUrl($user)); ?>">
                <?php echo t('My Profile'); ?> <span class="icon icon-external-link"></span>
            </a>
        </div>
    </div>
    <div class="list-group list-group-flush">
        <a class="list-group-item" href="#"><?php echo t('Take The Tour'); ?><span class="icon icon-external-link"></span></a>
        <a class="list-group-item" href="#"><?php echo t('Help & Tutorials'); ?><span class="icon icon-external-link"></span></a>
        <a class="list-group-item" href="#"><?php echo t('Customer Support'); ?><span class="icon icon-external-link"></span></a>
    </div>
    <div class="card-footer">
        <?php echo anchor(t('Sign Out'), SignOutUrl(), 'btn btn-secondary Leave'); ?>
    </div>
</div>
<?php
Gdn_Theme::assetEnd();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo htmlspecialchars(Gdn::locale()->Locale); ?>">
<head>
    <?php $this->renderAsset('Head'); ?>
    <!-- Robots should not see the dashboard, but tell them not to index it just in case. -->
    <meta name="robots" content="noindex,nofollow"/>
</head>
<body id="<?php echo htmlspecialchars($BodyIdentifier); ?>" class="<?php echo htmlspecialchars($this->CssClass); ?>">
<?php $this->renderAsset('Symbols'); ?>
<div class="navbar">
    <div class="navbar-brand">
        <?php $title = c('Garden.Title'); ?>
<!--        --><?php //if ($logo = c('Garden.Logo', false)) { ?>
<!--        <div class="navbar-image logo">--><?php //echo img(Gdn_Upload::url($logo), array('alt' => $title));?><!--</div>-->
<!--        --><?php //} else { ?>
<!--        <div class="title">--><?php //echo anchor($title, '/'); ?><!--</div>-->
<!--        --><?php //} ?>
        <div class="navbar-image logo"><?php echo anchor('Vanilla Forums', c('Garden.VanillaUrl'), 'vanilla-logo vanilla-logo-white'); ?></div>
<!--        --><?php //echo anchor($title, '/', 'title'); ?>
        <?php echo anchor(t('Visit Site').' <span class="icon icon-external-link"></span>', '/', 'btn btn-navbar'); ?>
    </div>
    <?php $dashboardNav = new DashboardNavModule(); ?>
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
<div class="container-fluid body">
    <div class="row main-row">
        <div class="col-sm-0 col-md-8 col-lg-6 col-xl-5 panel panel-left">
            <div class="panel-content panel-nav">
                <div class="js-scroll-to-fixed">
                <?php echo anchor($title, '/', 'title'); ?>
                <?php echo $dashboardNav; ?>
                </div>
            </div>
        </div>
        <div class="col-sm-24 col-md-16 col-lg-12 col-xl-14 main">
            <div class="content">
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
        <div class="col-sm-0 col-md-0 col-lg-6 col-xl-5 panel panel-help panel-right">
            <div class="panel-content">
                <div class="js-scroll-to-fixed">
                    <?php $this->renderAsset('Help'); ?>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="hidden js-dashboard-user-dropdown">
    <?php $this->renderAsset('DashboardUserDropDown'); ?>
</div>
<?php $this->fireEvent('AfterBody'); ?>
</body>
</html>
