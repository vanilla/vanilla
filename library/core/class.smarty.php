<?php
/**
 * Smart abstraction layer.
 *
 * @author Mark O'Sullivan <markm@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Core
 * @since 2.0
 */

use Vanilla\Logging\ErrorLogger;
use Vanilla\SmartyBC;

/**
 * Vanilla implementation of Smarty templating engine.
 */
class Gdn_Smarty implements \Vanilla\Contracts\Web\LegacyViewHandlerInterface
{
    /** @var Smarty\Smarty|null The smarty object used for the template. */
    protected ?Smarty\Smarty $_Smarty = null;

    /**
     *
     *
     * @param string $path
     * @param Gdn_Controller $controller
     */
    public function init($path, $controller)
    {
        $smarty = $this->smarty();

        // Get a friendly name for the controller.
        $controllerName = get_class($controller);
        if (stringEndsWith($controllerName, "Controller", true)) {
            $controllerName = substr($controllerName, 0, -10);
        }

        // Get an ID for the body.
        $methodStr = Gdn_Format::alphaNumeric(strtolower($controller->RequestMethod));
        $bodyIdentifier = strtolower($controller->ApplicationFolder . "_" . $controllerName) . "_" . $methodStr;
        $smarty->assign("BodyID", htmlspecialchars($bodyIdentifier));
        $smarty->assign("DataDrivenTitleBar", Gdn::config("Feature.DataDrivenTitleBar.Enabled", false));

        // Assign some information about the user.
        $session = Gdn::session();
        if ($session->isValid()) {
            $user = [
                "Name" => htmlspecialchars($session->User->Name),
                "CountNotifications" => (int) val("CountNotifications", $session->User, 0),
                "CountUnreadConversations" => (int) val("CountUnreadConversations", $session->User, 0),
                "SignedIn" => true,
            ];

            $photo = $session->User->Photo;
            if ($photo) {
                if (!isUrl($photo)) {
                    $photo = Gdn_Upload::url(changeBasename($photo, "n%s"));
                }
            } else {
                $photo = UserModel::getDefaultAvatarUrl($session->User);
            }
            $user["Photo"] = $photo;
        } else {
            $user = false;
        }
        $smarty->assign("User", $user);

        // Make sure that any datasets use arrays instead of objects.
        foreach ($controller->Data as $key => $value) {
            if ($value instanceof Gdn_DataSet) {
                $controller->Data[$key] = $value->resultArray();
            } elseif ($value instanceof stdClass) {
                $controller->Data[$key] = (array) $value;
            }
        }

        $bodyClass = val("CssClass", $controller->Data, "");
        $sections = Gdn_Theme::section(null, "get");
        if (is_array($sections)) {
            foreach ($sections as $section) {
                $bodyClass .= " Section-" . $section;
            }
        }

        $controller->Data["BodyClass"] = $bodyClass;

        // Set the current locale for themes to take advantage of.
        $locale = Gdn::locale()->Locale;
        $currentLocale = [
            "Key" => $locale,
            "Lang" => str_replace("_", "-", Gdn::locale()->language(true)), // mirrors html5 lang attribute
        ];
        if (class_exists("Locale")) {
            $currentLocale["Language"] = Locale::getPrimaryLanguage($locale);
            $currentLocale["Region"] = Locale::getRegion($locale);
            $currentLocale["DisplayName"] = Locale::getDisplayName($locale, $locale);
            $currentLocale["DisplayLanguage"] = Locale::getDisplayLanguage($locale, $locale);
            $currentLocale["DisplayRegion"] = Locale::getDisplayRegion($locale, $locale);
        }
        $smarty->assign("CurrentLocale", $currentLocale);

        $smarty->assign("Assets", (array) $controller->Assets);
        // 2016-07-07 Linc: Request used to return blank for homepage.
        // Now it returns defaultcontroller. This restores BC behavior.
        $isHomepage = val("isHomepage", $controller->Data);
        $path = $isHomepage ? "" : Gdn::request()->path();
        $smarty->assign("Path", $path);
        $smarty->assign("Homepage", $isHomepage); // true/false

        // Assign the controller data last so the controllers override any default data.
        $smarty->assign($controller->Data);
    }

    /**
     * Render the given view.
     *
     * @param string $path The path to the view's file.
     * @param Gdn_Controller $controller The controller that is rendering the view.
     */
    public function render($path, $controller)
    {
        $smarty = $this->smarty();
        $this->init($path, $controller);
        $compileID = $smarty->compile_id;
        if (defined("CLIENT_NAME")) {
            $compileID = CLIENT_NAME;
        }

        if (strpos($path, ":") === false) {
            $smarty->setTemplateDir(dirname($path));
        } else {
            [$type, $arg] = explode(":", $path, 2);
            if ($type === "file") {
                $smarty->setTemplateDir(dirname($arg));
            } elseif (!empty($controller->Theme)) {
                $smarty->setTemplateDir([
                    PATH_THEMES . "/{$controller->Theme}/views",
                    PATH_ADDONS_THEMES . "/{$controller->Theme}/views",
                ]);
            }
        }

        @$smarty->display($path, null, $compileID);
    }

