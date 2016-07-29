<?php if (!defined('APPLICATION')) exit();

echo '<h1>', $this->data('Title'), '</h1>';

$Form = $this->Form; //new Gdn_Form();
echo $Form->open();
echo $Form->errors();
?>
<ul>
    <li class="form-group row">
        <div class="label-wrap">
        <?php
            echo $Form->label('Name', 'Name');
            echo '<div class="info">', t('Enter a descriptive name.', 'Enter a descriptive name for the pocket. This name will not show up anywhere except when managing your pockets here so it is only used to help you remember the pocket.'), '</div>'; ?>
        </div>
        <?php echo $Form->textBoxWrap('Name'); ?>
    </li>
    <li class="form-group row">
        <div class="label-wrap">
        <?php
            echo $Form->label('Body', 'Body');
            echo '<div class="info">', t('The text of the pocket.', 'Enter the text of the pocket. This will be output exactly as you type it so make sure that you enter valid HTML.'), '</div>'; ?>
        </div>
        <?php echo $Form->textBoxWrap('Body', array('Multiline' => true));
        ?>
    </li>
    <li class="form-group row">
        <div class="label-wrap">
        <?php
            echo $Form->label('Page', 'Page');
            echo '<div class="info">', t('Select the location of the pocket.', 'Select the location of the pocket.'), '</div>'; ?>
        </div>
        <div class="input-wrap">
        <?php echo $Form->dropdown('Page', $this->data('Pages')); ?>
        </div>
    </li>
    <li class="form-group row">
        <div class="label-wrap">
        <?php
            echo $Form->label('Location', 'Location');
            echo '<div class="info">', t('Select the location of the pocket.', 'Select the location of the pocket.'), '</div>'; ?>
        </div>
        <div class="input-wrap">
        <?php echo $Form->dropdown('Location', array_merge(array('' => '('.sprintf(T('Select a %s'), t('Location')).')'), $this->data('LocationsArray'))); ?>
        </div>
    </li>
    <li class="js-repeat form-group row">
        <?php echo $Form->labelWrap('Repeat', 'RepeatType'); ?>
        <div class="input-wrap">
        <?php
            echo '<div>', $Form->radio('RepeatType', 'Before', array('Value' => Pocket::REPEAT_BEFORE, 'Default' => true)), '</div>';
            echo '<div>', $Form->radio('RepeatType', 'After', array('Value' => Pocket::REPEAT_AFTER)), '</div>';
            echo '<div>', $Form->radio('RepeatType', 'Repeat Every', array('Value' => Pocket::REPEAT_EVERY)), '</div>';
            echo '<div>', $Form->radio('RepeatType', 'Given Indexes', array('Value' => Pocket::REPEAT_INDEX)), '</div>';

            // Options for repeat every.
            echo '<div class="RepeatOptions RepeatEveryOptions padded-top">',
                '<div class="form-group row">',
                $Form->labelWrap('Frequency', 'EveryFrequency'),
                $Form->textBoxWrap('EveryFrequency'),
                '</div>',
                '<div class="form-group row">',
                $Form->labelWrap('Begin At', 'EveryBegin'),
                $Form->textBoxWrap('EveryBegin'),
                '</div>',
                '</div>';


            // Options for repeat indexes.
            echo '<div class="RepeatOptions RepeatIndexesOptions padded-top">',
                '<div class="form-group row">',
                '<div class="label-wrap">',
                $Form->label('Indexes', 'Indexes'),
                '<div class="info">', t('Enter a comma-delimited list of indexes, starting at 1.'), '</div>',
                '</div>',
                '<div class="input-wrap">',
                $Form->textBox('Indexes'),
                '</div>',
                '</div>',
                '</div>';
        ?>
        </div>
    </li>
    <li class="form-group row">
        <?php
            echo $Form->labelWrap('Conditions', ''); ?>
        <div class="input-wrap">
            <?php
            echo $Form->checkbox("MobileOnly", t("Only display on mobile browsers."));
            echo '<div class="info">', t('Limit the display of this pocket to "mobile only".'), '</div>';

            echo $Form->checkbox("MobileNever", t("Never display on mobile browsers."));
            echo '<div class="info">', t('Limit the display of this pocket for mobile devices.'), '</div>';

            echo $Form->checkbox("EmbeddedNever", t("Don't display for embedded comments."));
            echo '<div class="info">', t('Limit the display of this pocket for embedded comments.'), '</div>';

            echo $Form->checkbox("ShowInDashboard", t("Display in dashboard. (not recommended)"));
            echo '<div class="info">', t("Most pockets shouldn't be displayed in the dashboard."), '</div>';

            echo $Form->checkbox("Ad", t("This pocket is an ad."));
            echo '<div class="info">', t("Users with the no ads permission will not see this pocket."), '</div>';
            ?>
        </div>
    </li>
    <li class="form-group row">
        <?php
            echo $Form->labelWrap('Enable/Disable', 'Disabled'); ?>
        <div class="input-wrap">
        <?php
            echo '<div>', $Form->radio('Disabled', t('Enabled', 'Enabled: The pocket will be displayed.'), array('Value' => Pocket::ENABLED)), '</div>';

            echo '<div>', $Form->radio('Disabled', t('Disabled', 'Disabled: The pocket will <b>not</b> be displayed.'), array('Value' => Pocket::DISABLED)), '</div>';

            echo '<div>', $Form->radio('Disabled', t('Test Mode', 'Test Mode: The pocket will only be displayed for pocket administrators.'), array('Value' => Pocket::TESTING)), '</div>';
        ?>
        </div>
    </li>
</ul>
<div class="js-modal-footer form-footer">
<?php echo $Form->button('Save'); ?>
</div>
<?php $Form->close();

