<h1><?php echo t('Archive Discussions'); ?></h1>
<?php
echo $this->Form->open();
echo $this->Form->errors();
?>
<ul>
    <li class="form-group">
        <div class="label-wrap">
            <?php
            echo $this->Form->label('Archive Discussions', 'Vanilla.Archive.Date');
            echo '<div class="info">',
            t('Vanilla.Archive.Description', 'You can choose to archive forum discussions older than a certain date. Archived discussions are effectively closed, allowing no new posts.'),
            '</div>'; ?>
        </div>
        <div class="input-wrap">
            <?php
            echo $this->Form->Calendar('Vanilla.Archive.Date', ['placeholder' => t('YYYY-mm-dd')]);
            ?>
        </div>
    </li>
    <li class="form-group">
        <div class="input-wrap no-label">
            <?php
            echo $this->Form->CheckBox('Vanilla.Archive.Exclude', 'Exclude archived discussions from the discussions list');
            ?>
        </div>
    </li>
</ul>
<div class="form-footer js-modal-footer">
    <?php echo $this->Form->close('Save'); ?>
</div>