    /**
     * @return Smarty\Smarty The smarty object used for rendering.
     */
    public function smarty(): Smarty\Smarty
    {
        if (is_null($this->_Smarty)) {
            $smarty = new SmartyBC();
            $this->_Smarty = $smarty;

            $smarty->error_reporting = ErrorLogger::ERROR_SUPPRESSED;

            // All smarty behaviour will need to be re-evaluated in PHP 9.x
            $smarty->setCacheDir(PATH_CACHE . "/Smarty/cache");
            $smarty->setCompileDir(PATH_CACHE . "/Smarty/compile");
            @$smarty->addPluginsDir(PATH_LIBRARY . "/SmartyPlugins");
            $smarty->setDebugTemplate(PATH_APPLICATIONS . "/dashboard/views/debug.tpl");
            $smarty->registerPlugin("function", "debug_vars", [$this, "debugVars"], false);
            $smarty->addDefaultModifiers(["default:false"]);

            Gdn::pluginManager()->callEventHandlers($smarty, "Gdn_Smarty", "Init");
            $this->enableSecurity($smarty);
            $smarty->default_modifiers[] = "default:false";
        }
        return $this->_Smarty;
    }

    /**
     * See if the provided template causes any errors.
     *
     * @param string $path Path of template file to test.
     * @return boolean TRUE if template loads successfully.
     */
    public function testTemplate($path)
    {
        $smarty = $this->smarty();
        $this->init($path, Gdn::controller());
        $compileID = $smarty->compile_id;
        if (defined("CLIENT_NAME")) {
            $compileID = CLIENT_NAME;
        }

        $return = true;
        try {
            $result = $smarty->fetch($path, null, $compileID);
            // echo wrap($Result, 'textarea', array('style' => 'width: 900px; height: 400px;'));
            $return = !(
                $result == "" ||
                strpos($result, "<title>Fatal Error</title>") > 0 ||
                strpos($result, "<h1>Something has gone wrong.</h1>") > 0
            );
        } catch (Exception $ex) {
            $return = false;
        }
        return $return;
    }

    /**
     * Output template variables.
     *
     * @param mixed $params
     * @param Smarty\TemplateBase $smarty
     * @return string
     */
    public function debugVars($params, $smarty)
    {
        $debug = new Smarty\Debug();
        $templateVars = $debug->getTemplateVars();
        $vars = self::sanitizeVariables($templateVars);
        ksort($vars);

        $sm = new SmartyBC();
        $sm->assign("assigned_vars", $vars);
        return $sm->fetch(PATH_APPLICATIONS . "/dashboard/views/debug_vars.tpl");
    }

    /**
     * Sanitize template variables to remove or obscure sensitive information.
     *
     * @param array $vars
     * @param int $level
     * @return array
     */
    public static function sanitizeVariables(array $vars, int $level = 0): array
    {
        $remove = ["password", "accesstoken", "fingerprint", "updatetoken"];
        $obscure = [
            "insertipaddress",
            "updateipaddress",
            "lastipaddress",
            "allipaddresses",
            "dateofbirth",
            "hashmethod",
            "email",
            "firstemail",
            "lastemail",
        ];

        $r = [];

        foreach ($vars as $key => $value) {
            $lkey = strtolower($key);
            if (in_array($lkey, $remove, true) || ($level === 0 && $key === "Assets")) {
                continue;
            } elseif (in_array($lkey, $obscure, true)) {
                $r[$key] = "***OBSCURED***";
            } elseif (is_array($value)) {
                $r[$key] = self::sanitizeVariables($value, $level + 1);
            } elseif ($value instanceof stdClass) {
                $r[$key] = (object) self::sanitizeVariables((array) $value, $level + 1);
            } else {
                $r[$key] = $value;
            }
        }
        return $r;
    }

    /**
     * Enable the security features on a Smarty object.
     *
     * @param SmartyBC $smarty
     */
    public function enableSecurity(SmartyBC $smarty): void
    {
        $security = new SmartySecurityVanilla($smarty);

        $security->allow_constants = false;
        $security->allow_super_globals = false;
        $security->streams = null;

        $functions = [
            "category",
            "categoryUrl",
            "checkPermission",
            "commentUrl",
            "discussionUrl",
            "inSection",
            "inCategory",
            "ismobile",
            "multiCheckPermission",
            "getValue",
            "setValue",
            "url",
            "useragenttype",
            "userUrl",
            "sprintf",
            "substr",
        ];

        foreach ($functions as $function) {
            $this->smarty()->registerPlugin(\Smarty\Smarty::PLUGIN_MODIFIER, $function, $function);
            $lowercase = strtolower($function);
            if ($lowercase !== $function) {
                $this->smarty()->registerPlugin(\Smarty\Smarty::PLUGIN_MODIFIER, $lowercase, $function);
            }
        }

        // Casing issue
        $this->smarty()->registerPlugin(\Smarty\Smarty::PLUGIN_MODIFIER, "InSection", "inSection");

        $smarty->enableSecurity($security);
    }
}
