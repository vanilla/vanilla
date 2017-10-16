<?php
/* Gdn_Controller $this */
$this->fireAs('dashboard')->fireEvent('render');
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars(Gdn::locale()->Locale); ?>">
<head>
    <?php $this->renderAsset('Head'); ?>
    <?php // Robots should not see the dashboard, but tell them not to index it just in case. ?>
    <meta name="robots" content="noindex,nofollow">
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
    <?php
    $userBlock = new MediaItemModule(val('Name', $user), userUrl($user), $roleTitles, '', ['class' => 'card-block']);
    $userBlock->setView('media-sm')
        ->setImage(userPhotoUrl($user))
        ->addButton(t('My Profile').' '.dashboardSymbol('external-link', 'icon-11 icon-text'), url(userUrl($user)),
        ['class' => 'btn btn-sm-rounded btn-secondary padded-top']);
    echo $userBlock;
    ?>
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
    <header class="navbar js-navbar">
        <button class="js-drawer-toggle drawer-toggle btn btn-link" type="button">
            &#9776;
        </button>
        <div class="navbar-brand">
            <?php $title = c('Garden.Title'); ?>
            <div class="navbar-image logo"><?php echo wrap('Vanilla Forums', 'span', ['class' => 'vanilla-logo vanilla-logo-white']); ?></div>
            <?php echo anchor(t('Visit Site').' '.dashboardSymbol('external-link', 'icon-11'), '/', 'btn btn-navbar padded-left'); ?>
        </div>
        <?php
        /** @var DashboardNavModule $dashboardNav */
        $dashboardNav = DashboardNavModule::getDashboardNav();
        ?>
        <nav class="nav nav-pills">
            <?php
            foreach ($dashboardNav->getSectionsInfo() as $section) { ?>
                <div class="nav-item">
                    <a class="nav-link js-save-pref-dashboard-landing-page <?php echo val('active', $section); ?>" href="<?php echo url(val('url', $section)); ?>" data-section="<?php echo val('section', $section) ?>">
                        <div class="nav-link-heading"><?php echo val('title', $section); ?></div>
                        <div class="nav-link-description"><?php echo val('description', $section, '&nbsp;'); ?></div>
                    </a>
                </div>
            <?php } ?>
        </nav>
        <div class="navbar-memenu">
            <?php
            if (Gdn::session()->isValid()) {
                $photo = '<img src="'.userPhotoUrl($user).'">';
                $attr = [
                    'class' => 'navbar-profile js-drop',
                    'data-content-id' => 'cardUserContent',
                    'id' => 'cardUserTrigger'
                ];
                echo wrap($photo.' <span class="icon icon-caret-down"></span>', 'div', $attr);
            }
            ?>
        </div>
    </header>
    <div class="main-row pusher<?php echo $this->data('IsWidePage') ? ' main-row-wide' : ''; ?>" id="main-row">
        <div class="panel panel-left js-drawer">
            <div class="panel-nav panel-content-wrapper">
                <div class="js-fluid-fixed panel-content">
                    <?php echo anchor($title.' '.dashboardSymbol('external-link', 'icon-16'), '/', 'title'); ?>
                    <div id="panel-nav" class="js-panel-nav">
                        <?php echo $dashboardNav; ?>
                    </div>
                    <aside class="drawer-only">
                        <?php $this->renderAsset('DashboardUserDropDown'); ?>
                    </aside>
                </div>
            </div>
        </div>
        <div class="panel panel-help panel-right">
            <div class="panel-content-wrapper">
                <div id="fixed-help" class="js-fluid-fixed panel-content">
                    <?php $this->renderAsset('Help'); ?>
                </div>
            </div>
        </div>
        <div class="main">
            <section role="main" class="content">
                <?php $this->renderAsset('Content'); ?>
            </section>
            <footer class="footer">
                <?php $this->renderAsset('Foot'); ?>
                <div class="footer-logo logo-wrap">
                    <?php echo anchor('Vanilla Forums', c('Garden.VanillaUrl'), 'vanilla-logo'); ?>
                    <div class="footer-logo-powered">
                        <div class="footer-logo-powered-text">— <?php echo t('%s Powered', 'Powered'); ?> —</div>
                    </div>
                </div>
                <div class="footer-nav nav">
                    <?php
                    $showVanillaVersion = true;
                    $this->EventArguments['ShowVanillaVersion'] = &$showVanillaVersion;
                    $this->fireAs('dashboard')->fireEvent('footerNav');
                    if ($showVanillaVersion) : ?>
                    <div class="vanilla-version footer-nav-item nav-item"><?php echo t('Version').' '.APPLICATION_VERSION ?></div>
                    <?php endif; ?>
                </div>
            </footer>
        </div>
    </div>
</div>
<aside id="cardUserContent" aria-hidden="true" class="hidden">
    <?php $this->renderAsset('DashboardUserDropDown'); ?>
</aside>
<?php $this->fireEvent('AfterBody'); ?>
</body>
</html>
