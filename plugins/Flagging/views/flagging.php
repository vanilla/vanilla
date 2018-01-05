<?php if (!defined('APPLICATION')) exit(); ?>
<?php echo heading(t($this->data('Title'))); ?>
<section>
    <?php
    echo subheading(t('Flagging Settings'));
    echo $this->Form->open();
    echo $this->Form->errors();
    ?>
    <ul>
        <li class="form-group">
            <?php echo $this->Form->labelWrap('Category to Use', 'Plugins.Flagging.CategoryID'); ?>
            <div class="input-wrap">
                <?php echo $this->Form->categoryDropDown('Plugins.Flagging.CategoryID', ['Value' => c('Plugins.Flagging.CategoryID')]); ?>
            </div>
        </li>
        <li class="form-group">
            <div class="input-wrap no-label">
                <?php echo $this->Form->checkBox('Plugins.Flagging.UseDiscussions', t('Create Discussions')); ?>
            </div>
        </li>
    </ul>
    <?php echo $this->Form->close('Save'); ?>
</section>
<?php
// Flagged Items list
if (!count($this->FlaggedItems)) {
    echo '<div class="padded">'.t('FlagQueueEmpty', "There are no items awaiting moderation at this time.").'</div>';
} else { ?>
<div class="table-wrap">
    <table class="table-data js-tj">
        <thead>
        <tr>
            <th><?php echo t('Post Type'); ?></th>
            <th><?php echo t('Flagged By'); ?></th>
            <th class="options"></th>
        </tr>
        </thead>
        <tbody>
            <?php
            foreach ($this->FlaggedItems as $URL => $FlaggedList) {
                ksort($FlaggedList, SORT_STRING);
                $numComplaintsInThread = sizeof($FlaggedList);
                $multipleReportsString = '<div class="info italic">'.sprintf(t('Flagged by %s users.'), $numComplaintsInThread).'</div>';
                $flaggedBy = ($numComplaintsInThread > 1) ? $multipleReportsString : '';
                foreach ($FlaggedList as $FlagIndex => $Flag) {
                    $type = htmlentities(ucfirst($Flag['ForeignType']));
                    $flaggedBy .= sprintf(t('<strong>%s</strong> on %s'), anchor(htmlspecialchars($Flag['InsertName']), userUrl(['UserID' => $Flag['InsertUserID'], 'Name' => $Flag['InsertName']])), $Flag['DateInserted']).' '.t('said:');
                    $flaggedBy .= '<div class="FlaggedReason">'.Gdn_Format::text($Flag['Comment']).'</div>';
                }
                $options = anchor(t('Take Action'), $Flag['ForeignURL'], 'btn btn-primary');
                $options .= anchor(t('Dismiss'), 'plugin/flagging/dismiss/'.$Flag['EncodedURL'], 'btn btn-primary js-modal-confirm', ['data-body' => t('Are you sure you want to dismiss this flag?')]);
                ?>
                <tr>
                    <td class="FlaggedType"><?php echo $type; ?></td>
                    <td class="FlaggedBy"><?php echo $flaggedBy; ?></td>
                    <td class="options">
                        <div class="btn-group">
                        <?php echo $options; ?>
                        </div>
                    </td>
                </tr>
                <?php
            }
            ?>
        </tbody>
    </table>
</div>
<?php } ?>
