<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php printf(Gdn::Translate('Welcome, %s!'), Gdn::Session()->User->Name); ?></h1>
<?php
$this->RenderAsset('Messages');
/*
<p><?php echo Gdn::Translate("Here's some stuff you might want to do:"); ?></p>
<ul class="BigList">
   <li class="one"><?php echo Anchor('Define how users register for your forum', '/settings/registration'); ?></li>
   <li class="two"><?php echo Anchor('Manage your plugins', '/settings/plugins'); ?></li>
   <li class="three"><?php echo Anchor('Organize your discussion categories', '/categories/manage'); ?></li>
   <li class="four"><?php echo Anchor('Customize your profile', '/profile'); ?></li>
   <li class="five"><?php echo Anchor('Start your first discussion', '/post/discussion'); ?></li>
</ul>

<p>Here's some stuff you might want to do:</p>
<ul class="BigList">
   <li class="one"><a href="../settings/registration">Define how users register for your forum</a></li>
   <li class="two"><a href="../settings/plugins">Manage your plugins</a></li>
   <li class="three"><a href="../categories/manage">Organize your discussion categories</a></li>
   <li class="four"><a href="../profile">Customize your profile</a></li>
   <li class="five"><a href="../post/discussion">Start your first discussion</a></li>
</ul>


*/


