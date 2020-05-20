<?php if (!defined('APPLICATION')) exit();

echo '<h1>', $this->data('Title'), '</h1>';

/** @var Gdn_Form $Form */
$Form = $this->Form; //new gdn_Form();
echo $Form->open();
echo $Form->errors();
?>
<ul>
    <li class="form-group">
        <div class="label-wrap-wide">
            <?php echo $Form->label('Enable Pocket', 'Enabled'); ?>
            <div class="info"><?php echo t('Disabled pockets will not be displayed.'); ?></div>
        </div>
        <div class="input-wrap-right">
            <?php echo $Form->toggle('Enabled'); ?>
        </div>
    </li>
    <li class="form-group">
        <div class="label-wrap">
        <?php
            echo $Form->label('Name', 'Name');
            echo '<div class="info">', t('Enter a descriptive name.', 'Enter a descriptive name for the pocket. This name will not show up anywhere except when managing your pockets here so it is only used to help you remember the pocket.'), '</div>'; ?>
        </div>
        <?php echo $Form->textBoxWrap('Name'); ?>
    </li>
    <li class="form-group">
        <div class="label-wrap">
        <?php
            echo $Form->label('Body', 'Body');
            echo '<div class="info">', t('The text of the pocket.', 'Enter the text of the pocket. This will be output exactly as you type it so make sure that you enter valid HTML.'), '</div>'; ?>
        </div>
        <?php echo $Form->textBoxWrap('Body', ['Multiline' => true, 'class' => 'js-pocket-body']);
        ?>
    </li>
    <li class="form-group">
        <div class="label-wrap">
        <?php
            echo $Form->label('Page', 'Page');
            echo '<div class="info">', t('Select the location of the pocket.', 'Select the location of the pocket.'), '</div>'; ?>
        </div>
        <div class="input-wrap">
        <?php echo $Form->dropdown('Page', $this->data('Pages')); ?>
        </div>
    </li>
    <li class="form-group">
        <div class="label-wrap">
        <?php
            echo $Form->label('Location', 'Location');
            echo '<div class="info">', t('Select the location of the pocket.', 'Select the location of the pocket.'), '</div>'; ?>
        </div>
        <div class="input-wrap">
        <?php echo $Form->dropdown('Location', array_merge(['' => '('.sprintf(t('Select a %s'), t('Location')).')'], $this->data('LocationsArray'))); ?>
        </div>
    </li>
    <li class="js-repeat form-group">
        <?php echo $Form->labelWrap('Repeat', 'RepeatType'); ?>
        <div class="input-wrap">
        <?php
            echo '<div>', $Form->radio('RepeatType', 'Before', ['Value' => Pocket::REPEAT_BEFORE, 'Default' => true]), '</div>';
            echo '<div>', $Form->radio('RepeatType', 'After', ['Value' => Pocket::REPEAT_AFTER]), '</div>';
            echo '<div>', $Form->radio('RepeatType', 'Repeat Every', ['Value' => Pocket::REPEAT_EVERY]), '</div>';
            echo '<div>', $Form->radio('RepeatType', 'Given Indexes', ['Value' => Pocket::REPEAT_INDEX]), '</div>';

            // Options for repeat every.
            echo '<div class="RepeatOptions RepeatEveryOptions padded-top">',
                '<div class="form-group">',
                $Form->labelWrap('Frequency', 'EveryFrequency'),
                $Form->textBoxWrap('EveryFrequency'),
                '</div>',
                '<div class="form-group">',
                $Form->labelWrap('Begin At', 'EveryBegin'),
                $Form->textBoxWrap('EveryBegin'),
                '</div>',
                '</div>';


            // Options for repeat indexes.
            echo '<div class="RepeatOptions RepeatIndexesOptions padded-top">',
                '<div class="form-group">',
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
    <li class="form-group">
        <?php
            echo $Form->labelWrap('Conditions', ''); ?>
        <div class="input-wrap">
            <?php
            echo $Form->checkbox("MobileOnly", t("Only display on mobile browsers."));
            echo $Form->checkbox("MobileNever", t("Never display on mobile browsers."));
            echo $Form->checkbox("EmbeddedNever", t("Don't display for embedded comments."));
            echo $Form->checkbox("ShowInDashboard", t("Display in dashboard. (not recommended)"));
            echo $Form->checkbox("Ad", t("This pocket is an ad.").' '.t("Users with the no ads permission will not see this pocket."));
            ?>
        </div>
    </li>

    <?php echo $Form->react(
        "Roles", "pocket-multi-role-input",
        [
            "tag" => "li",
            "value" => $Form->getValue("Roles") ?? ""
        ]
    );
    ?>

    <li class="form-group">
        <div class="label-wrap-wide">
            <?php echo $Form->label('Test Mode', 'Testing'); ?>
            <?php echo wrap(t('The pocket will only be displayed for pocket administrators.'), 'div', ['class' => 'info']) ?>
        </div>
        <div class="input-wrap-right">
            <?php echo $Form->toggle('TestMode'); ?>
        </div>
    </li>
</ul>

<?php echo $Form->close('Save');

