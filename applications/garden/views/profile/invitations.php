<?php if (!defined('APPLICATION')) exit();
echo $this->Form->Open();
echo $this->Form->Errors();
echo '<ul>';
if ($this->InvitationCount > 0) {
   echo '<li><h3>'.sprintf(Gdn::Translate('You have %s invitations left for this month.'), $this->InvitationCount).'</h3></li>';
}
if ($this->InvitationCount != 0) {
?>
   <li><strong><?php echo Gdn::Translate('Enter the email address of the person you would like to invite:'); ?></strong></li>
   <li>
   <?php
      echo $this->Form->Label('Email', 'Email');
      echo $this->Form->TextBox('Email');
      echo $this->Form->Button('Invite');
   ?></li>
</ul>
<?php } ?>
<table>
   <thead>
      <tr>
         <th><?php echo Gdn::Translate('Invitation Code'); ?></th>
         <th class="Alt"><?php echo Gdn::Translate('Sent To'); ?></th>
         <th><?php echo Gdn::Translate('On'); ?></th>
         <th class="Alt"><?php echo Gdn::Translate('Status'); ?></th>
      </tr>
   </thead>
   <tbody>
<?php
$Session = Gdn::Session();
foreach ($this->InvitationData->Result('Text') as $Invitation) {
?>
   <tr>
      <td><?php echo $Invitation->Code; ?></td>
      <td class="Alt"><?php
         if ($Invitation->AcceptedName == '')
            echo $Invitation->Email;
         else
            echo Anchor($Invitation->AcceptedName, '/profile/'.$Invitation->AcceptedUserID);
            
      ?></td>
      <td><?php echo Format::Date($Invitation->DateInserted); ?></td>
      <td class="Alt"><?php
         if ($Invitation->AcceptedName == '') {
            echo Gdn::Translate('Pending');
            echo ' ('
               .Anchor('Uninvite', '/profile/uninvite/'.$Invitation->InvitationID.'/'.$Session->TransientKey())
               .', '.Anchor('Re-send Invitation', '/profile/sendinvite/'.$Invitation->InvitationID.'/'.$Session->TransientKey())
            .')';
         } else {
            echo Gdn::Translate('Accepted');
         }
            
      ?></td>
   </tr>
<?php } ?>
    </tbody>
</table>
<?php
echo $this->Form->Close();