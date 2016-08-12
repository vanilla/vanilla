<?php
/**
 * Smart abstraction layer.
 *
 * @author Mark O'Sullivan <markm@vanillaforums.com>
 * @copyright 2009-2016 Vanilla Forums Inc.
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
     * @param string $Path
     * @param Gdn_Controller $Controller
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
        $Smarty->assign('BodyID', htmlspecialchars($BodyIdentifier));
        //$Smarty->assign('Config', Gdn::Config());

        // Assign some information about the user.
        $Session = Gdn::session();
        if ($Session->isValid()) {
            $User = array(
                'Name' => htmlspecialchars($Session->User->Name),
                'Photo' => '',
                'CountNotifications' => (int)val('CountNotifications', $Session->User, 0),
                'CountUnreadConversations' => (int)val('CountUnreadConversations', $Session->User, 0),
                'SignedIn' => true);

            $Photo = $Session->User->Photo;
            if ($Photo) {
                if (!isUrl($Photo)) {
                    $Photo = Gdn_Upload::url(changeBasename($Photo, 'n%s'));
                }
            } else {
                $Photo = UserModel::getDefaultAvatarUrl($Session->User);
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
            'Lang' => str_replace('_', '-', Gdn::locale()->language(true)) // mirrors html5 lang attribute
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
        // 2016-07-07 Linc: Request used to return blank for homepage.
        // Now it returns defaultcontroller. This restores BC behavior.
        $isHomepage = val('isHomepage', $Controller->Data);
        $Path = ($isHomepage) ? "" : Gdn::request()->path();
        $Smarty->assign('Path', $Path);
        $Smarty->assign('Homepage', $isHomepage); // true/false

        // Assign the controller data last so the controllers override any default data.
        $Smarty->assign($Controller->Data);

        $security = new SmartySecurityVanilla($Smarty);

        $security->php_handling = Smarty::PHP_REMOVE;
        $security->allow_constants = false;
        $security->allow_super_globals = false;
        $security->streams = null;

        $security->setPhpFunctions(array_merge($security->php_functions, [
            'array', // Yes, Smarty really blocks this.
            'category',
            'checkPermission',
            'inSection',
            'inCategory',
            'ismobile',
            'multiCheckPermission',
            'getValue',
            'setValue',
            'url',
            'useragenttype'
        ]));

        $security->php_modifiers = array_merge(
            $security->php_functions,
            array('sprintf')
        );

        $Smarty->enableSecurity($security);

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

        $Smarty->setTemplateDir(dirname($Path));
        $Smarty->display($Path, null, $CompileID);
    }

    /**
     *
     *
     * @return Smarty The smarty object used for rendering.
     */
    public function smarty() {
        if (is_null($this->_Smarty)) {
            $Smarty = new SmartyBC();

            $Smarty->setCacheDir(PATH_CACHE.'/Smarty/cache');
            $Smarty->setCompileDir(PATH_CACHE.'/Smarty/compile');
            $Smarty->addPluginsDir(PATH_LIBRARY.'/vendors/SmartyPlugins');

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
