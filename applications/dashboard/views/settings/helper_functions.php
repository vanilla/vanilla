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
