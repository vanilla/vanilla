<?php
/**
 * Smart abstraction layer.
 *
 * @author Mark O'Sullivan <markm@vanillaforums.com>
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0
 */

/**
 * Vanilla implementation of Smarty templating engine.
 */
class Gdn_Smarty {

    /** @var Smarty The smarty object used for the template. */
    protected $_Smarty = null;

    /**
     *
     *
     * @param $Path
     * @param $Controller
     */
    public function init($Path, $Controller) {
        $Smarty = $this->smarty();

        // Get a friendly name for the controller.
        $ControllerName = get_class($Controller);
        if (StringEndsWith($ControllerName, 'Controller', true)) {
            $ControllerName = substr($ControllerName, 0, -10);
        }

        // Get an ID for the body.
        $BodyIdentifier = strtolower($Controller->ApplicationFolder.'_'.$ControllerName.'_'.Gdn_Format::alphaNumeric(strtolower($Controller->RequestMethod)));
        $Smarty->assign('BodyID', $BodyIdentifier);
        //$Smarty->assign('Config', Gdn::Config());

        // Assign some information about the user.
        $Session = Gdn::session();
        if ($Session->isValid()) {
            $User = array(
                'Name' => $Session->User->Name,
                'Photo' => '',
                'CountNotifications' => (int)val('CountNotifications', $Session->User, 0),
                'CountUnreadConversations' => (int)val('CountUnreadConversations', $Session->User, 0),
                'SignedIn' => true);

            $Photo = $Session->User->Photo;
            if ($Photo) {
                if (!IsUrl($Photo)) {
                    $Photo = Gdn_Upload::Url(ChangeBasename($Photo, 'n%s'));
                }
            } else {
                if (function_exists('UserPhotoDefaultUrl')) {
                    $Photo = UserPhotoDefaultUrl($Session->User, 'ProfilePhoto');
                } elseif ($ConfigPhoto = C('Garden.DefaultAvatar'))
                    $Photo = Gdn_Upload::url($ConfigPhoto);
                else {
                    $Photo = Asset('/applications/dashboard/design/images/defaulticon.png', true);
                }
            }
            $User['Photo'] = $Photo;
        } else {
            $User = false; /*array(
            'Name' => '',
            'CountNotifications' => 0,
            'SignedIn' => FALSE);*/
        }
        $Smarty->assign('User', $User);

        // Make sure that any datasets use arrays instead of objects.
        foreach ($Controller->Data as $Key => $Value) {
            if ($Value instanceof Gdn_DataSet) {
                $Controller->Data[$Key] = $Value->resultArray();
            } elseif ($Value instanceof stdClass) {
                $Controller->Data[$Key] = (array)$Value;
            }
        }

        $BodyClass = val('CssClass', $Controller->Data, '', true);
        $Sections = Gdn_Theme::section(null, 'get');
        if (is_array($Sections)) {
            foreach ($Sections as $Section) {
                $BodyClass .= ' Section-'.$Section;
            }
        }

        $Controller->Data['BodyClass'] = $BodyClass;

        // Set the current locale for themes to take advantage of.
        $Locale = Gdn::locale()->Locale;
        $CurrentLocale = array(
            'Key' => $Locale,
            'Lang' => str_replace('_', '-', $Locale) // mirrors html5 lang attribute
        );
        if (class_exists('Locale')) {
            $CurrentLocale['Language'] = Locale::getPrimaryLanguage($Locale);
            $CurrentLocale['Region'] = Locale::getRegion($Locale);
            $CurrentLocale['DisplayName'] = Locale::getDisplayName($Locale, $Locale);
            $CurrentLocale['DisplayLanguage'] = Locale::getDisplayLanguage($Locale, $Locale);
            $CurrentLocale['DisplayRegion'] = Locale::getDisplayRegion($Locale, $Locale);
        }
        $Smarty->assign('CurrentLocale', $CurrentLocale);

        $Smarty->assign('Assets', (array)$Controller->Assets);
        $Smarty->assign('Path', Gdn::request()->path());

        // Assign the controller data last so the controllers override any default data.
        $Smarty->assign($Controller->Data);

        $Smarty->Controller = $Controller; // for smarty plugins
        $Smarty->security = true;

        $Smarty->security_settings['IF_FUNCS'] = array_merge(
            $Smarty->security_settings['IF_FUNCS'],
            array('Category', 'CheckPermission', 'InSection', 'InCategory', 'MultiCheckPermission', 'GetValue', 'SetValue', 'Url')
        );

        $Smarty->security_settings['MODIFIER_FUNCS'] = array_merge(
            $Smarty->security_settings['MODIFIER_FUNCS'],
            array('sprintf')
        );

        $Smarty->secure_dir = array($Path);
    }

    /**
     * Render the given view.
     *
     * @param string $Path The path to the view's file.
     * @param Controller $Controller The controller that is rendering the view.
     */
    public function render($Path, $Controller) {
        $Smarty = $this->smarty();
        $this->init($Path, $Controller);
        $CompileID = $Smarty->compile_id;
        if (defined('CLIENT_NAME')) {
            $CompileID = CLIENT_NAME;
        }

        $Smarty->template_dir = dirname($Path);
        $Smarty->display($Path, null, $CompileID);
    }

    /**
     *
     *
     * @return Smarty The smarty object used for rendering.
     */
    public function smarty() {
        if (is_null($this->_Smarty)) {
            $Smarty = Gdn::factory('Smarty');

            $Smarty->cache_dir = PATH_CACHE.DS.'Smarty'.DS.'cache';
            $Smarty->compile_dir = PATH_CACHE.DS.'Smarty'.DS.'compile';
            $Smarty->plugins_dir[] = PATH_LIBRARY.DS.'vendors'.DS.'SmartyPlugins';

//         Gdn::PluginManager()->Trace = TRUE;
            Gdn::pluginManager()->callEventHandlers($Smarty, 'Gdn_Smarty', 'Init');

            $this->_Smarty = $Smarty;
        }
        return $this->_Smarty;
    }

    /**
     * See if the provided template causes any errors.
     *
     * @param type $Path Path of template file to test.
     * @return boolean TRUE if template loads successfully.
     */
    public function testTemplate($Path) {
        $Smarty = $this->smarty();
        $this->init($Path, Gdn::controller());
        $CompileID = $Smarty->compile_id;
        if (defined('CLIENT_NAME')) {
            $CompileID = CLIENT_NAME;
        }

        $Return = true;
        try {
            $Result = $Smarty->fetch($Path, null, $CompileID);
            // echo Wrap($Result, 'textarea', array('style' => 'width: 900px; height: 400px;'));
            $Return = ($Result == '' || strpos($Result, '<title>Fatal Error</title>') > 0 || strpos($Result, '<h1>Something has gone wrong.</h1>') > 0) ? false : true;
        } catch (Exception $ex) {
            $Return = false;
        }
        return $Return;
    }
}
