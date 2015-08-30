<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::session();
?>
    <div class="Help Aside">
        <?php
        echo wrap(t('Need More Help?'), 'h2');
        echo '<ul>';
        echo wrap(Anchor(t("Video tutorial on user registration"), 'settings/tutorials/user-registration'), 'li');
        echo '</ul>';
        ?>
    </div>
    <h1><?php echo t('Manage Applicants'); ?></h1>
<?php
echo $this->Form->open(array('action' => url('/dashboard/user/applicants')));
echo $this->Form->errors();
$NumApplicants = $this->UserData->numRows();
if ($NumApplicants == 0) { ?>
    <div class="Info"><?php echo t('There are currently no applicants.'); ?></div>
<?php } else { ?>
    <?php
    $AppText = plural($NumApplicants, 'There is currently %s applicant', 'There are currently %s applicants');
    ?>
    <div class="Info"><?php echo sprintf($AppText, $NumApplicants); ?></div>
    <table class="CheckColumn">
        <thead>
        <tr>
            <td><?php echo t('Action'); ?></td>
            <th class="Alt"><?php echo t('Applicant'); ?></th>
            <th><?php echo t('Options'); ?></th>
        </tr>
        </thead>
        <tbody>
        <?php
        foreach ($this->UserData->Format('Text')->result() as $User) {
            ?>
            <tr>
                <td><?php echo $this->Form->CheckBox('Applicants[]', '', array('value' => $User->UserID)); ?></td>
                <td class="Alt">
                    <?php
                    printf(t('<strong>%1$s</strong> (%2$s) %3$s'), $User->Name, Gdn_Format::Email($User->Email), Gdn_Format::date($User->DateInserted));

                    $this->EventArguments['User'] = $User;
                    $this->fireEvent("ApplicantInfo");
                    echo '<blockquote>'.$User->DiscoveryText.'</blockquote>';

                    $this->EventArguments['User'] = $User;
                    $this->fireEvent("AppendApplicantInfo");
                    ?></td>
                <td><?php
                    echo anchor(t('Approve'), '/user/approve/'.$User->UserID.'/'.$Session->TransientKey())
                        .' '.anchor(t('Decline'), '/user/decline/'.$User->UserID.'/'.$Session->TransientKey());
                    ?></td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
    <div class="Info">
    <?php
    echo $this->Form->button('Approve', array('Name' => 'Submit', 'class' => 'SmallButton'));
    echo $this->Form->button('Decline', array('Name' => 'Submit', 'class' => 'SmallButton'));
    ?></div><?php
}
echo $this->Form->close();
