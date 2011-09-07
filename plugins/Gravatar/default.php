<?php if (!defined('APPLICATION')) exit();

// Define the plugin:
$PluginInfo['Gravatar'] = array(
   'Name' => 'Gravatar',
   'Description' => 'Implements Gravatar avatars for all users who have not uploaded their own custom profile picture & icon.',
   'Version' => '1.3.1',
   'Author' => "Mark O'Sullivan",
   'AuthorEmail' => 'mark@vanillaforums.com',
   'AuthorUrl' => 'http://vanillaforums.com',
	'MobileFriendly' => TRUE
);

// 1.1 Fixes - Used GetValue to retrieve array props instead of direct references
// 1.2 Fixes - Make Gravatar work with the mobile theme
// 1.3 Fixes - Changed UserBuilder override to also accept an array of user info

class GravatarPlugin extends Gdn_Plugin {
   public function Setup() {
      // No setup required.
   }
}

if (!function_exists('UserBuilder')) {
   /**
    * Override the default UserBuilder function with one that switches the photo
    * out with a gravatar url if the photo is empty.
    */
   function UserBuilder($Object, $UserPrefix = '') {
		$Object = (object)$Object;
      $User = new stdClass();
      $UserID = $UserPrefix.'UserID';
      $Name = $UserPrefix.'Name';
      $Photo = $UserPrefix.'Photo';
      $Email = $UserPrefix.'Email';
      $Gender = $UserPrefix.'Gender';
      $User->UserID = $Object->$UserID;
      $User->Name = $Object->$Name;
      $User->Photo = property_exists($Object, $Photo) ? $Object->$Photo : '';
      $User->Gender = property_exists($Object, $Gender) ? $Object->$Gender : '';
      $HTTPS = GetValue('HTTPS', $_SERVER, '');
      $Protocol =  (strlen($HTTPS) || GetValue('SERVER_PORT', $_SERVER) == 443) ? 'https://secure.' : 'http://www.';
      if ($User->Photo == '' && property_exists($Object, $Email)) {
         $User->Photo = $Protocol.'gravatar.com/avatar.php?'
            .'gravatar_id='.md5(strtolower($Object->$Email))
            .'&amp;default='.urlencode(Asset(Gdn::Config('Plugins.Gravatar.DefaultAvatar', 'plugins/Gravatar/default.gif'), TRUE))
            .'&amp;size='.Gdn::Config('Garden.Thumbnail.Width', 40);
      }
		return $User;
   }
}
