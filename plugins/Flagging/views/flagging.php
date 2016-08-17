<?php if (!defined('APPLICATION')) exit(); ?>

<h1><?php echo t($this->Data['Title']); ?></h1>
<?php
// Settings
echo $this->Form->open();
echo $this->Form->errors();
?>
<h2><?php echo t('Flagging Settings'); ?></h2>
<ul>
    <li class="form-group row">
        <?php echo $this->Form->labelWrap('Category to Use', 'Plugins.Flagging.CategoryID'); ?>
        <div class="input-wrap">
            <?php echo $this->Form->CategoryDropDown('Plugins.Flagging.CategoryID', array('Value' => c('Plugins.Flagging.CategoryID'))); ?>
        </div>
    </li>
    <li class="form-group row">
        <div class="input-wrap no-label">
            <?php echo $this->Form->checkBox('Plugins.Flagging.UseDiscussions', t('Create Discussions')); ?>
        </div>
    </li>
</ul>
<div class="form-footer padded-bottom">
    <?php echo $this->Form->close('Save'); ?>
</div>
<?php
// Flagged Items list
if (!count($this->FlaggedItems)) {
    echo '<div class="padded">'.t('FlagQueueEmpty', "There are no items awaiting moderation at this time.").'</div>';
} else { ?>
<div class="table-wrap padded">
    <table>
        <thead>
        <tr>
            <th><?php echo t('Post Type'); ?></th>
            <th><?php echo t('Flagged By'); ?></th>
            <th class="options"><?php echo t('Options'); ?></th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <?php
            foreach ($this->FlaggedItems as $URL => $FlaggedList) {
                ksort($FlaggedList, SORT_STRING);
                $numComplaintsInThread = sizeof($FlaggedList);
                $multipleReportsString = '<div class="info italic">'.sprintf(t('Flagged by %s users.'), $numComplaintsInThread).'</div>';
                $flaggedBy = ($numComplaintsInThread > 1) ? $multipleReportsString : '';
                foreach ($FlaggedList as $FlagIndex => $Flag) {
                    $type = ucfirst($Flag['ForeignType']);
                    $flaggedBy .= sprintf(t('<strong>%s</strong> on %s'), anchor($Flag['InsertName'], "profile/{$Flag['InsertUserID']}/{$Flag['InsertName']}"), $Flag['DateInserted']).' '.t('said:');
                    $flaggedBy .= '<div class="FlaggedReason">'.Gdn_Format::text($Flag['Comment']).'</div>';
                }
                $options = anchor(t('Take Action'), $Flag['ForeignURL'], 'btn btn-primary');
                $options .= anchor(t('Dismiss'), 'plugin/flagging/dismiss/'.$Flag['EncodedURL'], 'btn btn-primary js-modal-confirm js-hijack', ['data-content' => ['body' => t('Are you sure you want to dismiss this flag?')]]);
                ?>
                <td class="FlaggedType"><?php echo $type; ?></td>
                <td class="FlaggedBy"><?php echo $flaggedBy; ?></td>
                <td class="options"><?php echo $options; ?></td>
                <?php
            }
            ?>
        </tr>
        </tbody>
    </table>
</div>
<?php } ?>
