<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::session();
?>
<h1><?php echo t('Manage Applicants'); ?></h1>
<?php
echo $this->Form->open(array('action' => url('/dashboard/user/applicants')));
echo $this->Form->errors();
$NumApplicants = $this->UserData->numRows();

if ($NumApplicants == 0) : ?>
    <div class="padded"><?php echo t('There are currently no applicants.'); ?></div>
<?php else : ?>
    <?php
    $AppText = plural($NumApplicants, 'There is currently %s applicant.', 'There are currently %s applicants.');
    ?>
    <div class="padded italic"><?php echo sprintf($AppText, $NumApplicants); ?></div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th><?php echo t('Applicant'); ?></th>
                    <th><?php echo t('Reason'); ?></th>
                    <th><?php echo t('IP Address'); ?></th>
                    <th><?php echo t('Date'); ?></th>
                    <th class="options"><?php echo t('Options'); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php
            foreach ($this->UserData->result() as $User) :
                $this->EventArguments['User'] = $User;
                $this->EventArguments['ApplicantMeta'] = array();
                $this->fireEvent("ApplicantInfo"); ?>
                <tr class="ApplicantMeta">
                    <td style="border-bottom:none;">
                        <div class="user-info">
                            <div class="username"><?php echo htmlspecialchars($User->Name); ?></div>
                            <div class="info user-email"><?php echo anchor($User->Email, 'mailto:'.$User->Email); ?></div>
                        </div>
                    </td>
                    <td>
                        <?php
                        // Output a definition list if a plugin passed us ordered data.
                        if (count($this->EventArguments['ApplicantMeta'])) {
                            foreach ($this->EventArguments['ApplicantMeta'] as $label => $value) {
                                echo '<dt>'.htmlspecialchars($label).'</dt><dd>'.htmlspecialchars($value).'</dd>';
                            }
                        }
                        // Only make a blockquote if we got a reason.
                        if ($User->DiscoveryText) {
                            echo Gdn_Format::text($User->DiscoveryText);
                        }
                        // Opportunity for plugins to do arbitrary appending.
                        $this->fireEvent("AppendApplicantInfo");
                        ?>
                    </td>
                    <td style="border-bottom:none;"><?php echo anchor(Gdn_Format::text($User->InsertIPAddress), '/user/browse?Keywords='.Gdn_Format::text($User->InsertIPAddress)); ?></td>
                    <td style="border-bottom:none;"><?php echo Gdn_Format::date($User->DateInserted); ?></td>
                    <td style="border-bottom:none;">
                        <div class="btn-group">
                        <?php
                        echo anchor(dashboardSymbol('checkmark'), '/user/approve/'.$User->UserID, 'ApproveApplicant btn btn-icon', ['aria-label' => t('Approve')]);
                        echo anchor(dashboardSymbol('delete'), '/user/decline/'.$User->UserID.'/'.$Session->TransientKey(), 'DeclineApplicant btn btn-icon', ['aria-label' => t('Decline')]);
                        ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
endif;

echo $this->Form->close();
