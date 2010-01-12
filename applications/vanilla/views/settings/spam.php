<?php if (!defined('APPLICATION')) exit();
$Count = array(1, 2, 3, 4, 5, 10, 15, 20, 25, 30);
$Time = array(30, 60, 90, 120, 240);
$Lock = array(30, 60, 90, 120, 240);
$SpamCount = ArrayCombine($Count, $Count);
$SpamTime = ArrayCombine($Time, $Time);
$SpamLock = ArrayCombine(array(60, 120, 180, 240, 300, 600), array(1, 2, 3, 4, 5, 10));
echo $this->Form->Open();
echo $this->Form->Errors();
?>
<h1><?php echo Gdn::Translate('Manage Spam'); ?></h1>
<div class="Info"><?php echo Gdn::Translate('Prevent spam on your forum by limiting the number of discussions &amp; comments that users can post within a given period of time.'); ?></div>
<table class="AltColumns">
   <thead>
      <tr>
         <th><?php echo Gdn::Translate('Only Allow Each User To Post'); ?></th>
         <th class="Alt"><?php echo Gdn::Translate('Within'); ?></th>
         <th><?php echo Gdn::Translate('Or Spamblock For'); ?></th>
      </tr>
   </thead>
   <tbody>
      <tr>
         <td>
            <?php echo $this->Form->DropDown('Vanilla.Discussion.SpamCount', $SpamCount); ?>
            <?php echo Gdn::Translate('discussion(s)'); ?>
         </td>
         <td class="Alt">
            <?php echo $this->Form->DropDown('Vanilla.Discussion.SpamTime', $SpamTime); ?>
            <?php echo Gdn::Translate('seconds'); ?>
         </td>
         <td>
            <?php echo $this->Form->DropDown('Vanilla.Discussion.SpamLock', $SpamLock); ?>
            <?php echo Gdn::Translate('minute(s)'); ?>
         </td>
      </tr>
      <tr>
         <td>
            <?php echo $this->Form->DropDown('Vanilla.Comment.SpamCount', $SpamCount); ?>
            <?php echo Gdn::Translate('comment(s)'); ?>
         </td>
         <td class="Alt">
            <?php echo $this->Form->DropDown('Vanilla.Comment.SpamTime', $SpamTime); ?>
            <?php echo Gdn::Translate('seconds'); ?>
         </td>
         <td>
            <?php echo $this->Form->DropDown('Vanilla.Comment.SpamLock', $SpamLock); ?>
            <?php echo Gdn::Translate('minute(s)'); ?>
         </td>
      </tr>
   </tbody>
</table>
<br /><ul>
   <li>
      <div class="Info"><?php echo Translate("It is a good idea to keep the maximum number of characters allowed in a comment down to a reasonable size."); ?></div>
   </li>
   <li>
      <?php
         echo $this->Form->Label('Max Comment Length', 'Vanilla.Comment.MaxLength');
         echo $this->Form->TextBox('Vanilla.Comment.MaxLength', array('class' => 'InputBox SmallInput'));
      ?>
   </li>
</ul>
<?php echo $this->Form->Close('Save');
