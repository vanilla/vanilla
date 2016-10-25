<?php if (!defined('APPLICATION')) exit();
/**
 * Get all tutorials, or a specific one.
 */
function getTutorials($tutorialCode = '') {
    // Define all Tutorials
    $Tutorials = array(
        array(
            'Code' => 'introduction',
            'Name' => 'Introduction to Vanilla',
            'Description' => 'This video gives you a brief overview of the Vanilla administrative dashboard and the forum itself.',
            'VideoID' => '31043422'
        ),
        array(
            'Code' => 'using-the-forum',
            'Name' => 'Using the Forum',
            'Description' => 'Learn how to start, announce, close, edit and delete discussions and comments.',
            'VideoID' => '31502992'
        ),
        array(
            'Code' => 'private-conversations',
            'Name' => 'Private Conversations',
            'Description' => 'Learn how to start new private conversations and add people to them.',
            'VideoID' => '31498383'
        ),
        array(
            'Code' => 'user-profiles',
            'Name' => 'User Profiles',
            'Description' => 'Learn how to use and manage your user profile. ',
            'VideoID' => '31499266'
        ),
        array(
            'Code' => 'appearance',
            'Name' => 'Changing the appearance of your forum',
            'Description' => 'This tutorial takes you through the "Appearance" section of the Vanilla Forums administrative dashboard.',
            'VideoID' => '31089641'
        ),
        array(
            'Code' => 'roles-and-permissions',
            'Name' => 'Managing Roles and Permissions in Vanilla',
            'Description' => 'This tutorial walks you through how to create new roles and how to use permissions.',
            'VideoID' => '31091056'
        ),
        array(
            'Code' => 'users',
            'Name' => 'Finding &amp; Managing Users',
            'Description' => 'This tutorial shows you how to search for and manage users.',
            'VideoID' => '31094514'
        ),
        array(
            'Code' => 'category-management-and-advanced-settings',
            'Name' => 'Category Management &amp; Advanced Settings',
            'Description' => 'Learn how to add, edit, and manage categories. Also learn about advanced forum settings.',
            'VideoID' => '31492046'
        ),
        array(
            'Code' => 'user-registration',
            'Name' => 'User Registration',
            'Description' => 'Learn to control how new users get into your community.',
            'VideoID' => '31493119'
        )
    );

    // Default Thumbnails
    $thumbnail = asset('applications/dashboard/design/images/help-tn-200.jpg');
    $largeThumbnail = asset('applications/dashboard/design/images/help-tn-640.jpg');
    foreach ($Tutorials as &$tutorial) {
        $tutorial['Thumbnail'] = $thumbnail;
        $tutorial['LargeThumbnail'] = $largeThumbnail;
    }

    if ($tutorialCode != '') {
        $Keys = array_column($Tutorials, 'Code');
        $Index = array_search($tutorialCode, $Keys);
        if ($Index === false) {
            return false; // Not found!
        }

        // Found it, so define it's thumbnail location
        $Tutorial = val($Index, $Tutorials);
        try {
            $videoInfo = json_decode(file_get_contents("http://vimeo.com/api/v2/video/{$Tutorial['VideoID']}.json"));
            if ($videoInfo && $vimeo = array_shift($videoInfo)) {
                $Tutorial['Thumbnail'] = str_replace('http://', '//', val('thumbnail_medium', $vimeo));
                $Tutorial['LargeThumbnail'] = str_replace('http://', '//', val('thumbnail_large', $vimeo));
            }
        } catch (Exception $Ex) {
            // Do nothing
        }
        return $Tutorial;
    } else {
        // Loop through each tutorial populating the thumbnail image location
        try {
            foreach ($Tutorials as $Key => &$Tutorial) {
                $videoInfo = json_decode(file_get_contents("http://vimeo.com/api/v2/video/{$Tutorial['VideoID']}.json"));
                if ($videoInfo && $vimeo = array_shift($videoInfo)) {
                    $Tutorial['Thumbnail'] = str_replace('http://', '//', val('thumbnail_medium', $vimeo));
                    $Tutorial['LargeThumbnail'] = str_replace('http://', '//', val('thumbnail_large', $vimeo));
                }
            }
        } catch (Exception $Ex) {
            // Do nothing
        }
        return $Tutorials;
    }
}

