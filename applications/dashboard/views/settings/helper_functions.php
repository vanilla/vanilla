<?php if (!defined('APPLICATION')) exit();
/**
 * Get all tutorials, or a specific one.
 */
function getTutorials($tutorialCode = '') {
    // Define all Tutorials
    $Tutorials = [
        [
            'Code' => 'introduction',
            'Name' => 'Introduction to Vanilla',
            'Description' => 'This video gives you a brief overview of the Vanilla administrative dashboard and the forum itself.',
            'VideoID' => '31043422'
        ],
        [
            'Code' => 'using-the-forum',
            'Name' => 'Using the Forum',
            'Description' => 'Learn how to start, announce, close, edit and delete discussions and comments.',
            'VideoID' => '31502992'
        ],
        [
            'Code' => 'private-conversations',
            'Name' => 'Private Conversations',
            'Description' => 'Learn how to start new private conversations and add people to them.',
            'VideoID' => '31498383'
        ],
        [
            'Code' => 'user-profiles',
            'Name' => 'User Profiles',
            'Description' => 'Learn how to use and manage your user profile. ',
            'VideoID' => '31499266'
        ],
        [
            'Code' => 'appearance',
            'Name' => 'Changing the appearance of your forum',
            'Description' => 'This tutorial takes you through the "Appearance" section of the Vanilla Forums administrative dashboard.',
            'VideoID' => '31089641'
        ],
        [
            'Code' => 'roles-and-permissions',
            'Name' => 'Managing Roles and Permissions in Vanilla',
            'Description' => 'This tutorial walks you through how to create new roles and how to use permissions.',
            'VideoID' => '31091056'
        ],
        [
            'Code' => 'users',
            'Name' => 'Finding &amp; Managing Users',
            'Description' => 'This tutorial shows you how to search for and manage users.',
            'VideoID' => '31094514'
        ],
        [
            'Code' => 'category-management-and-advanced-settings',
            'Name' => 'Category Management &amp; Advanced Settings',
            'Description' => 'Learn how to add, edit, and manage categories. Also learn about advanced forum settings.',
            'VideoID' => '31492046'
        ],
        [
            'Code' => 'user-registration',
            'Name' => 'User Registration',
            'Description' => 'Learn to control how new users get into your community.',
            'VideoID' => '31493119'
        ]
    ];

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

/**
 * Converts addon info into a media item.
 *
 * @param $addonName
 * @param $addonInfo
 * @param $isEnabled
 * @param $addonType
 * @param $filter
 */
function writeAddonMedia($addonName, $addonInfo, $isEnabled, $addonType, $filter) {
    $capitalCaseSheme = new \Vanilla\Utility\CapitalCaseScheme();
    $addonInfo = $capitalCaseSheme->convertArrayKeys($addonInfo, ['RegisterPermissions']);

    $screenName = Gdn_Format::display(val('Name', $addonInfo, $addonName));
    $description = Gdn_Format::html(t(val('Name', $addonInfo, $addonName).' Description', val('Description', $addonInfo, '')));
    $id = Gdn_Format::url($addonName).'-addon';
    $documentationUrl = val('DocumentationUrl', $addonInfo, '');
    $media = new MediaItemModule($screenName, '', $description, 'li', ['id' => $id]);
    $media->setView('media-addon');
    $media->setDocumentationUrl($documentationUrl);

    // Icon

    $addon = Gdn::addonManager()->lookupAddon($addonName);
    $iconPath = '';
    if ($addon) {
        $iconPath = $addon->getIcon();
    }
    if (!$iconPath) {
        $iconPath = val('IconUrl', $addonInfo, 'applications/dashboard/design/images/addon-placeholder.png');
    }

    $media->setImage($iconPath);

    // IsConfigured badge

    $badges = [];

    if (isset($addonInfo['Configured'])) {
        $badges[] = [
            'text' => $addonInfo['Configured'] ? t('Configured') : t('Not Configured'),
            'cssClass' => $addonInfo['Configured'] ? 'badge-success' : 'badge-warning',
        ];
    }

    $media->addOption('badges', $badges);

    // Settings button

    $settingsUrl = $isEnabled ? val('SettingsUrl', $addonInfo, '') : '';
    $settingsPopupClass = val('UsePopupSettings', $addonInfo, true) ? ' js-modal' : '';

    if ($settingsUrl != '') {
        $attr = [
            'class' => 'btn btn-icon-border'.$settingsPopupClass,
            'aria-label' => sprintf(t('Settings for %s'), $screenName),
            'data-reload-page-on-save' => false
        ];

        $media->addButton(dashboardSymbol('settings'), url($settingsUrl), $attr);
    }

    // Toggle

    if ($addonType === 'locales') {
        $action = $isEnabled ? 'disable' : 'enable';
    } else {
        $action = $filter;
    }
    if ($isEnabled) {
        $label = sprintf(t('Disable %s'), $screenName);
    } else {
        $label = sprintf(t('Enable %s'), $screenName);
    }

    $url = '/settings/'.$addonType.'/'.$action.'/'.$addonName;

    $media->setToggle(slugify($addonName), $isEnabled, $url, $label);

    // Meta

    $info = [];

    // Requirements

    $requiredApplications = val('RequiredApplications', $addonInfo, false);
    $requiredPlugins = val('RequiredPlugins', $addonInfo, false);
    $requirements = [];

    if (is_array($requiredApplications)) {
        foreach ($requiredApplications as $requiredApplication => $versionInfo) {
            $requirements[] = sprintf(t('%1$s Version %2$s'), $requiredApplication, $versionInfo);
        }
    }
    if (is_array($requiredPlugins)) {
        foreach ($requiredPlugins as $requiredPlugin => $versionInfo) {
            $requirements[] = sprintf(t('%1$s Version %2$s'), $requiredPlugin, $versionInfo);
        }
    }

    if (!empty($requirements)) {
        $requirementsMeta = sprintf(t('Requires: %s'), implode(', ', $requirements));
        $info[] = $requirementsMeta;
    }

    // Authors

    $author = val('Author', $addonInfo, '');
    $authors = [];

    // Check if singular author is set

    if ($author) {
        $authorUrl = val('AuthorUrl', $addonInfo, '');
        if ($authorUrl) {
            $authors[] = anchor($author, $authorUrl);
        } else {
            $authors[] = $author;
        }
    }

    // Check for multiple authors

    foreach (val('Authors', $addonInfo, []) as $author) {
        if (val('Homepage', $author)) {
            $authors[] = anchor(val('Name', $author), val('Homepage', $author));
        } else {
            $authors[] = val('Name', $author);
        }
    }

    if ($authors) {
        $authors = implode(', ', $authors);
        $info[] = sprintf(t('Created by %s'), $authors);
    }

    // Version Info

    $version = Gdn_Format::display(val('Version', $addonInfo, ''));
    $newVersion = val('NewVersion', $addonInfo, '');
    $upgrade = $newVersion != '' && version_compare($newVersion, $version, '>');

    if ($version != '') {
        $info[] = sprintf(t('Version %s'), $version);
    }

    $pluginUrl = val('PluginUrl', $addonInfo, '');

    if ($upgrade && $pluginUrl) {
        $info[] = anchor(sprintf(t('%1$s version %2$s is available.'), $screenName, $newVersion),
            combinePaths([$pluginUrl, 'find', urlencode($screenName)], '/'));
    }

    if ($pluginUrl != '') {
        $info[] = anchor(t('Visit Site'), $pluginUrl);
    }

    // Extra meta in addon array

    if ($meta = val('Meta', $addonInfo)) {
        foreach ($meta as $key => $value) {
            if (is_numeric($key)) {
                $info[] = $value;
            } else {
                $info[] = t($key).': '.$value;
            }
        }
    }

    $media->setMeta($info);
    echo $media;
}

