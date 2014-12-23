<?php if (!defined('APPLICATION')) exit();

/**
 * Signatures Plugin
 *
 * This plugin allows users to maintain a 'Signature' which is automatically
 * appended to all discussions and comments they make.
 *
 * Changes:
 *  1.0     Initial release
 *  1.4     Add SimpleAPI hooks
 *  1.4.1   Allow self-API access
 *  1.5     Improve "Hide Images"
 *  1.5.1   Improve permission checking granularity
 *  1.5.3-5 Disallow images plugin-wide from dashboard.
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Addons
 */

$PluginInfo['Signatures'] = array(
   'Name' => 'Signatures',
   'Description' => 'Users may create custom signatures that appear after each of their comments.',
   'Version' => '1.5.6',
   'RequiredApplications' => array('Vanilla' => '2.0.18'),
   'RequiredTheme' => FALSE,
   'RequiredPlugins' => FALSE,
   'HasLocale' => TRUE,
   'RegisterPermissions' => array('Plugins.Signatures.Edit' => 1),
   'Author' => "Tim Gunter",
   'AuthorEmail' => 'tim@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.com',
   'MobileFriendly' => FALSE,
   'SettingsUrl' => '/settings/signatures',
   'SettingsPermission' => 'Garden.Settings.Manage'
);

class SignaturesPlugin extends Gdn_Plugin {
   public $Disabled = FALSE;

   /**
    * @var array List of config settings can be overridden by sessions in other plugins
    */
   private $overriddenConfigSettings = array('MaxNumberImages', 'MaxLength');


   /**
    * Add mapper methods
    *
    * @param SimpleApiPlugin $Sender
    */
   public function SimpleApiPlugin_Mapper_Handler($Sender) {
      switch ($Sender->Mapper->Version) {
         case '1.0':
            $Sender->Mapper->AddMap(array(
               'signature/get'         => 'dashboard/profile/signature/modify',
               'signature/set'         => 'dashboard/profile/signature/modify',
            ), NULL, array(
               'signature/get'         => array('Signature'),
               'signature/set'         => array('Success'),
            ));
            break;
      }
   }

   /**
    * Add "Signature Settings" to profile edit mode side menu.
    *
    * @param $Sender
    */
   public function ProfileController_AfterAddSideMenu_Handler($Sender) {
      if (!CheckPermission('Garden.SignIn.Allow'))
         return;

      $SideMenu = $Sender->EventArguments['SideMenu'];
      $ViewingUserID = Gdn::Session()->UserID;

      if ($Sender->User->UserID == $ViewingUserID) {
         $SideMenu->AddLink('Options', Sprite('SpSignatures').' '.T('Signature Settings'), '/profile/signature', FALSE, array('class' => 'Popup'));
      } else {
         $SideMenu->AddLink('Options', Sprite('SpSignatures').' '.T('Signature Settings'), UserUrl($Sender->User, '', 'signature'), array('Garden.Users.Edit','Moderation.Signatures.Edit'), array('class' => 'Popup'));
      }
   }

   /**
    * Add "Signature Settings" to Profile Edit button group.
    * Only do this if they cannot edit profiles because otherwise they can't navigate there.
    *
    * @param $Sender
    */
   public function ProfileController_BeforeProfileOptions_Handler($Sender, $Args) {
      $CanEditProfiles = CheckPermission('Garden.Users.Edit') || CheckPermission('Moderation.Profiles.Edit');
      if (CheckPermission('Moderation.Signatures.Edit') && !$CanEditProfiles)
         $Args['ProfileOptions'][] = array('Text' => Sprite('SpSignatures').' '.T('Signature Settings'), 'Url' => UserUrl($Sender->User, '', 'signature'));
   }

   /**
    * Profile settings
    *
    * @param ProfileController $Sender
    */
   public function ProfileController_Signature_Create($Sender) {
      $Sender->Permission('Garden.SignIn.Allow');
      $Sender->Title('Signature Settings');

      $this->Dispatch($Sender);
   }


