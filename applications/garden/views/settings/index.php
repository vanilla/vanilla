<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php printf(Gdn::Translate('Welcome, %s!'), Gdn::Session()->User->Name); ?></h1>
<?php
$this->RenderAsset('Messages');
/*
<p>Here's some stuff you might want to do:</p>
<ul class="BigList">
   <li class="one"><a href="../settings/registration">Define how users register for your forum</a></li>
   <li class="two"><a href="../settings/plugins">Manage your plugins</a></li>
   <li class="three"><a href="../categories/manage">Organize your discussion categories</a></li>
   <li class="four"><a href="../profile">Customize your profile</a></li>
   <li class="five"><a href="../post/discussion">Start your first discussion</a></li>
</ul>
*/
?>
<h3><?php echo Gdn::Translate("What's the Buzz?"); ?></h3>
<dl>
<?php
$Count = count($this->BuzzData);
$i = 0;
foreach ($this->BuzzData as $Name => $Value) {
   $i++;
   echo '<dt>'.$Value.'</dt>
   <dd'.($Count == $i ? ' class="Last"' : '').'>'.$Name.'</dd>';
}
?>
</dl>

<h3><?php echo Gdn::Translate('Recently Active Users'); ?></h3>
<ul class="DataList RecentUsers">
   <?php
   $i = 0;
   foreach ($this->ActiveUserData as $User) {
      $i++;
      echo '<li'.($i == 5 ? ' class="Last"' : '').'>',
         UserPhoto($User->Name, $User->Photo),
         UserAnchor($User->Name),
         sprintf(Gdn::Translate('Last active %s'), Format::Date($User->DateLastActive)),
      '</li>';
   }
   ?>
</ul>