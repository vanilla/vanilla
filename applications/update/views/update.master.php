<?php echo '<?xml version="1.0" encoding="utf-8"?>'; ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-ca">
<head>
   <?php $this->RenderAsset('Head'); ?>
</head>
<body id="<?php echo $BodyIdentifier; ?>" class="<?php echo $this->CssClass; ?>">
   <div id="Frame">
      <div id="Head">
         <div class="Header">
            <h1><?php echo T('VANILLA'); ?></h1>
            <span class="About"><?php echo T('BACKUP & UPGRADE'); ?> | <?php echo Anchor(T('Visit Site'),'/'); ?></span>
         </div>
      </div>
      <div id="Body">
         <?php
            ob_start();
            $this->RenderAsset('Panel');
            $Panel = ob_get_clean();
            if (strlen($Panel))
               echo "<div id=\"Panel\">{$Panel}</div>\n";
         ?>
         <div id="Content"><?php $this->RenderAsset('Content'); ?></div>
      </div>
      <div id="Foot">
			<?php
				$this->RenderAsset('Foot');
			?>
      </div>
   </div>
   <?php $this->FireEvent('AfterBody'); ?>
</body>
</html>