<?php if (!defined('APPLICATION')) exit(); ?>
<h2 class="H"><?php echo T('Invitations'); ?></h2>
<?php
echo $this->Form->Open();
echo $this->Form->Errors();
if ($this->InvitationCount > 0) {
   echo '<div class="Info">'.sprintf(T('You have %s invitations left for this month.'), $this->InvitationCount).'</div>';
}
if ($this->InvitationCount != 0) {
?>
   <div class="Info"><?php echo T('Enter the email address of the person you would like to invite:'); ?></div>
<ul>
   <li>
   <?php
      echo $this->Form->Label('Email', 'Email');
      echo $this->Form->TextBox('Email');
      echo ' ', $this->Form->Button('Invite');
   ?></li>
</ul>
<?php
}

if ($this->InvitationData->NumRows() > 0) {
?>
<table class="AltRows">
   <thead>
      <tr>
         <th><?php echo T('Invitation Code'); ?></th>
         <th class="Alt"><?php echo T('Sent To'); ?></th>
         <th><?php echo T('On'); ?></th>
         <th class="Alt"><?php echo T('Status'); ?></th>
      </tr>
   </thead>
   <tbody>
<?php
$Session = Gdn::Session();
$Alt = FALSE;
foreach ($this->InvitationData->Format('Text')->Result() as $Invitation) {
   $Alt = $Alt == TRUE ? FALSE : TRUE;
?>
   <tr<?php echo ($Alt ? ' class="Alt"' : ''); ?>>
      <td><?php echo $Invitation->Code; ?></td>
      <td class="Alt"><?php
         if ($Invitation->AcceptedName == '')
            echo $Invitation->Email;
         else
            echo Anchor($Invitation->AcceptedName, '/profile/'.$Invitation->AcceptedUserID);
            
         if ($Invitation->AcceptedName == '') {
            echo '<div>'
               .Anchor(T('Uninvite'), '/profile/uninvite/'.$Invitation->InvitationID.'/'.$Session->TransientKey(), 'Uninvite')
               .' | '.Anchor(T('Send Again'), '/profile/sendinvite/'.$Invitation->InvitationID.'/'.$Session->TransientKey(), 'SendAgain')
            .'</div>';
         }
      ?></td>
      <td><?php echo Gdn_Format::Date($Invitation->DateInserted); ?></td>
      <td class="Alt"><?php
         if ($Invitation->AcceptedName == '') {
            echo T('Pending');
         } else {
            echo T('Accepted');
         }
            
      ?></td>
   </tr>
<?php } ?>
    </tbody>
</table>
<?php
}
echo $this->Form->Close();