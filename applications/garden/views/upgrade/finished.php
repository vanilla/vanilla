<?php if (!defined('APPLICATION')) exit(); ?>

<div class="Title">
   <h1><span>Vanilla</span></h1>
   <h2><?php echo T("Import Complete!"); ?></h2>
</div>
<form>
   <ul>
      <li class="Last">
         <p>Up next you should take the following steps:</p>
         <p>1. <?php echo Anchor('Organize your categories', '/vanilla/categories/manage'); ?> because Vanilla 2's default categories are still laying around...</p>
         <p>2. Because Vanilla 2 handles roles and permissions in a new (and awesome) way, we can't automatically transfer all of your permission settings. So, you'll need to <?php echo Anchor('re-assign all of the permissions for all of your roles', '/garden/role'); ?>.</p>
      </li>
   </ul>
</form>