   public function Controller_Index($Sender) {
      $Sender->Permission(array(
         'Garden.Profiles.Edit'
      ));

      $Args = $Sender->RequestArgs;
      if (sizeof($Args) < 2)
         $Args = array_merge($Args, array(0,0));
      elseif (sizeof($Args) > 2)
         $Args = array_slice($Args, 0, 2);

      list($UserReference, $Username) = $Args;

      $canEditSignatures = CheckPermission('Plugins.Signatures.Edit');

      // Normalize no image config setting
      if (C('Plugins.Signatures.MaxNumberImages') === 0 || C('Plugins.Signatures.MaxNumberImages') === '0') {
         SaveToConfig('Plugins.Signatures.MaxNumberImages', 'None');
      }

      $Sender->GetUserInfo($UserReference, $Username);
      $UserPrefs = Gdn_Format::Unserialize($Sender->User->Preferences);
      if (!is_array($UserPrefs))
         $UserPrefs = array();

      $Validation = new Gdn_Validation();
      $ConfigurationModel = new Gdn_ConfigurationModel($Validation);
      $ConfigArray = array(
         'Plugin.Signatures.Sig'          => NULL,
         'Plugin.Signatures.HideAll'      => NULL,
         'Plugin.Signatures.HideImages'   => NULL,
         'Plugin.Signatures.HideMobile'   => NULL,
         'Plugin.Signatures.Format'       => NULL
      );
      $SigUserID = $ViewingUserID = Gdn::Session()->UserID;

      if ($Sender->User->UserID != $ViewingUserID) {
         $Sender->Permission(array('Garden.Users.Edit','Moderation.Signatures.Edit'), FALSE);
         $SigUserID = $Sender->User->UserID;
         $canEditSignatures = true;
      }

      $Sender->SetData('CanEdit', $canEditSignatures);
      $Sender->SetData('Plugin-Signatures-ForceEditing', ($SigUserID == Gdn::Session()->UserID) ? FALSE : $Sender->User->Name);
      $UserMeta = $this->GetUserMeta($SigUserID, '%');

      if ($Sender->Form->AuthenticatedPostBack() === FALSE && is_array($UserMeta))
         $ConfigArray = array_merge($ConfigArray, $UserMeta);

      $ConfigurationModel->SetField($ConfigArray);

      // Set the model on the form.
      $Sender->Form->SetModel($ConfigurationModel);

      $Data = $ConfigurationModel->Data;
      $Sender->SetData('Signature', $Data);

      $this->SetSignatureRules($Sender);

      // If seeing the form for the first time...
      if ($Sender->Form->IsPostBack() === FALSE) {
         $Data['Body'] = GetValue('Plugin.Signatures.Sig', $Data);
         $Data['Format'] = GetValue('Plugin.Signatures.Format', $Data);

         // Apply the config settings to the form.
         $Sender->Form->SetData($Data);
      } else {
         $Values = $Sender->Form->FormValues();

         if ($canEditSignatures) {
            $Values['Plugin.Signatures.Sig'] = GetValue('Body', $Values, NULL);
            $Values['Plugin.Signatures.Format'] = GetValue('Format', $Values, NULL);
         }

         //$this->StripLineBreaks($Values['Plugin.Signatures.Sig']);

         $FrmValues = array_intersect_key($Values, $ConfigArray);

         if (sizeof($FrmValues)) {

            if (!GetValue($this->MakeMetaKey('Sig'), $FrmValues)) {
               // Delete the signature.
               $FrmValues[$this->MakeMetaKey('Sig')] = NULL;
               $FrmValues[$this->MakeMetaKey('Format')] = NULL;
            }

            $this->CrossCheckSignature($Values, $Sender);

            if ($Sender->Form->ErrorCount() == 0) {
               foreach ($FrmValues as $UserMetaKey => $UserMetaValue) {
                  $Key = $this->TrimMetaKey($UserMetaKey);

                  switch ($Key) {
                     case 'Format':
                        if (strcasecmp($UserMetaValue, 'Raw') == 0)
                           $UserMetaValue = NULL; // don't allow raw signatures.
                     break;
                  }

                  $this->SetUserMeta($SigUserID, $Key, $UserMetaValue);
               }
               $Sender->InformMessage(T("Your changes have been saved."));
            }
         }
      }

      $Sender->Render('signature', '', 'plugins/Signatures');
   }

