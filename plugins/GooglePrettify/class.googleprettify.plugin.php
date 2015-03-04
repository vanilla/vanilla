<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */

// Define the plugin:
$PluginInfo['GooglePrettify'] = array(
   'Name' => 'Syntax Prettifier',
   'Description' => 'Adds pretty syntax highlighting to code in discussions and tab support to the comment box. This is a great addon for communities that support programmers and designers.',
   'Version' => '1.2',
   'RequiredApplications' => array('Vanilla' => '2.0.18'),
   'MobileFriendly' => TRUE,
   'Author' => 'Todd Burry',
   'AuthorEmail' => 'todd@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.org/profile/todd',
   'SettingsUrl' => '/dashboard/settings/googleprettify',
   'SettingsPermission' => 'Garden.Settings.Manage',
);

// Changelog
// v1.1 Add Tabby, docs/cleanup  -Lincoln, Aug 2012

class GooglePrettifyPlugin extends Gdn_Plugin {
	/**
	 * Add Prettify to page text.
	 */
	public function AddPretty($Sender) {
		$Sender->Head->AddTag('script', array('type' => 'text/javascript', '_sort' => 100), $this->GetJs());
      $Sender->AddJsFile('prettify.js', 'plugins/GooglePrettify', array('_sort' => 101));
      if ($Language = C('Plugins.GooglePrettify.Language')) {
         $Sender->AddJsFile("lang-$Language.js", 'plugins/GooglePrettify', array('_sort' => 102));
      }
	}
	
	/**
	 * Add Tabby to a page's text areas.
	 */
	public function AddTabby($Sender) {
		if (C('Plugins.GooglePrettify.UseTabby', FALSE)) {
      	$Sender->AddJsFile('jquery.textarea.js', 'plugins/GooglePrettify');
      	$Sender->Head->AddTag('script', array('type' => 'text/javascript', '_sort' => 100), 'jQuery(document).ready(function () {
     $("textarea").livequery(function () {$("textarea").tabby();})
});');
      }
	}
   
   /**
    * Prettify script initializer.
    * 
    * @return string
    */
   public function GetJs() {
      $Class = '';
      if (C('Plugins.GooglePrettify.LineNumbers'))
         $Class .= ' linenums';
      if ($Language = C('Plugins.GooglePrettify.Language')) {
         $Class .= " lang-$Language";
      }
      
      $Result = "jQuery(document).ready(function($) {
         var pp = false;

         $('.Message').livequery(function () { 
            $('pre', this).addClass('prettyprint$Class');
            if (pp)
               prettyPrint();
            $('pre', this).removeClass('prettyprint')
         });
         
         prettyPrint();
         pp = true;
      });";
      return $Result;
   }
      
   public function AssetModel_StyleCss_Handler($Sender) {
      if (!C('Plugins.GooglePrettify.NoCssFile'))
         $Sender->AddCssFile('prettify.css', 'plugins/GooglePrettify');
   }
   
   public function AssetModel_GenerateETag_Handler($Sender, $Args) {
      if (!C('Plugins.GooglePrettify.NoCssFile'))
         $Args['ETagData']['Plugins.GooglePrettify.NoCssFile'] = TRUE;
   }
   
   /**
    * Add Prettify formatting to discussions.
    * 
    * @param DiscussionController $Sender 
    */
   public function DiscussionController_Render_Before($Sender) {
      $this->AddPretty($Sender);
   	$this->AddTabby($Sender);
   }
   
   /**
    * Add Tabby to post textarea.
    * 
    * @param PostController $Sender 
    */
   public function PostController_Render_Before($Sender) {
   	$this->AddPretty($Sender);
   	$this->AddTabby($Sender);
   }
   
   /**
    * Settings page.
    * 
    * @param unknown_type $Sender
    * @param unknown_type $Args
    */
   public function SettingsController_GooglePrettify_Create($Sender, $Args) {
      $Cf = new ConfigurationModule($Sender);
      $CssUrl = Asset('/plugins/GooglePrettify/design/prettify.css', TRUE);
      
      $Languages = array(
         'apollo' => 'apollo',
         'clj' => 'clj',
         'css' => 'css',
         'go' => 'go',
         'hs' => 'hs',
         'lisp' => 'lisp',
         'lua' => 'lua',
         'ml' => 'ml',
         'n' => 'n',
         'proto' => 'proto',
         'scala' => 'scala',
         'sql' => 'sql',
         'text' => 'tex',
         'vb' => 'visual basic',
         'vhdl' => 'vhdl',
         'wiki' => 'wiki',
         'xq' => 'xq',
         'yaml' => 'yaml'
         );
      
      $Cf->Initialize(array(
          'Plugins.GooglePrettify.LineNumbers' => array('Control' => 'CheckBox', 'Description' => 'Add line numbers to source code.', 'Default' => FALSE),
          'Plugins.GooglePrettify.NoCssFile' => array('Control' => 'CheckBox', 'LabelCode' => 'Exclude Default CSS File', 'Description' => "If you want to define syntax highlighting in your custom theme you can disable the <a href='$CssUrl'>default css</a> with this setting.", 'Default' => FALSE),
          'Plugins.GooglePrettify.UseTabby' => array('Control' => 'CheckBox', 'LabelCode' => 'Allow Tab Characters', 'Description' => "If users enter a lot of source code then enable this setting to make the tab key enter a tab instead of skipping to the next control.", 'Default' => FALSE),
          'Plugins.GooglePrettify.Language' => array('Control' => 'DropDown', 'Items' => $Languages, 'Options' => array('IncludeNull' => TRUE),
             'Description' => 'We try our best to guess which language you are typing in, but if you have a more obscure language you can force all highlighting to be in that language. (Not recommended)')
      ));

      $Sender->AddSideMenu();
      $Sender->SetData('Title', T('Syntax Prettifier Settings'));
      $Cf->RenderAll();
   }
}