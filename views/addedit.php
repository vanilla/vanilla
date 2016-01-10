<?php if (!defined('APPLICATION')) exit();

echo '<h1>', $this->data('Title'), '</h1>';

$Form = $this->Form; //new Gdn_Form();
echo $Form->open();
echo $Form->errors();
?>
<ul>
    <li>
        <?php
            echo $Form->label('Name', 'Name');
            echo '<div class="Info2">', t('Enter a descriptive name.', 'Enter a descriptive name for the pocket. This name will not show up anywhere except when managing your pockets here so it is only used to help you remember the pocket.'), '</div>';
            echo $Form->textBox('Name');
        ?>
    </li>
    <li>
        <?php
            echo $Form->label('Body', 'Body');
            echo '<div class="Info2">', t('The text of the pocket.', 'Enter the text of the pocket. This will be output exactly as you type it so make sure that you enter valid HTML.'), '</div>';
            echo $Form->textBox('Body', array('Multiline' => true));
        ?>
    </li>
    <li>
        <?php
            echo $Form->label('Page', 'Page');
            echo '<div class="Info2">', t('Select the location of the pocket.', 'Select the location of the pocket.'), '</div>';
            echo $Form->dropdown('Page', $this->data('Pages'));
        ?>
    </li>
    <li>
        <?php
            echo $Form->label('Location', 'Location');
            echo '<div class="Info2">', t('Select the location of the pocket.', 'Select the location of the pocket.'), '</div>';
            echo $Form->dropdown('Location', array_merge(array('' => '('.sprintf(T('Select a %s'), t('Location')).')'), $this->data('LocationsArray')));
            // Write the help for each location type.
            foreach ($this->data('Locations') as $Location => $Options) {
                if (!array_key_exists('Description', $Options))
                    continue;

                echo '<div class="Info LocationInfo '.$Location.'Info">',
                    Gdn_Format::html($Options['Description']),
                    '</div>';
            }
        ?>
    </li>
    <li class="js-repeat">
        <?php
            echo $Form->label('Repeat', 'RepeatType');
            echo '<div>', $Form->radio('RepeatType', 'Before', array('Value' => Pocket::REPEAT_BEFORE, 'Default' => true)), '</div>';
            echo '<div>', $Form->radio('RepeatType', 'After', array('Value' => Pocket::REPEAT_AFTER)), '</div>';
            echo '<div>', $Form->radio('RepeatType', 'Repeat Every', array('Value' => Pocket::REPEAT_EVERY)), '</div>';

            // Options for repeat every.
            echo '<div class="RepeatOptions RepeatEveryOptions P">',
                '<div class="Info2">', t('Enter numbers starting at 1.'), '</div>',
                $Form->label('Frequency', 'EveryFrequency', array('Class' => 'SubLabel')),
                $Form->textBox('EveryFrequency', array('Class' => 'SmallInput')),
                ' <br /> '.$Form->label('Begin At', 'EveryBegin', array('Class' => 'SubLabel')),
                $Form->textBox('EveryBegin', array('Class' => 'SmallInput')),
                '</div>';

            echo '<div>', $Form->radio('RepeatType', 'Given Indexes', array('Value' => Pocket::REPEAT_INDEX)), '</div>';

            // Options for repeat indexes.
            echo '<div class="RepeatOptions RepeatIndexesOptions P">',
                '<div class="Info2">', t('Enter a comma-delimited list of indexes, starting at 1.'), '</div>',
                $Form->label('Indexes', 'Indexes', array('Class' => 'SubLabel')),
                $Form->textBox('Indexes'),
                '</div>';
        ?>
    </li>
    <li>
        <?php
            echo $Form->label('Conditions', '');

            echo '<div class="Info2">', t('Limit the display of this pocket to "mobile only".'), '</div>';
            echo $Form->checkbox("MobileOnly", t("Only display on mobile browsers."));

            echo '<div class="Info2">', t('Limit the display of this pocket for mobile devices.'), '</div>';
            echo $Form->checkbox("MobileNever", t("Never display on mobile browsers."));

            echo '<div class="Info2">', t('Limit the display of this pocket for embedded comments.'), '</div>';
            echo $Form->checkbox("EmbeddedNever", t("Don't display for embedded comments."));

            echo '<div class="Info2">', t("Most pockets shouldn't be displayed in the dashboard."), '</div>';
            echo $Form->checkbox("ShowInDashboard", t("Display in dashboard. (not recommended)"));

            echo '<div class="Info2">', t("Users with the no ads permission will not see this pocket."), '</div>';
            echo $Form->checkbox("Ad", t("This pocket is an ad."));
        ?>
    </li>
    <li>
        <?php
            echo $Form->label('Enable/Disable', 'Disabled');

            echo '<div>', $Form->radio('Disabled', t('Enabled', 'Enabled: The pocket will be displayed.'), array('Value' => Pocket::ENABLED)), '</div>';

            echo '<div>', $Form->radio('Disabled', t('Disabled', 'Disabled: The pocket will <b>not</b> be displayed.'), array('Value' => Pocket::DISABLED)), '</div>';

            echo '<div>', $Form->radio('Disabled', t('Test Mode', 'Test Mode: The pocket will only be displayed for pocket administrators.'), array('Value' => Pocket::TESTING)), '</div>';
        ?>
    </li>
</ul>
<?php
echo $Form->button('Save'),
    '&nbsp;&nbsp;&nbsp;&nbsp;', anchor(t('Cancel'), '/settings/pockets', 'Cancel'), ' ',
    $Form->close();
?>