   /**
    * Checks signature against constraints set in config settings,
    * and executes the external ValidateSignature function, if it exists.
    *
    * @param $Values Signature settings form values
    * @param $Sender Controller
    */
   public function CrossCheckSignature($Values, &$Sender) {
      $this->CheckSignatureLength($Values, $Sender);
      $this->CheckNumberOfImages($Values, $Sender);

      // Validate the signature.
      if (function_exists('ValidateSignature')) {
         $Sig = trim(GetValue('Plugin.Signatures.Sig', $Values));
         if (ValidateRequired($Sig) && !ValidateSignature($Sig, GetValue('Plugin.Signatures.Format', $Values))) {
            $Sender->Form->AddError('Signature invalid.');
         }
      }
   }

   /**
    * Checks signature length against Plugins.Signatures.MaxLength
    *
    * @param $Values Signature settings form values
    * @param $Sender Controller
    */
   public function CheckSignatureLength($Values, &$Sender) {
      if (C('Plugins.Signatures.MaxLength') && C('Plugins.Signatures.MaxLength') > 0) {
         $Sig = Gdn_Format::To($Values['Plugin.Signatures.Sig'], $Sender->Form->GetFormValue('Format'));
         $TextValue = html_entity_decode(trim(strip_tags($Sig)));

         // Validate the amount of text.
         if (strlen($TextValue) > C('Plugins.Signatures.MaxLength')) {
            $Sender->Form->AddError(sprintf(T('ValidateLength'), T('Signature'), (strlen($TextValue)-C('Plugins.Signatures.MaxLength'))));
         }
      }
   }

   /**
    * Checks number of images in signature against Plugins.Signatures.MaxNumberImages
    *
    * @param $Values Signature settings form values
    * @param $Sender Controller
    */
   public function CheckNumberOfImages($Values, &$Sender) {
      if (C('Plugins.Signatures.MaxNumberImages') && C('Plugins.Signatures.MaxNumberImages') !== 'Unlimited') {
         $max = C('Plugins.Signatures.MaxNumberImages');
         $numMatches = preg_match_all('/(<img|\[img.*\]|\!\[.*\])/i', $Values['Plugin.Signatures.Sig']);
         if (C('Plugins.Signatures.MaxNumberImages') === 'None' && $numMatches > 0) {
            $Sender->Form->AddError('Images not allowed');
         }
         else if ($numMatches > $max) {
            $Sender->Form->AddError('@'.FormatString('You are only allowed {maxImages,plural,%s image,%s images}.',
                  array('maxImages' => $max)));
         }
      }
   }

   public function SetSignatureRules(&$Sender) {
      $rules = array();
      $rulesParams = array();
      $imagesAllowed = true;



      if (C('Plugins.Signatures.MaxNumberImages', 'Unlimited') !== 'Unlimited') {
         if (C('Plugins.Signatures.MaxNumberImages') === 'None') {
            $rules[] = T('Images not allowed.');
            $imagesAllowed = false;
         } else {
            $rulesParams['maxImages'] = C('Plugins.Signatures.MaxNumberImages');
            $rules[] = FormatString(T('Use up to {maxImages,plural,%s image, %s images}.'), $rulesParams);
         }
      }
      if ($imagesAllowed && C('Plugins.Signatures.MaxImageHeight') && C('Plugins.Signatures.MaxImageHeight') > 0) {
         $rulesParams['maxImageHeight'] = C('Plugins.Signatures.MaxImageHeight');
         $rules[] = FormatString(T('Images will be scaled to a maximum height of {maxImageHeight}px.'), $rulesParams);

      }
      if (C('Plugins.Signatures.MaxLength') && C('Plugins.Signatures.MaxLength') > 0) {
         $rulesParams['maxLength'] = C('Plugins.Signatures.MaxLength');
         $rules[] = FormatString(T('Signatures can be up to {maxLength} characters long.'), $rulesParams);
      }

      $Sender->SetData('SignatureRules', implode(' ', $rules));
   }


         /**
    * Strips all line breaks from text
    *
    * @param string $Text
    * @param string $Delimiter
    */
   public function StripLineBreaks(&$Text, $Delimiter = ' ') {
      $Text = str_replace(array("\r\n", "\r"), "\n", $Text);
      $lines = explode("\n", $Text);
      $new_lines = array();
      foreach ($lines as $i => $line) {
         $line = trim($line);
         if(!empty($line)) {
            $new_lines[] = $line;
         }
      }
      $Text = implode($new_lines, $Delimiter);
   }

