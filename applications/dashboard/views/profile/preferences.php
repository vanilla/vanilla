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

                    $CountTypes = 0;
                    foreach ($this->data("PreferenceTypes.{$PreferenceGroup}") as $PreferenceType) {
                        echo wrap(t($PreferenceType), 'td', array('class' => 'PrefCheckBox'));
                        $PreferenceTypeOrder[$PreferenceType] = $CountTypes;
                        $CountTypes++;
                    }
                    ?>
                </tr>
                </thead>
                <tbody>
                <?php
                foreach ($Preferences as $Names) {
                    // Make sure there are preferences.
                    $ConfigCount = 0;
                    foreach ($Names as $Name) {
                        $CP = c('Preferences.'.$Name, '0');
                        if ($CP !== FALSE && $CP != 2)
                            $ConfigCount++;
                    }
                    if ($ConfigCount == 0)
                        continue;

                    echo '<tr>';
                    $Desc = val($Name, $this->data("PreferenceList.{$PreferenceGroup}"));
                    if (is_array($Desc))
                        list($Desc, $Location) = $Desc;
                    echo wrap($Desc, 'td', array('class' => 'Description'));

                    $LastName = '';
                    $i = 0;
                    foreach ($Names as $Name) {
                        $NameTypeExplode = explode(".", $Name);
                        $NameType = $NameTypeExplode[0];
                        $ConfigPref = c('Preferences.'.$Name, '0');
                        if ($ConfigPref === FALSE || $ConfigPref == 2) {
                            echo wrap('&nbsp;', 'td', array('class' => 'PrefCheckBox'));
                        } else {
                            if (count($Names) < $CountTypes) {
                                $PreferenceTypeOrderCount = 0;
                                foreach ($PreferenceTypeOrder as $PreferenceTypeName => $PreferenceTypeOrderValue) {
                                    if ($NameType == $PreferenceTypeName) {
                                        if ($PreferenceTypeOrderValue == $PreferenceTypeOrderCount) echo wrap($this->Form->CheckBox($Name, '', array('value' => '1')), 'td', array('class' => 'PrefCheckBox'));
                                    } else echo wrap('&nbsp;', 'td', array('class' => 'PrefCheckBox'));
                                    $PreferenceTypeOrderCount++;
                                }
                            } else echo wrap($this->Form->CheckBox($Name, '', array('value' => '1')), 'td', array('class' => 'PrefCheckBox'));
                        }
                        $LastName = $Name;
                        $i++;
                    }

                    echo '</tr>';
                }
                ?>
                </tbody>
            </table>
        <?php
        }
        $this->fireEvent('CustomNotificationPreferences');
        echo $this->Form->close('Save Preferences', '', array('class' => 'Button Primary'));
        $this->fireEvent("AfterPreferencesRender");
        ?>
    </div>
</div>
