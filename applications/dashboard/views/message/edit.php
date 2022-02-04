<?php if (!defined('APPLICATION')) exit(); ?>
    <h1><?php
        if (is_object($this->Message))
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
                <?php echo $this->Form->label('Appearance', 'CssClass'); ?>
            </div>
            <div class="input-wrap">
                <?php
                echo $this->Form->radio('CssClass', t('Casual'), ['value' => 'CasualMessage']);
                echo $this->Form->radio('CssClass', t('Information'), ['value' => 'InfoMessage']);
                echo $this->Form->radio('CssClass', t('Alert'), ['value' => 'AlertMessage']);
                echo $this->Form->radio('CssClass', t('Warning'), ['value' => 'WarningMessage']);
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
                <?php echo $this->Form->label('Page', 'Location'); ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->dropDown('Location', $this->data('Locations')); ?>
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
                <?php echo $this->Form->label('Category', 'CategoryID'); ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->dropDown('CategoryID', $this->data('Categories'), ['IncludeNull' => t('All Categories')]); ?>
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
