<?php
/* Gdn_Controller $this */
$this->fireAs('dashboard')->fireEvent('render');
?>
<?php echo '<?xml version="1.0" encoding="utf-8"?>'; ?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo htmlspecialchars(Gdn::locale()->Locale); ?>">
<head>
    <?php $this->renderAsset('Head'); ?>
    <!-- Robots should not see the dashboard, but tell them not to index it just in case. -->
    <meta name="robots" content="noindex,nofollow"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body id="<?php echo htmlspecialchars($BodyIdentifier); ?>" class="<?php echo $this->CssClass; ?>">
<?php $this->renderAsset('Symbols');

// TODO: Pull this asset out elsewhere
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
        <?php
        foreach($this->data('meList', []) as $meItem) {
            echo anchor(
                t($meItem['text']).(val('isExternal', $meItem, true) ? ' '.dashboardSymbol('external-link') : ''),
                $meItem['url'],
                'list-group-item',
                ['target' => '_blank']
            );
        }
        ?>
    </div>
    <div class="card-footer">
        <?php echo anchor(t('Sign Out'), signOutUrl(), 'btn btn-secondary Leave'); ?>
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
        $dashboardNav = DashboardNavModule::getDashboardNav();
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
<!--            <nav class="nav nav-pills nav-icons">-->
<!--            --><?php
//                $sections = $dashboardNav->getSectionsInfo(true);
//                foreach ($dashboardNav->getSectionsInfo(true) as $section) { ?>
<!--                    <div class="nav-item">-->
<!--                        <a class="nav-link --><?php //echo val('active', $section); ?><!--" href="--><?php //echo val('url', $section); ?><!--">-->
<!--                            <div class="nav-link-heading">--><?php //echo val('title', $section); ?><!--</div>-->
<!--                        </a>-->
<!--                    </div>-->
<!--                --><?php
//                } ?>
<!--            </nav>-->
            <?php
            if (Gdn::session()->isValid()) {
                $photo = '<img src="'.userPhotoUrl($user).'">';
                echo '<div class="navbar-profile js-card-user">'.$photo.' <span class="icon icon-caret-down"></span></div>';
            }
            ?>
        </div>
    </div>
    <div class="main-row pusher" id="main-row">
        <div class="panel panel-left drawer">
            <div class="panel-content panel-nav">
                <div class="js-scroll-to-fixed">
                    <?php echo anchor($title.' '.dashboardSymbol('external-link', '', 'icon-16'), '/', 'title'); ?>
                    <?php echo $dashboardNav; ?>
                </div>
            </div>
        </div>
        <div class="main">
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
                    <?php
                    $this->fireAs('dashboard')->fireEvent('footerNav');
                    ?>
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
