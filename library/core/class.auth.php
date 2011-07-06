<?php if (!defined('APPLICATION')) exit();
      if (!C('Garden.Installed', FALSE) && !$ForceStart) return;
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */

class Gdn_Auth {
   /// PROPERTIES ///


   /// METHODS ///

   public function RegisterUrl($Target = '/') {
      Deprecated('Gdn_Auth::RegisterUrl()', 'RegisterUrl()');
      return '/entry/register?Target='.urldecode($Target);
   }

   public function SetIdentity($Value, $Persist = FALSE) {
      Deprecated(__Function__, 'Gdn_Session::Start()');
      Gdn::Session()->Start($Value, TRUE, $Persist);
   /**
    *
    * @return type Gdn_CookieIdentity
    */
   }

   public function SignInUrl($Target = '/') {
      Deprecated('Gdn_Auth::SignInUrl()', 'SignInUrl()');
      return '/entry/signin?Target='.urlencode($Target);
   }

   public function SignOutUrl($Target = '/') {
      Deprecated('Gdn_Auth::SignOutUrl()', 'SignOutUrl()');
      $Query = array('TransientKey' => Gdn::Session()->TransientKey(), 'Target' => $Target);
      return '/entry/signout?'.http_build_query($Query);
   }

   public function StartAuthenticator() {
      Deprecated('Gdn_Auth::StartAuthenticator()', 'Gdn_Session::Initialize()');
      Gdn::Session()->Initialize();
   }
}