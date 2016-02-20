<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::session();
?>
<h1><?php echo t('Manage Applicants'); ?></h1>
<?php
echo $this->Form->open(array('action' => url('/dashboard/user/applicants')));
echo $this->Form->errors();
$NumApplicants = $this->UserData->numRows();

if ($NumApplicants == 0) : ?>
    <div class="Info"><?php echo t('There are currently no applicants.'); ?></div>
<?php else : ?>
    <?php
    $AppText = plural($NumApplicants, 'There is currently %s applicant.', 'There are currently %s applicants.');
    ?>
    <div class="Info"><?php echo sprintf($AppText, $NumApplicants); ?></div>
    <table>
        <thead>
            <tr>
                <th width="130px"><?php echo t('Action'); ?></th>
                <th><?php echo t('Applicant'); ?></th>
                <th><?php echo t('Email'); ?></th>
                <th><?php echo t('IP Address'); ?></th>
                <th><?php echo t('Date'); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php
        foreach ($this->UserData->result() as $User) :
            $this->EventArguments['User'] = $User;
            $this->EventArguments['ApplicantMeta'] = array();
            $this->fireEvent("ApplicantInfo"); ?>
            <tr class="ApplicantMeta">
                <td style="border-bottom:none;"><?php
                    echo anchor(t('Approve'), '/user/approve/'.$User->UserID, 'SmallButton ApproveApplicant');
                    echo anchor(t('Decline'), '/user/decline/'.$User->UserID, 'CancelButton DeclineApplicant');
                    ?>
                </td>
                <td style="border-bottom:none;"><strong><?php echo htmlspecialchars($User->Name); ?></strong></td>
                <td style="border-bottom:none;"><?php echo anchor($User->Email, 'mailto:'.$User->Email); ?></td>
                <td style="border-bottom:none;"><?php echo anchor(Gdn_Format::text($User->InsertIPAddress), '/user/browse?Keywords='.Gdn_Format::text($User->InsertIPAddress)); ?></td>
                <td style="border-bottom:none;"><?php echo Gdn_Format::date($User->DateInserted); ?></td>
            </tr>
            <tr>
                <td></td>
                <td colspan="4">
                <?php
                    // Output a definition list if a plugin passed us ordered data.
                    if (count($this->EventArguments['ApplicantMeta'])) {
                        foreach ($this->EventArguments['ApplicantMeta'] as $label => $value) {
                            echo '<dt>'.htmlspecialchars($label).'</dt><dd>'.htmlspecialchars($value).'</dd>';
                        }
                    }
                    // Only make a blockquote if we got a reason.
                    if ($User->DiscoveryText) {
                        echo '<blockquote>'.wrap(t('Reason for joining', 'Reason: '), 'em').Gdn_Format::text($User->DiscoveryText).'</blockquote>';
                    }
                    // Opportunity for plugins to do arbitrary appending.
                    $this->fireEvent("AppendApplicantInfo");
                    ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php
endif;

echo $this->Form->close();
