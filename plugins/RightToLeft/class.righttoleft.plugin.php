<?php

if (!defined('APPLICATION'))
   exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 */


// Define the plugin:
$PluginInfo['RightToLeft'] = array(
    'Name' => 'Right to Left (RTL) Support',
    'Description' => "Adds a css stub to pages with some tweaks for right-to-left (rtl) languages and adds 'rtl' to body css class.",
    'Version' => '1.0.1',
    'RequiredApplications' => array('Vanilla' => '2.0.18'),
    'MobileFriendly' => TRUE,
    'Author' => 'Becky Van Bussel',
    'AuthorEmail' => 'becky@vanillaforums.com',
    'AuthorUrl' => 'http://www.vanillaforums.org/'
);

class RightToLeftPlugin extends Gdn_Plugin {

   /**
    * @var array $rtlLocales List the locales that are rtl.
    */
    protected $rtlLocales = array('ar', 'fa', 'he', 'ku', 'ps', 'sd', 'ug', 'ur', 'yi');

   /**
    * Add the rtl stylesheets to the page.
    *
    * The rtl stylesheets should always be added separately so that they aren't combined with other stylesheets when
    * a non-rtl language is still being displayed.
    *
    * @param Gdn_Controller $Sender
    */
    public function Base_Render_Before(&$Sender) {
        $currentLocale = substr(Gdn::Locale()->Current(), 0, 2);

        if (in_array($currentLocale, $this->rtlLocales)) {
            if (InSection('Dashboard')) {
               $Sender->AddCssFile('admin_rtl.css', 'plugins/RightToLeft');
            } else {
               $Sender->AddCssFile('style_rtl.css', 'plugins/RightToLeft');
            }

            $Sender->CssClass .= ' rtl';
       }
    }
}
