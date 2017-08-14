<?php if (!defined('APPLICATION')) exit(); ?>
<style>
    table.PreferenceGroup {
        width: 500px;
    }

    table.PreferenceGroup th {
        vertical-align: bottom;
        text-align: center;
    }

    table.PreferenceGroup thead .TopHeading {
        text-align: center;
        border-bottom: none;
    }

    table.PreferenceGroup thead .BottomHeading {
        border-top: none;
    }

    th.PrefCheckBox, td.PrefCheckBox {
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
            if ($PreferenceGroup == 'Notifications') {
                $Header = t('General');
            } else {
                $Header = t($PreferenceGroup);
            }
            ?>
            <h2><?php echo $Header; ?></h2>
            <table class="PreferenceGroup">
                <thead>
                <tr>
                    <th id="<?php echo $Header; ?>NotificationHeader" scope="col" style="text-align:left">
                        <?php echo t('Notification'); ?>
                    </th>
                    <?php
                    $PreferenceTypes = $this->data("PreferenceTypes.{$PreferenceGroup}");
                    foreach ($PreferenceTypes as $PreferenceType) {
                        if ($PreferenceType === 'Email' && c('Garden.Email.Disabled')) {
                            continue;
                        }
                        echo wrap(
                            t($PreferenceType),
                            'th',
                            [
                                'id' => "{$Header}{$PreferenceType}Header",
                                'class' => 'PrefCheckBox',
                                'scope' => 'col'
                            ]
                        );
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
                        if ($NotificationType === 'Email' && c('Garden.Email.Disabled')) {
                            continue;
                        }

                        $ConfigPreference = c('Preferences.'.$NotificationType.'.'.$Event, 0);
                        $preferenceDisabled = ($ConfigPreference === false || $ConfigPreference == 2);

                        if (!in_array($NotificationType.'.'.$Event, $Settings) || $preferenceDisabled) {
                            // If preference does not exist, or is excluded by a config setting, show an empty cell.
                            $ColumnsMarkup .= wrap('&nbsp;', 'td', ['class' => 'PrefCheckBox']);
                        } else {
                            // Everything's fine, show checkbox.
                            $checkbox = $this->Form->checkBox($NotificationType.'.'.$Event, '', ['value' => '1']);
                            $ColumnsMarkup .= wrap(
                                $checkbox,
                                'td',
                                [
                                    'class' => 'PrefCheckBox',
                                    'headers' => "{$Header}{$NotificationType}Header"
                                ]
                            );
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
                        echo wrap(
                            $Description,
                            'td',
                            [
                                'class' => 'Description',
                                'headers' => "{$Header}NotificationHeader"
                            ]
                        );
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