   public function StripFormatting() {

   }

   /*
    * API METHODS
    */

   public function Controller_Modify($Sender) {
      $Sender->DeliveryMethod(DELIVERY_METHOD_JSON);
      $Sender->DeliveryType(DELIVERY_TYPE_DATA);

      $UserID = Gdn::Request()->Get('UserID');
      if ($UserID != Gdn::Session()->UserID)
         $Sender->Permission(array('Garden.Users.Edit','Moderation.Signatures.Edit'), FALSE);

      $User = Gdn::UserModel()->GetID($UserID);
      if (!$User)
         throw new Exception("No such user '{$UserID}'", 404);

      $Translation = array(
         'Plugin.Signatures.Sig'          => 'Body',
         'Plugin.Signatures.Format'       => 'Format',
         'Plugin.Signatures.HideAll'      => 'HideAll',
         'Plugin.Signatures.HideImages'   => 'HideImages',
         'Plugin.Signatures.HideMobile'   => 'HideMobile'
      );

      $UserMeta = $this->GetUserMeta($UserID, '%');
      $SigData = array();
      foreach ($Translation as $TranslationField => $TranslationShortcut)
         $SigData[$TranslationShortcut] = GetValue($TranslationField, $UserMeta, NULL);

      $Sender->SetData('Signature', $SigData);

      if ($Sender->Form->IsPostBack()) {
         $Sender->SetData('Success', FALSE);

         // Validate the signature.
         if (function_exists('ValidateSignature')) {
            $Sig = $Sender->Form->GetFormValue('Body');
            $Format = $Sender->Form->GetFormValue('Format');
            if (ValidateRequired($Sig) && !ValidateSignature($Sig, $Format)) {
               $Sender->Form->AddError('Signature invalid.');
            }
         }

         if ($Sender->Form->ErrorCount() == 0) {
            foreach ($Translation as $TranslationField => $TranslationShortcut) {
               $UserMetaValue = $Sender->Form->GetValue($TranslationShortcut, NULL);
               if (is_null($UserMetaValue)) continue;

               if ($TranslationShortcut == 'Body' && empty($UserMetaValue))
                  $UserMetaValue = NULL;

               $Key = $this->TrimMetaKey($TranslationField);

               switch ($Key) {
                  case 'Format':
                     if (strcasecmp($UserMetaValue, 'Raw') == 0)
                        $UserMetaValue = NULL; // don't allow raw signatures.
                  break;
               }

               if ($Sender->Form->ErrorCount() == 0) {
                  $this->SetUserMeta($UserID, $Key, $UserMetaValue);
               }
            }
            $Sender->SetData('Success', TRUE);
         }
      }

      $Sender->Render();
   }

   protected function UserPreferences($SigKey = NULL, $Default = NULL) {
      static $UserSigData = NULL;
      if (is_null($UserSigData)) {
         $UserSigData = $this->GetUserMeta(Gdn::Session()->UserID, '%');

//         decho($UserSigData);
      }

      if (!is_null($SigKey))
         return GetValue($SigKey, $UserSigData, $Default);

      return $UserSigData;
   }

   protected function Signatures($Sender, $RequestUserID = NULL, $Default = NULL) {
      static $Signatures = NULL;

      if (is_null($Signatures)) {
         $Signatures = array();

         // Short circuit if not needed.
         if ($this->Hide()) return $Signatures;

         $Discussion = $Sender->Data('Discussion');
         $Comments = $Sender->Data('Comments');
         $UserIDList = array();

         if ($Discussion)
            $UserIDList[GetValue('InsertUserID', $Discussion)] = 1;

         if ($Comments && $Comments->NumRows()) {
            $Comments->DataSeek(-1);
            while ($Comment = $Comments->NextRow())
               $UserIDList[GetValue('InsertUserID', $Comment)] = 1;
         }

         if (sizeof($UserIDList)) {
            $DataSignatures = $this->GetUserMeta(array_keys($UserIDList), 'Sig');
            $Formats = (array)$this->GetUserMeta(array_keys($UserIDList), 'Format');

            if (is_array($DataSignatures)) {
               foreach ($DataSignatures as $UserID => $UserSig) {
                  $Sig = GetValue($this->MakeMetaKey('Sig'), $UserSig);
                  if (isset($Formats[$UserID])) {
                     $Format = GetValue($this->MakeMetaKey('Format'), $Formats[$UserID], C('Garden.InputFormatter'));
                  } else {
                     $Format = C('Garden.InputFormatter');
                  }

                  $Signatures[$UserID] = array($Sig, $Format);
               }
            }
         }

      }

      if (!is_null($RequestUserID))
         return GetValue($RequestUserID, $Signatures, $Default);

      return $Signatures;
   }


