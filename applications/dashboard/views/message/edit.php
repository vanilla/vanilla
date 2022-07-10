<?php if (!defined('APPLICATION')) exit(); ?>
    <h1><?php
        if (is_array($this->Message))
            echo t('Edit Message');
        else
            echo t('Add Message');
        ?></h1>
<?php
/** @var \MessageController $this */
echo $this->Form->open();
echo $this->Form->errors();
?>
    <ul role="presentation">
        <li class="form-group" role="presentation">
            <div class="label-wrap">
                <?php echo $this->Form->label('Appearance', 'Type'); ?>
            </div>
            <div class="input-wrap">
                <?php
                echo $this->Form->radio('Type', t('Casual'), ['value' => 'casual']);
                echo $this->Form->radio('Type', t('Information'), ['value' => 'info']);
                echo $this->Form->radio('Type', t('Alert'), ['value' => 'alert']);
                echo $this->Form->radio('Type', t('Warning'), ['value' => 'warning']);
                ?>
            </div>
        </li>
        <li class="form-group" role="presentation">
            <div class="label-wrap">
            <?php echo $this->Form->label('Message', 'Content'); ?>
            <?php echo wrap(t("This field partially supports HTML."), 'div', ['class' => 'info']); ?>
            </div>
            <div class="input-wrap">
            <?php echo $this->Form->textBox('Content', ['MultiLine' => TRUE]); ?>
            </div>
        </li>
        <li class="form-group" role="presentation">
            <div class="label-wrap">
                <?php echo $this->Form->label('Page', 'LayoutViewType'); ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->dropDown('LayoutViewType', $this->data('Locations')); ?>
            </div>
        </li>
        <li class="form-group" role="presentation">
            <div class="label-wrap">
                <?php echo $this->Form->label('Position', 'AssetTarget'); ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->dropDown('AssetTarget', $this->AssetData); ?>
            </div>
        </li>
        <li class="form-group" role="presentation">
            <div class="label-wrap">
                <?php echo $this->Form->label('Category', 'RecordID'); ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->dropDown('RecordID', $this->data('Categories'), ['IncludeNull' => t('All Categories')]); ?>
                <div class="no-label padded-top">
                    <?php echo $this->Form->checkBox('IncludeSubcategories', 'Include Subcategories'); ?>
                </div>
            </div>
        </li>
        <li class="form-group" role="presentation">
            <?php echo $this->Form->toggle('AllowDismiss', 'Dismissible', ['value' => '1'], 'Allow users to dismiss this message.'); ?>
        </li>
        <li class="form-group" role="presentation">
            <?php echo $this->Form->toggle('Enabled', 'Enabled', ['value' => '1'], 'An enabled message will be visible on the site.'); ?>
        </li>
    </ul>
<?php echo $this->Form->close('Save');
