<?php if (!defined('APPLICATION')) exit(); ?>
<style>
    table.PreferenceGroup {
        width: 500px;
    }

    thead td {
        vertical-align: bottom;
        text-align: center;
    }

    table.PreferenceGroup thead .TopHeading {
        border-bottom: none;
    }

    table.PreferenceGroup thead .BottomHeading {
        border-top: none;
    }

    td.PrefCheckBox {
        width: 50px;
        text-align: center;
    }

    table.PreferenceGroup tbody tr:hover td {
        background: #efefef;
    }

    .Info {
        width: 486px;
    }
</style>
<div class="FormTitleWrapper">
    <h1 class="H"><?php echo $this->data('Title'); ?></h1>

    <div class="Preferences">
        <?php
        echo $this->Form->open();
        echo $this->Form->errors();
        $this->fireEvent("BeforePreferencesRender");

        foreach ($this->data('PreferenceGroups') as $PreferenceGroup => $Preferences) {
            echo wrap(t($PreferenceGroup == 'Notifications' ? 'General' : $PreferenceGroup), 'h2');
            ?>
            <table class="PreferenceGroup">
                <thead>
                <tr>
                    <?php
                    echo wrap(t('Notification'), 'td', array('style' => 'text-align: left'));

                    $PreferenceTypes = $this->data("PreferenceTypes.{$PreferenceGroup}");
                    foreach ($PreferenceTypes as $PreferenceType) {
                        echo wrap(t($PreferenceType), 'td', array('class' => 'PrefCheckBox'));
                    }
                    ?>
                </tr>
                </thead>
                <tbody>
                <?php
                // Get all descriptions of possible notifications
                $Descriptions = $this->data("PreferenceList.{$PreferenceGroup}");
                // Loop through all possible preferences.
                foreach ($Preferences as $Event => $Settings) {
                    $RowHasConfigValues = false;
                    $ColumnsMarkup = '';
                    // Loop through all means of notification.
                    foreach ($PreferenceTypes as $NotificationType) {
                        $ConfigPreference = c('Preferences.'.$NotificationType.'.'.$Event, 0);
                        $preferenceDisabled = ($ConfigPreference === false || $ConfigPreference == 2);

                        if (!in_array($NotificationType.'.'.$Event, $Settings) || $preferenceDisabled) {
                            // If preference does not exist, or is excluded by a config setting, show an empty cell.
                            $ColumnsMarkup .= wrap('&nbsp;', 'td', ['class' => 'PrefCheckBox']);
                        } else {
                            // Everything's fine, show checkbox.
                            $checkbox = $this->Form->checkBox($NotificationType.'.'.$Event, '', ['value' => '1']);
                            $ColumnsMarkup .= wrap($checkbox, 'td', ['class' => 'PrefCheckBox']);
                            // Set flag so that line is printed.
                            $RowHasConfigValues = true;
                        }
                    }
                    // Check if there are config values in this row.
                    if ($RowHasConfigValues) {
                        // Make sure we have complete numeric indexes.
                        $Settings = array_values($Settings);
                        $Description = val($Settings[0], $Descriptions);
                        if (is_array($Description)) {
                            $Description = $Description[0];
                        }
                        echo '<tr>';
                        echo wrap($Description, 'td', ['class' => 'Description']);
                        echo $ColumnsMarkup;
                        echo '</tr>';
                    }
                }
                ?>
                </tbody>
            </table>
        <?php
        }
        $this->fireEvent('CustomNotificationPreferences');
        echo $this->Form->close('Save Preferences', '', ['class' => 'Button Primary']);
        $this->fireEvent("AfterPreferencesRender");
        ?>
    </div>
</div>
