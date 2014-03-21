<?php if (!defined('APPLICATION')) exit();
/**
 * Get all tutorials, or a specific one.
 */
function GetTutorials($TutorialCode = '') {
   // Define all Tutorials
   $Tutorials = array(
      array(
         'Code' => 'introduction',
         'Name' => T('Introduction to Vanilla'),
         'Description' => T('This video gives you a brief overview of the Vanilla administrative dashboard and the forum itself.'),
         'VideoID' => '31043422'
      ),
      array(
         'Code' => 'using-the-forum',
         'Name' => T('Using the Forum'),
         'Description' => T('Learn how to start, announce, close, edit and delete discussions and comments.'),
         'VideoID' => '31502992'
      ),
      array(
         'Code' => 'private-conversations',
         'Name' => T('Private Conversations'),
         'Description' => T('Learn how to start new private conversations and add people to them.'),
         'VideoID' => '31498383'
      ),
      array(
         'Code' => 'user-profiles',
         'Name' => T('User Profiles'),
         'Description' => T('Learn how to use and manage your user profile.'),
         'VideoID' => '31499266'
      ),
      array(
         'Code' => 'appearance',
         'Name' => T('Changing the appearance of your forum'),
         'Description' => T('This tutorial takes you through the "Appearance" section of the Vanilla Forums administrative dashboard.'),
         'VideoID' => '31089641'
      ),
      array(
         'Code' => 'roles-and-permissions',
         'Name' => T('Managing Roles and Permissions in Vanilla'),
         'Description' => T('This tutorial walks you through how to create new roles and how to use permissions.'),
         'VideoID' => '31091056'
      ),
      array(
         'Code' => 'users',
         'Name' => T('Finding &amp; Managing Users'),
         'Description' => T('This tutorial shows you how to search for and manage users.'),
         'VideoID' => '31094514'
      ),
      array(
         'Code' => 'category-management-and-advanced-settings',
         'Name' => T('Category Management &amp; Advanced Settings'),
         'Description' => T('Learn how to add, edit, and manage categories. Also learn about advanced forum settings.'),
         'VideoID' => '31492046'
      ),
      array(
         'Code' => 'user-registration',
         'Name' => T('User Registration'),
         'Description' => T('Learn to control how new users get into your community.'),
         'VideoID' => '31493119'
      )
   );
   
   // Default Thumbnails
   $Thumbnail = Asset('applications/dashboard/design/images/help-tn-200.jpg');
   $LargeThumbnail = Asset('applications/dashboard/design/images/help-tn-640.jpg');
   for ($i = 0; $i < count($Tutorials); $i++) {
      $Tutorials[$i]['Thumbnail'] = $Thumbnail;
      $Tutorials[$i]['LargeThumbnail'] = $LargeThumbnail;
   }
   
   if ($TutorialCode != '') {
      $Keys = ConsolidateArrayValuesByKey($Tutorials, 'Code');
      $Index = array_search($TutorialCode, $Keys);
      if ($Index === FALSE)
         return FALSE; // Not found!
      
      // Found it, so define it's thumbnail location
      $Tutorial = GetValue($Index, $Tutorials);
      $VideoID = GetValue('VideoID', $Tutorial);
      try {
         $Vimeo = unserialize(file_get_contents("http://vimeo.com/api/v2/video/".$Tutorial['VideoID'].".php"));
         $Tutorial['Thumbnail'] = GetValue('thumbnail_medium', GetValue('0', $Vimeo));
         $Tutorial['LargeThumbnail'] = GetValue('thumbnail_large', GetValue('0', $Vimeo));
      } catch (Exception $Ex) {
         // Do nothing   
      }
      return $Tutorial;
   } else {
      // Loop through each tutorial populating the thumbnail image location
      try {
         foreach ($Tutorials as $Key => $Tutorial) {
            $Vimeo = unserialize(file_get_contents("http://vimeo.com/api/v2/video/".$Tutorial['VideoID'].".php"));
            $Tutorials[$Key]['Thumbnail'] = GetValue('thumbnail_medium', GetValue('0', $Vimeo));
            $Tutorials[$Key]['LargeThumbnail'] = GetValue('thumbnail_large', GetValue('0', $Vimeo));
         }
      } catch (Exception $Ex) {
         // Do nothing   
      }
      return $Tutorials;
   }
}
