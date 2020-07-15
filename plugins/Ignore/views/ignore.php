<?php if (!defined('APPLICATION')) exit(); ?>
<h2 class="H"><?php echo $this->data('Title'); ?></h2>
<?php
echo $this->Form->open();
echo $this->Form->errors();

$NumIgnoredUsers = sizeof($this->data('IgnoreList'));
$Moderator = Gdn::session()->checkPermission('Garden.Users.Edit');
$Restricted = $this->data('IgnoreRestricted');

?>
<?php if ($this->data('ForceEditing', FALSE)): ?>
   <div class="Warning"><?php echo sprintf(t('You are viewing %s\'s ignore list'),$this->data('ForceEditing')); ?></div>
<?php endif; ?>

<?php if ($NumIgnoredUsers): ?>
<table class="IgnoreList <?php echo ($Restricted) ? 'Restricted' : ''; ?>">
   <thead>
      <tr>
         <th colspan="2"><?php echo t('User'); ?></th>
         <th><?php echo t('Date Ignored'); ?></th>
         <th></th>
      </tr>
   </thead>
   <tbody>
      <?php foreach ($this->data('IgnoreList') as $IgnoredUser): ?>

      <?php
         $DateIgnoredTime = strtotime($IgnoredUser['IgnoreDate']);
         if (!$DateIgnoredTime)
            $DateIgnored = 'Unknown';
         else
            $DateIgnored = Gdn_Format::date($DateIgnoredTime);
      ?>
      <tr>
         <td class="IgnoreUserPhoto"><?php echo userPhoto($IgnoredUser); ?></td>
         <td class="IgnoreUserName"><?php echo userAnchor($IgnoredUser); ?></td>
         <td class="IgnoreUserDate"><?php echo $DateIgnored; ?></td>
         <td class="IgnoreUserAction"><?php echo (!$this->data('ForceEditing') & !$Restricted) ? anchor('Unignore', "/user/ignore/toggle/{$IgnoredUser['UserID']}/".Gdn_Format::url($IgnoredUser['Name']), 'Ignore Button Popup') : ''; ?></td>
      </tr>
      <?php endforeach; ?>
   </tbody>
</table>
<?php endif; ?>

<?php
$NumIgnoreLimit = $this->data('IgnoreLimit');
if ($NumIgnoreLimit != 'infinite'):
   $IgnoreListPercent = round(($NumIgnoredUsers / $NumIgnoreLimit) * 100, 2);
   echo wrap(sprintf(t('IgnoreListMeter'), $IgnoreListPercent, $NumIgnoredUsers, $NumIgnoreLimit), 'div');
else:
   echo wrap(sprintf(t('IgnoreListUnlimited'), $NumIgnoredUsers, plural($NumIgnoredUsers, 'person','people')), 'div');
endif;
?>

<?php if ($Restricted): ?>
   <div class="Info">
      <?php if ($this->data('ForceEditing')):
         echo sprintf(t('%s is prohibited from using the ignore feature.'));
            else: echo t('You are prohibited from using the ignore feature.');
      endif; ?>
      <?php if ($Moderator && $this->data('ForceEditing', TRUE)):
         echo anchor('Restore', "/user/ignorelist/allow/{$this->User->UserID}/".Gdn_Format::url($this->User->Name), 'Ignore Hijack', ['id' => 'revoke']);
      endif; ?>
   </div>
<?php elseif ($Moderator && $this->data('ForceEditing', TRUE)): ?>
   <div class="Warning"><?php echo sprintf(t('IgnoreListRevoke'), $this->data('ForceEditing')); ?> <?php echo anchor('Revoke', "/user/ignorelist/revoke/{$this->User->UserID}/".Gdn_Format::url($this->User->Name), 'Ignore Hijack', ['id' => 'revoke']); ?></div>
<?php endif; ?>

<?php if (!$this->data('ForceEditing') && !$Restricted): ?>
<ul>
   <li>
      <?php
         echo $this->Form->label('Ignore Someone', 'AddIgnore');
         echo $this->Form->textbox('AddIgnore');
      ?>
   </li>
</ul>
<?php echo $this->Form->close('OK');

else:
   echo $this->Form->close();
endif;
