<?php
/**
 * Smart abstraction layer.
 *
 * @author Mark O'Sullivan <markm@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
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
     * @param string $path
     * @param Gdn_Controller $controller
     */
    public function init($path, $controller) {
        $smarty = $this->smarty();

        // Get a friendly name for the controller.
        $controllerName = get_class($controller);
        if (stringEndsWith($controllerName, 'Controller', true)) {
            $controllerName = substr($controllerName, 0, -10);
        }

        // Get an ID for the body.
        $bodyIdentifier = strtolower($controller->ApplicationFolder.'_'.$controllerName.'_'.Gdn_Format::alphaNumeric(strtolower($controller->RequestMethod)));
        $smarty->assign('BodyID', htmlspecialchars($bodyIdentifier));
        //$Smarty->assign('Config', Gdn::config());

        // Assign some information about the user.
        $session = Gdn::session();
        if ($session->isValid()) {
            $user = [
                'Name' => htmlspecialchars($session->User->Name),
                'Photo' => '',
                'CountNotifications' => (int)val('CountNotifications', $session->User, 0),
                'CountUnreadConversations' => (int)val('CountUnreadConversations', $session->User, 0),
                'SignedIn' => true];

            $photo = $session->User->Photo;
            if ($photo) {
                if (!isUrl($photo)) {
                    $photo = Gdn_Upload::url(changeBasename($photo, 'n%s'));
                }
            } else {
                $photo = UserModel::getDefaultAvatarUrl($session->User);
            }
            $user['Photo'] = $photo;
        } else {
            $user = false; /*array(
            'Name' => '',
            'CountNotifications' => 0,
            'SignedIn' => FALSE);*/
        }
        $smarty->assign('User', $user);

        // Make sure that any datasets use arrays instead of objects.
        foreach ($controller->Data as $key => $value) {
            if ($value instanceof Gdn_DataSet) {
                $controller->Data[$key] = $value->resultArray();
            } elseif ($value instanceof stdClass) {
                $controller->Data[$key] = (array)$value;
            }
        }

        $bodyClass = val('CssClass', $controller->Data, '', true);
        $sections = Gdn_Theme::section(null, 'get');
        if (is_array($sections)) {
            foreach ($sections as $section) {
                $bodyClass .= ' Section-'.$section;
            }
        }

        $controller->Data['BodyClass'] = $bodyClass;

        // Set the current locale for themes to take advantage of.
        $locale = Gdn::locale()->Locale;
        $currentLocale = [
            'Key' => $locale,
            'Lang' => str_replace('_', '-', Gdn::locale()->language(true)) // mirrors html5 lang attribute
        ];
        if (class_exists('Locale')) {
            $currentLocale['Language'] = Locale::getPrimaryLanguage($locale);
            $currentLocale['Region'] = Locale::getRegion($locale);
            $currentLocale['DisplayName'] = Locale::getDisplayName($locale, $locale);
            $currentLocale['DisplayLanguage'] = Locale::getDisplayLanguage($locale, $locale);
            $currentLocale['DisplayRegion'] = Locale::getDisplayRegion($locale, $locale);
        }
        $smarty->assign('CurrentLocale', $currentLocale);

        $smarty->assign('Assets', (array)$controller->Assets);
        // 2016-07-07 Linc: Request used to return blank for homepage.
        // Now it returns defaultcontroller. This restores BC behavior.
        $isHomepage = val('isHomepage', $controller->Data);
        $path = ($isHomepage) ? "" : Gdn::request()->path();
        $smarty->assign('Path', $path);
        $smarty->assign('Homepage', $isHomepage); // true/false

        // Assign the controller data last so the controllers override any default data.
        $smarty->assign($controller->Data);

        $security = new SmartySecurityVanilla($smarty);

        $security->php_handling = Smarty::PHP_REMOVE;
        $security->allow_constants = false;
        $security->allow_super_globals = false;
        $security->streams = null;

        $security->setPhpFunctions(array_merge($security->php_functions, [
            'array', // Yes, Smarty really blocks this.
            'category',
            'categoryUrl',
            'checkPermission',
            'commentUrl',
            'discussionUrl',
            'inSection',
            'inCategory',
            'ismobile',
            'multiCheckPermission',
            'getValue',
            'setValue',
            'url',
            'useragenttype',
            'userUrl',
        ]));

        $security->php_modifiers = array_merge(
            $security->php_functions,
            ['sprintf']
        );

        $smarty->enableSecurity($security);

    }

    /**
     * Render the given view.
     *
     * @param string $path The path to the view's file.
     * @param Controller $controller The controller that is rendering the view.
     */
    public function render($path, $controller) {
        $smarty = $this->smarty();
        $this->init($path, $controller);
        $compileID = $smarty->compile_id;
        if (defined('CLIENT_NAME')) {
            $compileID = CLIENT_NAME;
        }

        $smarty->setTemplateDir(dirname($path));
        $smarty->display($path, null, $compileID);
    }

    /**
     *
     *
     * @return Smarty The smarty object used for rendering.
     */
    public function smarty() {
        if (is_null($this->_Smarty)) {
            $smarty = new SmartyBC();

            $smarty->setCacheDir(PATH_CACHE.'/Smarty/cache');
            $smarty->setCompileDir(PATH_CACHE.'/Smarty/compile');
            $smarty->addPluginsDir(PATH_LIBRARY.'/SmartyPlugins');

//         Gdn::pluginManager()->Trace = TRUE;
            Gdn::pluginManager()->callEventHandlers($smarty, 'Gdn_Smarty', 'Init');

            $this->_Smarty = $smarty;
        }
        return $this->_Smarty;
    }

    /**
     * See if the provided template causes any errors.
     *
     * @param type $path Path of template file to test.
     * @return boolean TRUE if template loads successfully.
     */
    public function testTemplate($path) {
        $smarty = $this->smarty();
        $this->init($path, Gdn::controller());
        $compileID = $smarty->compile_id;
        if (defined('CLIENT_NAME')) {
            $compileID = CLIENT_NAME;
        }

        $return = true;
        try {
            $result = $smarty->fetch($path, null, $compileID);
            // echo wrap($Result, 'textarea', array('style' => 'width: 900px; height: 400px;'));
            $return = ($result == '' || strpos($result, '<title>Fatal Error</title>') > 0 || strpos($result, '<h1>Something has gone wrong.</h1>') > 0) ? false : true;
        } catch (Exception $ex) {
            $return = false;
        }
        return $return;
    }
}
