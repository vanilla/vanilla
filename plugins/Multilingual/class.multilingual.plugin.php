<?php if (!defined('APPLICATION')) exit();
/**
 * Multilingual Plugin
 * 
 * @author Matt Lincoln Russell <lincoln@vanillaforums.com>
 * @copyright 2011 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Addons
 */

$PluginInfo['Multilingual'] = array(
   'Name' => 'Multilingual',
   'Description' => "Allows use of multiple languages. Users can select their preferred language via a link in the footer, and administrators may embed their forum in different languages in different places.",
   'Version' => '1.1',
   'RequiredApplications' => array('Vanilla' => '2.0.18'),
   'MobileFriendly' => TRUE,
   'Author' => "Matt Lincoln Russell",
   'AuthorEmail' => 'lincoln@vanillaforums.com',
   'AuthorUrl' => 'http://lincolnwebs.com'
);

/* Changelog
   1.0 - Make MobileFriendly //Lincoln 2012-01-13
   1.1 - Move locale setting to later in startup for Embed //Lincoln 2012-02-22
*/

/**
 * Allows multiple locales to work in Vanilla.
 *
 * You can trigger an alternate locale by adding 'locale' in the query string,
 * setting var vanilla_embed_locale in an embedded forum, or selecting one of the 
 * language links added to the footer. User-selected locale takes precedence.
 * The selected locale is stored in the session. If it is user-selected AND the
 * user is logged in, it is stored in UserMeta.
 *
 * @example http://example.com/discussions?locale=de-DE
 * @example <script>var vanilla_embed_locale = 'de-DE';</script>
 */
class MultilingualPlugin extends Gdn_Plugin {
   /**
    * Set user's preferred locale. 
    *
    * Moved event from AppStart to AfterAnalyzeRequest to allow Embed to set P3P header first.
    */
   public function Gdn_Dispatcher_AfterAnalyzeRequest_Handler($Sender) {
      // Set user preference
      if ($TempLocale = $this->GetAlternateLocale()) {
         Gdn::Locale()->Set($TempLocale, Gdn::ApplicationManager()->EnabledApplicationFolders(), Gdn::PluginManager()->EnabledPluginFolders());
      }
   }
   
   /**
    * Build footer link to change locale.
    */
   public function BuildLocaleLink($Name, $UrlCode) {
      $Url = 'profile/setlocale/'.$UrlCode.'/'.Gdn::Session()->TransientKey();
      return Wrap(Anchor(ucwords($Name), $Url), 'span', array('class' => 'LocaleOption'));
   }
   
   /**
    * Show alternate locale options in Foot.
    */
   public function Base_Render_Before($Sender) {
      // Not in Dashboard
      // Block guests until guest sessions are restored
      if ($Sender->MasterView == 'admin' || !CheckPermission('Garden.SignIn.Allow'))
         return;
      
      // Get locales
      $LocaleModel = new LocaleModel();
      $Options = $LocaleModel->EnabledLocalePacks();
      $Locales = $LocaleModel->AvailableLocalePacks();
            
      // Build & add links
      $Links = T('Change language').': ';
      foreach ($Options as $Slug => $Code) {
         $LocaleInfo = GetValue($Slug, $Locales);
         $LocaleName = str_replace(' Transifex', '' , GetValue('Name', $LocaleInfo)); // No 'Transifex' in names, pls.
         $Links .= $this->BuildLocaleLink($LocaleName, $Code);
      }
      
      // Hackily add English option
      $Links .= $this->BuildLocaleLink('English', 'en-CA');
      
      $LocaleLinks = Wrap($Links, 'div', array('class' => 'LocaleOptions'));
      $Sender->AddAsset('Foot', $LocaleLinks);
      
      // Add a simple style
      $Sender->AddAsset('Head', '<style>.LocaleOption { padding-left: 10px; } .Dashboard .LocaleOptions { display: none; }</style>');
   }
   
   /**
    * Get user preference or queried locale.
    */
   protected function GetAlternateLocale() {
      $Locale = FALSE;
      
      // User preference
      if (!$Locale) {
         $Locale = $this->GetUserMeta(Gdn::Session()->UserID, 'Locale', FALSE);
         $Locale = GetValue('Plugin.Multilingual.Locale', $Locale, FALSE);
      }
      // Query string
      if (!$Locale) {
         $Locale = $this->ValidateLocale(GetValue('locale', $_GET, FALSE));
         if ($Locale)
            Gdn::Session()->Stash('Locale', $Locale);
      }
      // Session
      if (!$Locale) {
         $Locale = Gdn::Session()->Stash('Locale', '', FALSE);
      }
      
      return $Locale;
   }
   
   /**
    * Allow user to set their preferred locale via link-click.
    */
   public function ProfileController_SetLocale_Create($Sender, $Args = array()) {
      // Check intent
      if (isset($Args[1]))
         Gdn::Session()->ValidateTransientKey($Args[1]);
      else Redirect($_SERVER['HTTP_REFERER']);
      
      // If we got a valid locale, save their preference
      if (isset($Args[0])) {
         $Locale = $this->ValidateLocale($Args[0]);
         if ($Locale) {
            Gdn::Session()->Stash('Locale', $Locale);
            if (CheckPermission('Garden.SignIn.Allow'))
               $this->SetUserMeta(Gdn::Session()->UserID, 'Locale', $Locale);
         }
      }
      
      // Back from whence we came
      Redirect($_SERVER['HTTP_REFERER']);
   }
   
   /**
    * Confirm selected locale is valid and available.
    * 
    * @param string $Locale Locale code.
    * @return $Locale or FALSE.
    */
   protected function ValidateLocale($Locale) {
      $LocaleModel = new LocaleModel();
      $Options = $LocaleModel->EnabledLocalePacks();
      $Options['English'] = 'en-CA'; // Hackily include the default
      return (in_array($Locale, $Options)) ? $Locale : FALSE;
   }
}