   /** Deprecated in 2.1. */
   public function Base_AfterCommentBody_Handler($Sender) {
      if ($this->Disabled)
         return;

      $this->DrawSignature($Sender);
   }

   /**
    * Add a custom signature style tag to enforce image height.
    *
    * @param Gdn_Control $sender
    * @param array $args
    */
   public function base_render_before($sender, $args) {
      if ($maxImageHeight = C('Plugins.Signatures.MaxImageHeight')) {
         $maxImageHeight = (int)$maxImageHeight;

         $style = <<<EOT
.Signature img, .UserSignature img {
   max-height: {$maxImageHeight}px !important;
}
EOT;

         $sender->Head->AddTag('style', array('_sort' => 100), $style);
      }
   }

   /** New call for 2.1. */
   public function DiscussionController_AfterDiscussionBody_Handler($Sender) {
      if ($this->Disabled)
         return;
      $this->DrawSignature($Sender);
   }

   protected function DrawSignature($Sender) {
      if ($this->Hide()) return;

      if (isset($Sender->EventArguments['Discussion']))
         $Data = $Sender->EventArguments['Discussion'];

      if (isset($Sender->EventArguments['Comment']))
         $Data = $Sender->EventArguments['Comment'];

      $SourceUserID = GetValue('InsertUserID', $Data);
      $User = Gdn::UserModel()->GetID($SourceUserID, DATASET_TYPE_ARRAY);
      if (GetValue('HideSignature', $User, FALSE))
         return;


      $Signature = $this->Signatures($Sender, $SourceUserID);

      if (is_array($Signature))
         list($Signature, $SigFormat) = $Signature;
      else
         $SigFormat = C('Garden.InputFormatter');

      if (!$SigFormat)
         $SigFormat = C('Garden.InputFormatter');

      $this->EventArguments = array(
         'UserID'    => $SourceUserID,
         'Signature' => &$Signature
      );
//      $this->FireEvent('BeforeDrawSignature');

      $SigClasses = '';
      if (!is_null($Signature)) {
         $HideImages = $this->UserPreferences('Plugin.Signatures.HideImages', FALSE);

         if ($HideImages)
            $SigClasses .= 'HideImages ';

         // Don't show empty sigs
         if ($Signature == '') {
            return;
         }

         // If embeds were disabled from the dashboard, temporarily set the
         // universal config to make sure no URLs are turned into embeds.
         if (!C('Plugins.Signatures.AllowEmbeds', true)) {
             $originalEnableUrlEmbeds = C('Garden.Format.DisableUrlEmbeds', false);
             SaveToConfig(array(
                'Garden.Format.DisableUrlEmbeds' => true
             ), null, array(
                'Save' => false
             ));
         }

         $UserSignature = Gdn_Format::To($Signature, $SigFormat)."<!-- $SigFormat -->";

         // Restore original config.
         if (!C('Plugins.Signatures.AllowEmbeds', true)) {
             SaveToConfig(array(
                'Garden.Format.DisableUrlEmbeds' => $originalEnableUrlEmbeds
             ), null, array(
                'Save' => false
             ));
         }

         $this->EventArguments = array(
            'UserID'    => $SourceUserID,
            'String' => &$UserSignature
         );

         $this->FireEvent('FilterContent');

         if ($UserSignature) {
            echo "<div class=\"Signature UserSignature {$SigClasses}\">{$UserSignature}</div>";
         }
      }
   }

