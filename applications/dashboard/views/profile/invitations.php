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
   <div class="InviteForm">
      <?php
      echo $this->Form->Label('Enter the email address of the person you would like to invite:', 'Email');
      echo $this->Form->TextBox('Email');
      echo ' ', $this->Form->Button('Invite');
      ?>
   </div>
<?php
}

if ($this->InvitationData->NumRows() > 0) {
?>
<table class="AltRows Invitations DataTable">
   <thead>
      <tr>
         <th class=""><?php echo T('Sent To', 'Recipient'); ?></th>
         <th class="Alt InviteMeta"><?php echo T('On'); ?></th>
         <th class="InviteMeta"><?php echo T('Status'); ?></th>
         <th class="Alt InviteMeta"><?php echo T('Expires'); ?></th>
         <?php
//         <th class="InviteCode Alt InviteMeta"><?php echo T('Invitation Code', 'Code'); <!--</th>-->
         ?>
      </tr>
   </thead>
   <tbody>
<?php
$Session = Gdn::Session();
$Alt = FALSE;
foreach ($this->InvitationData->Format('Text')->Result() as $Invitation) {
   $Alt = $Alt == TRUE ? FALSE : TRUE;
?>
   <tr class="js-invitation" data-id="<?php echo $Invitation->InvitationID ?>">
      <td class="Alt"><?php
         if ($Invitation->AcceptedName == '') {
            echo $Invitation->Email;
            echo Wrap(
               Anchor(T('Uninvite'), "/profile/uninvite/{$Invitation->InvitationID}", 'Uninvite Hijack')
               .' | '.
               Anchor(T('Send Again'), "/profile/sendinvite/{$Invitation->InvitationID}", 'SendAgain Hijack')
               , 'div');
         } else {
            $User = Gdn::UserModel()->GetID($Invitation->AcceptedUserID);
            echo UserAnchor($User);

            echo Wrap(
               Anchor(T('Remove'), "/profile/deleteinvitation/{$Invitation->InvitationID}", 'Delete Hijack')
               , 'div');
         }

         if ($Invitation->AcceptedName == '') {

         }
      ?></td>
      <td><?php echo Gdn_Format::Date($Invitation->DateInserted, 'html'); ?></td>
      <td class="Alt"><?php
         if ($Invitation->AcceptedName == '') {
            echo T('Pending');
         } else {
            echo T('Accepted');
         }
            
      ?></td>
      <td>
         <?php
         if (!$Invitation->DateExpires) {
            echo T('Never expires', 'Never');
         } else {
            echo Gdn_Format::Date($Invitation->DateExpires, 'html');
         }
         ?>
      </td>
      <?php
//      <td><?php echo $Invitation->Code; <!--</td>-->
      ?>
   </tr>
<?php } ?>
    </tbody>
</table>
<?php
}
echo $this->Form->Close();