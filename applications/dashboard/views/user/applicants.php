<?php if (!defined('APPLICATION')) exit();

$Session = Gdn::Session();
echo $this->Form->Open(array('action' => Url('/dashboard/user/applicants')));
?>
<h1><?php echo T('Manage Applicants'); ?></h1>
<?php
echo $this->Form->Errors();
if ($this->UserData->NumRows() == 0) {
   ?>
<p><?php echo T('There are currently no applicants.'); ?></p>
   <?php
} else {
   ?>
<table class="CheckColumn">
   <thead>
      <tr>
         <td><?php echo T('Action'); ?></td>
         <th class="Alt"><?php echo T('Applicant'); ?></th>
         <th><?php echo T('Options'); ?></th>
      </tr>
   </thead>
   <tbody>
   <?php
   foreach ($this->UserData->Format('Text')->Result() as $User) {
   ?>
      <tr>
         <td><?php echo $this->Form->CheckBox('Applicants[]', '', array('value' => $User->UserID)); ?></td>
         <td class="Alt">
            <?php
            printf(T('<strong>%1$s</strong> (%2$s) %3$s'), $User->Name, Gdn_Format::Email($User->Email), Gdn_Format::Date($User->DateInserted));
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