   protected function Hide() {
      if ($this->Disabled)
         return TRUE;

      if (!Gdn::Session()->IsValid() && C('Plugins.Signatures.HideGuest'))
         return TRUE;

      if (strcasecmp(Gdn::Controller()->RequestMethod, 'embed') == 0 && C('Plugin.Signatures.HideEmbed', TRUE))
         return TRUE;

      if ($this->UserPreferences('Plugin.Signatures.HideAll', FALSE))
         return TRUE;

      if (IsMobile() && $this->UserPreferences('Plugin.Signatures.HideMobile', FALSE))
         return TRUE;

      return FALSE;
   }

   protected function _StripOnly($str, $tags, $stripContent = false) {
      $content = '';
      if(!is_array($tags)) {
         $tags = (strpos($str, '>') !== false ? explode('>', str_replace('<', '', $tags)) : array($tags));
         if(end($tags) == '') array_pop($tags);
      }
      foreach($tags as $tag) {
         if ($stripContent)
             $content = '(.+</'.$tag.'[^>]*>|)';
          $str = preg_replace('#</?'.$tag.'[^>]*>'.$content.'#is', '', $str);
      }
      return $str;
   }

   public function Setup() {
      // Nothing to do here!
   }

   public function Structure() {
      // Nothing to do here!
   }

   public function AssetModel_StyleCss_Handler($Sender) {
      $Sender->AddCssFile('signature.css', 'plugins/Signatures');
   }


   public function SettingsController_Signatures_Create($Sender) {
      $Sender->Permission('Garden.Settings.Manage');

      $Conf = new ConfigurationModule($Sender);
      $Conf->Initialize(array(
          'Plugins.Signatures.HideGuest' => array('Control' => 'CheckBox', 'LabelCode' => 'Hide signatures for guests'),
          'Plugins.Signatures.HideEmbed' => array('Control' => 'CheckBox', 'LabelCode' => 'Hide signatures on embedded comments', 'Default' => TRUE),
          'Plugins.Signatures.AllowEmbeds' => array('Control' => 'CheckBox', 'LabelCode' => 'Allow embedded content', 'Default' => true),
           //'Plugins.Signatures.TextOnly' => array('Control' => 'CheckBox', 'LabelCode' => '@'.sprintf(T('Enforce %s'), T('text-only'))),
          'Plugins.Signatures.Default.MaxNumberImages' => array('Control' => 'Dropdown', 'LabelCode' => '@'.sprintf(T('Max number of %s'), T('images')), 'Items' => array('Unlimited' => T('Unlimited'), 'None' => T('None'), 1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5)),
          'Plugins.Signatures.Default.MaxLength' => array('Control' => 'TextBox', 'Type' => 'int', 'LabelCode' => '@'.sprintf(T('Max %s length'), T('signature')), 'Options' => array('class' => 'InputBox SmallInput')),
          'Plugins.Signatures.MaxImageHeight' => array('Control' => 'TextBox', 'LabelCode' => '@'.sprintf(T('Max height of %s'), T('images'))." ".T('in pixels'), 'Options' => array('class' => 'InputBox SmallInput')),
      ));

      $this->SetConfigSettingsToDefault('Plugins.Signatures', $this->overriddenConfigSettings);

      $Sender->AddSideMenu();
      $Sender->SetData('Title', sprintf(T('%s Settings'), T('Signature')));
      $Sender->ConfigurationModule = $Conf;
      $Conf->RenderAll();
//      $Sender->Render('Settings', '', 'plugins/AmazonS3');
   }

   /**
    * Why do we need this? (i.e., Mantra for the function)
    * We retrieve the signature restraints from the config settings.
    * These are sometimes overridden by plugins (i.e., Ranks)
    * If we load the dashboard signature settings form from the config file,
    * we will get whatever session config settings are present, not
    * the default. As such, we've created default config variables that
    * populate the form, but we've got to transfer them over to the
    * config settings in use.
    *
    * Sets config settings to the default settings.
    *
    *
    * @param string $basename
    * @param array $settings
    *
    */
   public function SetConfigSettingsToDefault($basename, $settings) {
      foreach ($settings as $setting) {
         SaveToConfig($basename.'.'.$setting, C($basename.'.Default.'.$setting));
      }
   }
}
