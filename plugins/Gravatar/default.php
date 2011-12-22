<?php if (!defined('APPLICATION')) exit();

// Define the plugin:
$PluginInfo['Gravatar'] = array(
   'Name' => 'Gravatar',
   'Description' => 'Implements Gravatar avatars for all users who have not uploaded their own custom profile picture & icon.',
   'Version' => '1.4',
   'Author' => "Mark O'Sullivan",
   'AuthorEmail' => 'mark@vanillaforums.com',
   'AuthorUrl' => 'http://vanillaforums.com',
	'MobileFriendly' => TRUE
);

// 1.1 Fixes - Used GetValue to retrieve array props instead of direct references
// 1.2 Fixes - Make Gravatar work with the mobile theme
// 1.3 Fixes - Changed UserBuilder override to also accept an array of user info
// 1.4 Change - Lets you chain Vanillicon as the default by setting Plugins.Gravatar.UseVanillicon in config.

class GravatarPlugin extends Gdn_Plugin {
   public function Setup() {
      // No setup required.
   }
}

if (!function_exists('UserPhotoDefaultUrl')) {
   function UserPhotoDefaultUrl($User) {
      $Email = GetValue('Email', $User);
      $HTTPS = GetValue('HTTPS', $_SERVER, '');
      $Protocol =  (strlen($HTTPS) || GetValue('SERVER_PORT', $_SERVER) == 443) ? 'https://secure.' : 'http://www.';

      $Url = $Protocol.'gravatar.com/avatar.php?'
         .'gravatar_id='.md5(strtolower($Email))
         .'&amp;size='.C('Garden.Thumbnail.Width', 50);
         
      if (C('Plugins.Gravatar.UseVanillicon', FALSE))
         $Url .= '&amp;default='.urlencode(Asset('http://vanillicon.com/'.md5($User->Email).'.png'));
      else
         $Url .= '&amp;default='.urlencode(Asset(C('Plugins.Gravatar.DefaultAvatar', 'plugins/Gravatar/default.gif'), TRUE));
      
      return $Url;
   }
}
