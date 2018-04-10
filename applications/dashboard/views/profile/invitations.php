<?php if (!defined('APPLICATION')) exit(); ?>
    <h1 class="H"><?php echo t('Invitations'); ?></h1>
<?php
echo $this->Form->open();
echo $this->Form->errors();
if ($this->InvitationCount > 0) {
    echo '<div class="Info">'.sprintf(t('You have %s invitations left for this month.'), $this->InvitationCount).'</div>';
}
if ($this->InvitationCount != 0) {
    ?>
    <div class="InviteForm">
        <?php
        echo $this->Form->label('Enter the email address of the person you would like to invite:', 'Email');
        echo $this->Form->textBox('Email');
        echo ' ', $this->Form->button('Invite');
        ?>
    </div>
<?php
}

if ($this->InvitationData->numRows() > 0) {
    ?>
    <table class="Invitations DataTable">
        <thead>
        <tr>
            <th class=""><?php echo t('Sent To', 'Recipient'); ?></th>
            <th class="InviteMeta"><?php echo t('On'); ?></th>
            <th class="InviteMeta"><?php echo t('Status'); ?></th>
            <th class="InviteMeta"><?php echo t('Expires'); ?></th>
            <?php
            //         <th class="InviteCode InviteMeta"><?php echo t('Invitation Code', 'Code'); <!--</th>-->
            ?>
        </tr>
        </thead>
        <tbody>
        <?php
        $Session = Gdn::session();
        foreach ($this->InvitationData->format('Text')->result() as $Invitation) {
            ?>
            <tr class="js-invitation" data-id="<?php echo $Invitation->InvitationID ?>">
                <td><?php
                    if ($Invitation->AcceptedName == '') {
                        echo $Invitation->Email;
                        echo wrap(
                            anchor(t('Uninvite'), "/profile/uninvite/{$Invitation->InvitationID}", 'Uninvite Hijack')
                            .' | '.
                            anchor(t('Send Again'), "/profile/sendinvite/{$Invitation->InvitationID}", 'SendAgain Hijack')
                            , 'div');
                    } else {
                        $User = Gdn::userModel()->getID($Invitation->AcceptedUserID);
                        echo userAnchor($User);

                        echo wrap(
                            anchor(t('Remove'), "/profile/deleteinvitation/{$Invitation->InvitationID}", 'Delete Hijack')
                            , 'div');
                    }

                    if ($Invitation->AcceptedName == '') {

                    }
                    ?></td>
                <td><?php echo Gdn_Format::date($Invitation->DateInserted, 'html'); ?></td>
                <td><?php
                    if ($Invitation->AcceptedName == '') {
                        echo t('Pending');
                    } else {
                        echo t('Accepted');
                    }

                    ?></td>
                <td>
                    <?php
                    if (!$Invitation->DateExpires) {
                        echo t('Never expires', 'Never');
                    } else {
                        echo Gdn_Format::date($Invitation->DateExpires, 'html');
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
</div>
<?php
}
echo $this->Form->close();