function writeAddonMedia($addonName, $addonInfo, $isEnabled, $addonType, $filter) {
    $capitalCaseSheme = new \Vanilla\Utility\CapitalCaseScheme();
    $addonInfo = $capitalCaseSheme->convertArrayKeys($addonInfo, ['RegisterPermissions']);

    $Version = Gdn_Format::display(val('Version', $addonInfo, ''));
    $ScreenName = Gdn_Format::display(val('Name', $addonInfo, $addonName));

    $SettingsUrl = $isEnabled ? val('SettingsUrl', $addonInfo, '') : '';
    $SettingsPopupClass = 'js-modal';

    if (!val('UsePopupSettings', $addonInfo, true)) {
        $SettingsPopupClass = '';
    }

    $PluginUrl = val('PluginUrl', $addonInfo, '');
    $Author = val('Author', $addonInfo, '');
    $AuthorUrl = val('AuthorUrl', $addonInfo, '');
    $authors = [];
    if ($Author) {
        if ($AuthorUrl) {
            $authors[] = anchor($Author, $AuthorUrl);
        } else {
            $authors[] = $Author;
        }
    }
    foreach (val('Authors', $addonInfo, []) as $author) {
        if (val('Homepage', $author)) {
            $authors[] = anchor(val('Name', $author), val('Homepage', $author));
        } else {
            $authors[] = val('Name', $author);
        }
    }
    $NewVersion = val('NewVersion', $addonInfo, '');
    $Upgrade = $NewVersion != '' && version_compare($NewVersion, $Version, '>');
    $RowClass = $isEnabled ? 'Enabled' : 'Disabled';
    $addon = Gdn::addonManager()->lookupAddon($addonName);
    $IconPath = '';
    if ($addon) {
        $IconPath = $addon->getIcon();
    }
    if (!$IconPath) {
        $IconPath = val('IconUrl', $addonInfo, 'applications/dashboard/design/images/addon-placeholder.png');
    }
    ?>
    <div class="media-left">
        <?php echo wrap(img($IconPath, array('class' => 'PluginIcon')), 'div', ['class' => 'media-image-wrap']); ?>
    </div>
    <div class="media-body">
        <div class="media-heading"><div class="media-title"><?php echo $ScreenName; ?></div>
            <div class="info"><?php
                $Info = [];

                $RequiredApplications = val('RequiredApplications', $addonInfo, false);
                $RequiredPlugins = val('RequiredPlugins', $addonInfo, false);
                $requirements = '';
                if (is_array($RequiredApplications) || is_array($RequiredPlugins)) {
                    $requirements = t('Requires: ');
                }
                $i = 0;
                if (is_array($RequiredApplications)) {
                    if ($i > 0)
                        $requirements .= ', ';

                    foreach ($RequiredApplications as $RequiredApplication => $VersionInfo) {
                        $requirements .= sprintf(t('%1$s Version %2$s'), $RequiredApplication, $VersionInfo);
                        ++$i;
                    }
                }
                if ($RequiredPlugins !== FALSE) {
                    foreach ($RequiredPlugins as $RequiredPlugin => $VersionInfo) {
                        if ($i > 0)
                            $requirements .= ', ';

                        $requirements .= sprintf(t('%1$s Version %2$s'), $RequiredPlugin, $VersionInfo);
                        ++$i;
                    }
                }

                if ($requirements != '') {
                    $Info[] = $requirements;
                }

                if ($authors) {
                    $authors = implode(', ', $authors);
                    $Info[] = sprintf(t('Created by %s'), $authors);
                }

                if ($Version != '') {
                    $Info[] = sprintf(t('Version %s'), $Version);
                }

                if ($PluginUrl != '') {
                    $Info[] = anchor(t('Visit Site'), $PluginUrl);
                }

                if ($meta = val('Meta', $addonInfo)) {
                    foreach ($meta as $key => $value) {
                        if (is_numeric($key)) {
                            $Info[] = $value;
                        } else {
                            $Info[] = t($key).': '.$value;
                        }
                    }
                }

                echo implode('<span class="spacer">•</span>', $Info);

                ?>
                <?php
                if ($Upgrade) {
                    ?>
                    <div class="<?php echo $RowClass; ?>">
                        <div class="Alert"><a href="<?php
                            echo combinePaths(array($PluginUrl, 'find', urlencode($ScreenName)), '/');
                            ?>"><?php
                                printf(t('%1$s version %2$s is available.'), $ScreenName, $NewVersion);
                                ?></a></div>
                    </div>
                    <?php
                }
                ?>
            </div>
        </div>
        <div class="media-description"><?php echo Gdn_Format::html(t(val('Name', $addonInfo, $addonName).' Description', val('Description', $addonInfo, ''))); ?></div>
    </div>
    <div class="media-right media-options">
        <?php if ($SettingsUrl != '') {
            echo wrap(anchor(dashboardSymbol('settings'), $SettingsUrl, 'btn btn-icon-border '.$SettingsPopupClass, ['aria-label' => sprintf(t('Settings for %s'), $ScreenName), 'data-reload-page-on-save' => 'false']), 'div', ['class' => 'btn-wrap']);
        }
        ?>
        <div id="<?php echo strtolower($addonName); ?>-toggle">
            <?php
            if ($addonType === 'locales') {
                $action = $isEnabled ? 'disable' : 'enable';
            } else {
                $action = $filter;
            }
            if ($isEnabled) {
                $toggleState = 'on';
                echo wrap(anchor('<div class="toggle-well"></div><div class="toggle-slider"></div>', '/settings/'.$addonType.'/'.$action.'/'.$addonName, 'Hijack', ['aria-label' =>sprintf(t('Disable %s'), $ScreenName)]), 'span', array('class' => "toggle-wrap toggle-wrap-{$toggleState}"));
            } else {
                $toggleState = 'off';
                echo wrap(anchor('<div class="toggle-well"></div><div class="toggle-slider"></div>', '/settings/'.$addonType.'/'.$action.'/'.$addonName, 'Hijack', ['aria-label' =>sprintf(t('Enable %s'), $ScreenName)]), 'span', array('class' => "toggle-wrap toggle-wrap-{$toggleState}"));
            } ?>
        </div>
    </div>
<?php }
