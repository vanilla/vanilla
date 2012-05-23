<?php echo '<?xml version="1.0" encoding="utf-8"?>'; ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-ca">
<head>
   <?php $this->RenderAsset('Head'); ?>
   <!-- Robots should not see the dashboard, but tell them not to index it just in case. -->
   <meta name="robots" content="noindex,nofollow" />
</head>
<body id="<?php echo $BodyIdentifier; ?>" class="<?php echo $this->CssClass; ?>">
   <div id="Frame">
      <div id="Head">
			<h1><?php echo Anchor(C('Garden.Title').' '.Wrap(T('Visit Site')), '/'); ?></h1>
         <div class="User">
            <?php
			      $Session = Gdn::Session();
					if ($Session->IsValid()) {
						$this->FireEvent('BeforeUserOptionsMenu');
						
						$Name = $Session->User->Name;
						$CountNotifications = $Session->User->CountNotifications;
						if (is_numeric($CountNotifications) && $CountNotifications > 0)
							$Name .= Wrap($CountNotifications);
							
						echo Anchor($Name, UserUrl($Session->User), 'Profile');
						echo Anchor(T('Sign Out'), SignOutUrl(), 'Leave');
					}
				?>
         </div>
      </div>
      <div id="Body">
         <div id="Panel">
            <?php
            $this->RenderAsset('Panel');
            ?>
         </div>
         <div id="Content"><?php $this->RenderAsset('Content'); ?></div>
      </div>
      <div id="Foot">
			<?php
				$this->RenderAsset('Foot');
				echo '<div class="Version">Version ', APPLICATION_VERSION, '</div>';
				echo Wrap(Anchor(Img('/applications/dashboard/design/images/logo_footer.png', array('alt' => 'Vanilla Forums')), C('Garden.VanillaUrl')), 'div');
			?>
		</div>
   </div>
	<?php $this->FireEvent('AfterBody'); ?>
</body>
</html>