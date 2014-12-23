<?php if (!defined('APPLICATION')) exit(); ?>
<h2 class="H"><?php echo $this->Data('Title'); ?></h2>
<?php
echo $this->Form->Open();
echo $this->Form->Errors();

$NumIgnoredUsers = sizeof($this->Data('IgnoreList'));
$Moderator = Gdn::Session()->CheckPermission('Garden.Users.Edit');
$Restricted = $this->Data('IgnoreRestricted');

?>
<?php if ($this->Data('ForceEditing', FALSE)): ?>
   <div class="Warning"><?php echo sprintf(T("You are viewing %s's ignore list"),$this->Data('ForceEditing')); ?></div>
<?php endif; ?>

<?php if ($NumIgnoredUsers): ?>
<table class="IgnoreList <?php echo ($Restricted) ? 'Restricted' : ''; ?>">
   <thead>
      <tr>
         <th colspan="2"><?php echo T('User'); ?></th>
         <th><?php echo T('Date Ignored'); ?></th>
         <th></th>
      </tr>
   </thead>
   <tbody>
      <?php foreach ($this->Data('IgnoreList') as $IgnoredUser): ?>

      <?php
         $DateIgnoredTime = strtotime($IgnoredUser['IgnoreDate']);
         if (!$DateIgnoredTime)
            $DateIgnored = 'Unknown';
         else
            $DateIgnored = Gdn_Format::Date($DateIgnoredTime);
      ?>
      <tr>
         <td class="IgnoreUserPhoto"><?php echo UserPhoto($IgnoredUser); ?></td>
         <td class="IgnoreUserName"><?php echo UserAnchor($IgnoredUser); ?></td>
         <td class="IgnoreUserDate"><?php echo $DateIgnored; ?></td>
         <td class="IgnoreUserAction"><?php echo (!$this->Data('ForceEditing') & !$Restricted) ? Anchor('Unignore', "/user/ignore/toggle/{$IgnoredUser['UserID']}/".Gdn_Format::Url($IgnoredUser['Name']), 'Ignore Button Popup') : ''; ?></td>
      </tr>
      <?php endforeach; ?>
   </tbody>
</table>
<?php endif; ?>

<?php
$NumIgnoreLimit = $this->Data('IgnoreLimit');
if ($NumIgnoreLimit != 'infinite'):
   $IgnoreListPercent = round(($NumIgnoredUsers / $NumIgnoreLimit) * 100, 2);
   echo Wrap(sprintf(T("Ignore list is <b>%s%%</b> full (<b>%d/%d</b>)."), $IgnoreListPercent, $NumIgnoredUsers, $NumIgnoreLimit), 'div');
else:
   echo Wrap(sprintf(T("<b>Unlimited</b> list, ignored <b>%d</b> %s"), $NumIgnoredUsers, Plural($NumIgnoredUsers, 'person','people')), 'div');
endif;
?>

<?php if ($Restricted): ?>
   <?php $ReferTo = ($this->Data('ForceEditing') ? sprintf(T("%s is"), $this->Data('ForceEditing')) : T("You are")); ?>
   <div class="Info">
      <?php echo sprintf(T("%s prohibited from using the ignore feature."),$ReferTo); ?>
      <?php if ($Moderator && $this->Data('ForceEditing', TRUE)):
         echo Anchor('Restore', "/user/ignorelist/allow/{$this->User->UserID}/".Gdn_Format::Url($this->User->Name), 'Ignore Hijack', array('id' => 'revoke'));
      endif; ?>
   </div>
<?php elseif ($Moderator && $this->Data('ForceEditing', TRUE)): ?>
   <div class="Warning"><?php echo sprintf(T("Revoke <b>%s</b>'s ignore list privileges?"), $this->Data('ForceEditing')); ?> <?php echo Anchor('Revoke', "/user/ignorelist/revoke/{$this->User->UserID}/".Gdn_Format::Url($this->User->Name), 'Ignore Hijack', array('id' => 'revoke')); ?></div>
<?php endif; ?>

<?php if (!$this->Data('ForceEditing') && !$Restricted): ?>
<ul>
   <li>
      <?php
         echo $this->Form->Label('Ignore Someone', 'AddIgnore');
         echo $this->Form->Textbox('AddIgnore');
      ?>
   </li>
</ul>
<?php echo $this->Form->Close('OK');

else:
   echo $this->Form->Close();
endif;
