<?php if (!defined('APPLICATION')) exit();

$Session = Gdn::Session();
echo $this->Form->Open(array('action' => Url('/garden/user/applicants')));
?>
<h1><?php echo Gdn::Translate('Manage Applicants'); ?></h1>
<?php
echo $this->Form->Errors();
if ($this->UserData->NumRows() == 0) {
   ?>
<p><?php echo Gdn::Translate('There are currently no applicants.'); ?></p>
   <?php
} else {
   ?>
<table class="CheckColumn">
   <thead>
      <tr>
         <td><?php echo Gdn::Translate('Action'); ?></td>
         <th class="Alt"><?php echo Gdn::Translate('Applicant'); ?></th>
         <th><?php echo Gdn::Translate('Options'); ?></th>
      </tr>
   </thead>
   <tbody>
   <?php
   foreach ($this->UserData->Result('Text') as $User) {
   ?>
      <tr>
         <td><?php echo $this->Form->CheckBox('Applicants[]', '', array('value' => $User->UserID)); ?></td>
         <td class="Alt">
            <?php
            printf(Gdn::Translate('<strong>%1$s</strong> (%2$s) %3$s'), $User->Name, Format::Email($User->Email), Format::Date($User->DateInserted));
            echo '<blockquote>'.$User->DiscoveryText.'</blockquote>';
         ?></td>
         <td><?php
         echo Anchor('Approve', '/user/approve/'.$User->UserID.'/'.$Session->TransientKey())
            .', '.Anchor('Decline', '/user/decline/'.$User->UserID.'/'.$Session->TransientKey());
         ?></td>
      </tr>
   <?php } ?>
   </tbody>
</table>
   <?php
   echo $this->Form->Button('Approve', array('Name' => $this->Form->EscapeFieldName('Submit'), 'class' => 'SmallButton'));
   echo $this->Form->Button('Decline', array('Name' => $this->Form->EscapeFieldName('Submit'), 'class' => 'SmallButton'));
}
echo $this->Form->Close();