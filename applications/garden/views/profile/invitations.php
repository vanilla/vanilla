<?php if (!defined('APPLICATION')) exit(); ?>
<h2><?php echo Gdn::Translate('My Invitations'); ?></h2>
<?php
echo $this->Form->Open();
echo $this->Form->Errors();
if ($this->InvitationCount > 0) {
   echo '<div class="Info">'.sprintf(Gdn::Translate('You have %s invitations left for this month.'), $this->InvitationCount).'</div>';
}
if ($this->InvitationCount != 0) {
?>
   <div class="Info"><?php echo Gdn::Translate('Enter the email address of the person you would like to invite:'); ?></div>
<ul>
   <li>
   <?php
      echo $this->Form->Label('Email', 'Email');
      echo $this->Form->TextBox('Email');
      echo $this->Form->Button('Invite');
   ?></li>
</ul>
<?php
}

if ($this->InvitationData->NumRows() > 0) {
?>
<table class="AltRows">
   <thead>
      <tr>
         <th><?php echo Gdn::Translate('Invite Code'); ?></th>
         <th class="Alt"><?php echo Gdn::Translate('Sent To'); ?></th>
         <th><?php echo Gdn::Translate('On'); ?></th>
         <th class="Alt"><?php echo Gdn::Translate('Status'); ?></th>
      </tr>
   </thead>
   <tbody>
<?php
$Session = Gdn::Session();
$Alt = FALSE;
foreach ($this->InvitationData->Result('Text') as $Invitation) {
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
               .Anchor('Uninvite', '/profile/uninvite/'.$Invitation->InvitationID.'/'.$Session->TransientKey(), 'Uninvite')
               .' | '.Anchor('Send Again', '/profile/sendinvite/'.$Invitation->InvitationID.'/'.$Session->TransientKey(), 'SendAgain')
            .'</div>';
         }
      ?></td>
      <td><?php echo Format::Date($Invitation->DateInserted); ?></td>
      <td class="Alt"><?php
         if ($Invitation->AcceptedName == '') {
            echo Gdn::Translate('Pending');
         } else {
            echo Gdn::Translate('Accepted');
         }
            
      ?></td>
   </tr>
<?php } ?>
    </tbody>
</table>
<?php
}
echo $this->Form->Close();