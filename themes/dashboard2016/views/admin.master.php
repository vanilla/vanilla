<?php echo '<?xml version="1.0" encoding="utf-8"?>'; ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo htmlspecialchars(Gdn::locale()->Locale); ?>">
<head>
    <?php $this->renderAsset('Head'); ?>
    <!-- Robots should not see the dashboard, but tell them not to index it just in case. -->
    <meta name="robots" content="noindex,nofollow"/>
</head>
<body id="<?php echo htmlspecialchars($BodyIdentifier); ?>" class="<?php echo htmlspecialchars($this->CssClass); ?>">
<div id="Frame">
    <div id="Head">
        <h1><?php echo anchor(c('Garden.Title').' '.Wrap(t('Visit Site')), '/'); ?></h1>

        <div class="User">
            <?php
            if (Gdn::session()->isValid()) {
                $this->fireEvent('BeforeUserOptionsMenu');

                $Name = Gdn::session()->User->Name;
                $CountNotifications = Gdn::session()->User->CountNotifications;
                if (is_numeric($CountNotifications) && $CountNotifications > 0) {
                    $Name .= wrap($CountNotifications);
                }

                echo anchor($Name, userUrl(Gdn::session()->User), 'Profile');
                echo anchor(t('Sign Out'), SignOutUrl(), 'Leave');
            }
            ?>
        </div>
    </div>
    <div id="Body">
        <div id="Panel">
            <?php
            $this->renderAsset('Panel');
            ?>
        </div>
        <div id="Content"><?php $this->renderAsset('Content'); ?></div>
    </div>
    <div id="Foot">
        <?php
        $this->renderAsset('Foot');
        echo '<div class="Version">Version ', APPLICATION_VERSION, '</div>';
        echo wrap(anchor(img('/applications/dashboard/design/images/logo_footer.png', array('alt' => 'Vanilla Forums')), c('Garden.VanillaUrl')), 'div');
        ?>
    </div>
</div>
<?php $this->fireEvent('AfterBody'); ?>
</body>
</html>
