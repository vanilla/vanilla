<?php
/**
 * Theme system.
 *
 * @author Mark O'Sullivan <markm@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Core
 * @since 2.0
 */

use Vanilla\Logging\ErrorLogger;
use Vanilla\Site\SiteSectionModel;

/**
 * Allows access to theme controls from within views, to give themers a unified
 * toolset for interacting with Vanilla from within views.
 */
class Gdn_Theme
{
    /** @var array  */
    protected static $_AssetInfo = [];

    protected static $_BulletSep = false;

    protected static $_BulletSection = false;

    /** @var array */
    protected static $_Section = [];

    /**
     *
     *
     * @param string $assetContainer
     */
    public static function assetBegin($assetContainer = "Panel")
    {
        self::$_AssetInfo[] = ["AssetContainer" => $assetContainer];
        ob_start();
    }

    /**
     *
     */
    public static function assetEnd()
    {
        if (count(self::$_AssetInfo) == 0) {
            return;
        }

        $asset = ob_get_clean();
        $assetInfo = array_pop(self::$_AssetInfo);

        Gdn::controller()->addAsset($assetInfo["AssetContainer"], $asset);
    }

    /**
     *
     *
     * @param $data
     * @param bool $homeLink
     * @param array $options
     * @return string
     */
    public static function breadcrumbs($data, $homeLink = true, $options = [])
    {
        // Format breadcrumb that is part of breadcrumb structured data.
        $format = '<a itemprop="item" href="{Url,html}"><span itemprop="name">{Name,html}</span></a>';
        // Format the home link which is not part of breadcrumb structured data.
        $baseFormat = '<a href="{Url,html}"><span>{Name,html}</span></a>';

        $result = "";

        if (!is_array($data)) {
            $data = [];
        }

        if ($homeLink) {
            $homeUrl = val("HomeUrl", $options);
            if (!$homeUrl) {
                $homeUrl = url("/", true);
            }

            $row = ["Name" => $homeLink, "Url" => $homeUrl, "CssClass" => "HomeCrumb"];
            if (!is_string($homeLink)) {
                $row["Name"] = t("Home");
            }

            array_unshift($data, $row);
        }

        if (val("HideLast", $options)) {
            // Remove the last item off the list.
            array_pop($data);
        }

        /** @var  SiteSectionModel $siteSectionModel */
        $siteSectionModel = Gdn::getContainer()->get(SiteSectionModel::class);
        $currentSection = $siteSectionModel->getCurrentSiteSection();
        $defaultRoute = ltrim($currentSection->getDefaultRoute()["Destination"] ?? "", "/");
        $count = 0;
        $dataCount = 0;
        $position = 1;
        $displayStructuredData = false;
        $homeLinkFound = false;

        foreach ($data as $row) {
            $dataCount++;

            if ($homeLinkFound && Gdn::request()->urlCompare($row["Url"] ?? "", $defaultRoute) === 0) {
                continue; // don't show default route twice.
            } else {
                $homeLinkFound = true;
            }

            // Add the breadcrumb wrapper.
            if ($count > 0) {
                $result .= '<span itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem">';
                $result .= '<meta itemprop="position" content="' . $position++ . '" />';
                $displayStructuredData = true;
            }

            $row["Url"] = !empty($row["Url"]) ? url($row["Url"]) : "#";
            $cssClass = htmlspecialchars("CrumbLabel " . val("CssClass", $row));
            if ($dataCount == count($data)) {
                $cssClass .= " Last";
            }

            $sanitizedRow = [
                "Name" => $row["Name"],
                "Url" => htmlspecialchars($row["Url"]),
                "CssClass" => $cssClass,
            ];

            $label =
                '<span class="' .
                $cssClass .
                '">' .
                ($count === 0
                    ? formatString($baseFormat, $sanitizedRow)
                    : formatString($format, $sanitizedRow) . "</span>") .
                "</span>";
            $result = concatSep('<span class="Crumb">' . t("Breadcrumbs Crumb", "›") . "</span> ", $result, $label);

            $count++;
        }

        // Display as BreadcrumbList if there is structured data.
        $result =
            '<span class="Breadcrumbs" ' .
            ($displayStructuredData ? 'itemscope itemtype="http://schema.org/BreadcrumbList"' : "") .
            ">" .
            $result .
            "</span>";
        return $result;
    }

    /**
     * Call before writing an item and it will optionally write a bullet seperator.
     *
     * @param string $section The name of the section.
     * @param bool $return whether or not to return the result or echo it.
     * @return string
     * @since 2.1
     */
    public static function bulletItem($section, $return = true)
    {
        $result = "";

        if (self::$_BulletSection === false) {
            self::$_BulletSection = $section;
        } elseif (self::$_BulletSection != $section) {
            $result = "<!-- $section -->" . self::$_BulletSep;
            self::$_BulletSection = $section;
        }

        if ($return) {
            return $result;
        } else {
            echo $result;
        }
    }

    /**
     * Call before starting a row of bullet-seperated items.
     *
     * @param string|bool $sep The seperator used to seperate each section.
     * @since 2.1
     */
    public static function bulletRow($sep = false)
    {
        if (!$sep) {
            if (!self::$_BulletSep) {
                self::$_BulletSep = " " . bullet() . " ";
            }
        } else {
            self::$_BulletSep = $sep;
        }
        self::$_BulletSection = false;
    }

    /**
     * Returns whether or not the page is in the current section.
     *
     * @param string|array $section
     */
    public static function inSection($section)
    {
        $section = (array) $section;
        foreach ($section as $name) {
            if (isset(self::$_Section[$name])) {
                return true;
            }
        }
        return false;
    }

    /**
     *
     *
     * @param $path
     * @param bool $text
     * @param null $format
     * @param array $options
     * @return mixed|null|string
     */
    public static function link($path, $text = false, $format = null, $options = [])
    {
        $session = Gdn::session();
        $class = val("class", $options, "");
        $withDomain = val("WithDomain", $options);
        $target = val("Target", $options, "");
        if ($target == "current") {
            $target = trim(url("", true), "/ ");
        }

        if (is_null($format)) {
            $format = '<a href="%url" class="%class">%text</a>';
        }

        switch ($path) {
            case "activity":
                touchValue("Permissions", $options, "Garden.Activity.View");
                break;
            case "category":
                $breadcrumbs = Gdn::controller()->data("Breadcrumbs");
                if (is_array($breadcrumbs) && count($breadcrumbs) > 0) {
                    $last = array_pop($breadcrumbs);
                    $path = val("Url", $last);
                    $defaultText = htmlspecialchars(val("Name", $last, t("Back")));
                } else {
                    $path = "/";
                    $defaultText = c("Garden.Title", t("Back"));
                }
                if (!$text) {
                    $text = $defaultText;
                }
                break;
            case "dashboard":
                $path = "dashboard/settings";
                touchValue("Permissions", $options, ["Garden.Settings.Manage", "Garden.Settings.View"]);
                if (!$text) {
                    $text = t("Dashboard");
                }
                break;
            case "home":
                $path = "/";
                if (!$text) {
                    $text = t("Home");
                }
                break;
            case "inbox":
                $path = "messages/inbox";
                touchValue("Permissions", $options, "Garden.SignIn.Allow");
                if (!$text) {
                    $text = t("Inbox");
                }
                if ($session->isValid() && $session->User->CountUnreadConversations) {
                    $class = trim($class . " HasCount");
                    $text .=
                        ' <span class="Alert">' .
                        htmlspecialchars($session->User->CountUnreadConversations) .
                        "</span>";
                }
                if (!$session->isValid() || !Gdn::addonManager()->lookupAddon("conversations")) {
                    $text = false;
                }
                break;
            case "forumroot":
                $route = Gdn::router()->getDestination("DefaultForumRoot");
                if (is_null($route)) {
                    $path = "/";
                } else {
                    $path = combinePaths(["/", $route]);
                }
                break;
            case "profile":
                touchValue("Permissions", $options, "Garden.SignIn.Allow");
                if (!$text && $session->isValid()) {
                    $text = htmlspecialchars($session->User->Name);
                }
                if ($session->isValid() && $session->User->CountNotifications) {
                    $class = trim($class . " HasCount");
                    $text .= ' <span class="Alert">' . htmlspecialchars($session->User->CountNotifications) . "</span>";
                }
                break;
            case "user":
                $path = "profile";
                touchValue("Permissions", $options, "Garden.SignIn.Allow");
                if (!$text && $session->isValid()) {
                    $text = htmlspecialchars($session->User->Name);
                }

                break;
            case "photo":
                $path = "profile";
                touchValue("Permissions", $options, "Garden.SignIn.Allow");
                if (!$text && $session->isValid()) {
                    $isFullPath =
                        strtolower(substr($session->User->Photo, 0, 7)) == "http://" ||
                        strtolower(substr($session->User->Photo, 0, 8)) == "https://";
                    $photoUrl = $isFullPath
                        ? $session->User->Photo
                        : Gdn_Upload::url(changeBasename($session->User->Photo, "n%s"));
                    $text = img($photoUrl, ["alt" => $session->User->Name]);
                }

                break;
            case "drafts":
                touchValue("Permissions", $options, "Garden.SignIn.Allow");
                if (!$text) {
                    $text = t("My Drafts");
                }
                if ($session->isValid() && $session->User->CountDrafts) {
                    $class = trim($class . " HasCount");
                    $text .= ' <span class="Alert">' . htmlspecialchars($session->User->CountDrafts) . "</span>";
                }
                break;
            case "discussions/bookmarked":
                touchValue("Permissions", $options, "Garden.SignIn.Allow");
                if (!$text) {
                    $text = t("My Bookmarks");
                }
                if ($session->isValid() && $session->User->CountBookmarks) {
                    $class = trim($class . " HasCount");
                    $text .= ' <span class="Count">' . htmlspecialchars($session->User->CountBookmarks) . "</span>";
                }
                break;
            case "discussions/mine":
                touchValue("Permissions", $options, "Garden.SignIn.Allow");
                if (!$text) {
                    $text = t("My Discussions");
                }
                if ($session->isValid() && $session->User->CountDiscussions) {
                    $class = trim($class . " HasCount");
                    $text .= ' <span class="Count">' . htmlspecialchars($session->User->CountDiscussions) . "</span>";
                }
                break;
            case "register":
                if (!$text) {
                    $text = t("Register");
                }
                $path = registerUrl($target);
                break;
            case "signin":
            case "signinout":
                // The destination is the signin/signout toggle link.
                if ($session->isValid()) {
                    if (!$text) {
                        $text = t("Sign Out");
                    }
                    $path = signOutUrl($target);
                    $class = concatSep(" ", $class, "SignOut");
                } else {
                    if (!$text) {
                        $text = t("Sign In");
                    }

                    $path = signInUrl($target);
                    if (signInPopup() && strpos(Gdn::request()->url(), "entry") === false) {
                        $class = concatSep(" ", $class, "SignInPopup");
                    }
                }
                break;
        }

        if ($text == false && strpos($format, "%text") !== false) {
            return "";
        }

        if (val("Permissions", $options) && !$session->checkPermission($options["Permissions"], false)) {
            return "";
        }

        $url = Gdn::request()->url($path, $withDomain);

        if ($tK = val("TK", $options)) {
            if (in_array($tK, [1, "true"])) {
                $tK = "TransientKey";
            }
            $url .= (strpos($url, "?") === false ? "?" : "&") . $tK . "=" . urlencode(Gdn::session()->transientKey());
        }

        if (strcasecmp(trim($path, "/"), Gdn::request()->path()) == 0) {
            $class = concatSep(" ", $class, "Selected");
        }

        // Build the final result.
        $result = $format;
        $result = str_replace("%url", $url, $result);
        $result = str_replace("%text", $text, $result);
        $result = str_replace("%class", $class, $result);

        return $result;
    }

    /**
     * Renders the banner logo, or just the banner title if the logo is not defined.
     *
     * @param array $properties
     */
    public static function logo($properties = [])
    {
        $logo = c("Garden.Logo");
        $title = c("Garden.Title", "Title");

        if (!$logo && isset($properties["fallbackLogo"]) && isUrl($properties["fallbackLogo"])) {
            $logo = $properties["fallbackLogo"];
        }

        if ($logo) {
            $properties += ["alt" => $title];

            // Only trim slash from relative paths.
            if (!stringBeginsWith($logo, "//")) {
                $logo = ltrim($logo, "/");
            }

            // Fix the logo path.
            if (stringBeginsWith($logo, "uploads/")) {
                $logo = substr($logo, strlen("uploads/"));
            }

            // Set optional title text.
            if (empty($properties["title"]) && c("Garden.LogoTitle")) {
                $properties["title"] = c("Garden.LogoTitle");
            }

            echo img(Gdn_Upload::url($logo), $properties);
        } else {
            echo htmlEsc($title);
        }
    }

    /**
     * Returns the mobile banner logo. If there is no mobile logo defined then this will just return
     * the regular logo or the mobile title.
     *
     * @param array $properties
     * @return string
     */
    public static function mobileLogo($properties = [])
    {
        $logo = c("Garden.MobileLogo", c("Garden.Logo"));
        $title = c("Garden.MobileTitle", c("Garden.Title", "Title"));

        if (!$logo && isset($properties["fallbackLogo"]) && isUrl($properties["fallbackLogo"])) {
            $logo = $properties["fallbackLogo"];
        }

        if ($logo) {
            return img(Gdn_Upload::url($logo), ["alt" => $title]);
        } else {
            return htmlEsc($title);
        }
    }

    /**
     *
     *
     * @param $name
     * @param array $properties
     * @return mixed|string
     */
    public static function module($name, $properties = [])
    {
        if (isset($properties["cache"])) {
            $key = isset($properties["cachekey"]) ? $properties["cachekey"] : "module." . $name;

            $result = Gdn::cache()->get($key);
            if ($result !== Gdn_Cache::CACHEOP_FAILURE) {
                //            trace('Module: '.$Result, $Key);
                return $result;
            }
        }

        try {
            if (!class_exists($name)) {
                if (debug()) {
                    $result = "Error: $name doesn't exist";
                } else {
                    $result = "<!-- Error: $name doesn't exist -->";
                }
            } else {
                if (!Gdn::controller()) {
                    return "<!-- Error: Could not render module without a Gdn_Controller instance. -->";
                }

                if (is_a($name, \Vanilla\Web\JsInterpop\AbstractReactModule::class)) {
                    $module = \Gdn::getContainer()->get($name);
                } else {
                    $module = \Gdn::getContainer()->getArgs($name, [Gdn::controller(), ""]);
                }

                $module->Visible = true;

                if ($module instanceof \Vanilla\Widgets\React\CombinedPropsWidgetInterface) {
                    $module->setProps($properties);
                } else {
                    // Add properties passed in from the controller.
                    $controllerProperties = Gdn::controller()->data("_properties." . strtolower($name), []);
                    $properties = array_merge($controllerProperties, $properties);

                    foreach ($properties as $name => $value) {
                        // Check for a setter method
                        if (method_exists($module, $method = "set" . ucfirst($name))) {
                            $module->$method($value);
                        } else {
                            $module->$name = $value;
                        }
                    }
                }

                $result = $module->toString();
            }
        } catch (Exception $ex) {
            if (debug()) {
                $result =
                    '<pre class="Exception">' .
                    htmlspecialchars($ex->getMessage() . "\n" . $ex->getTraceAsString()) .
                    "</pre>";
            } else {
                $result = "";
                ErrorLogger::error($ex, [$name]);
            }
        }

        if (isset($key)) {
            //         trace($Result, "Store $Key");
            Gdn::cache()->store($key, $result, [Gdn_Cache::FEATURE_EXPIRY => $properties["cache"]]);
        }

        return $result;
    }

    /**
     *
     *
     * @return string
     */
    public static function pagename()
    {
        $application = Gdn::dispatcher()->application();
        $controller = Gdn::dispatcher()->controller();
        switch ($controller) {
            case "discussions":
            case "discussion":
            case "post":
                return "discussions";

            case "inbox":
                return "inbox";

            case "activity":
                return "activity";

            case "profile":
                $args = Gdn::dispatcher()->controllerArguments();
                if (!sizeof($args) || (sizeof($args) && $args[0] == Gdn::session()->UserID)) {
                    return "profile";
                }
                break;
        }

        return "unknown";
    }

    /**
     * Reset the current section.
     */
    public static function resetSection()
    {
        self::$_Section = [];
    }

    /**
     * The current section the site is in. This can be one or more values. Think of it like a server-side css-class.
     *
     * @param string $section The name of the section.
     * @param string $method One of: add, remove, set, get.
     * @since 2.1
     *
     */
    public static function section($section, $method = "add")
    {
        $section = array_fill_keys((array) $section, true);

        switch (strtolower($method)) {
            case "add":
                self::$_Section = array_merge(self::$_Section, $section);
                break;
            case "remove":
                self::$_Section = array_diff_key(self::$_Section, $section);
                break;
            case "set":
                self::$_Section = $section;
                break;
            case "get":
            default:
                return array_keys(self::$_Section);
        }
    }

    /**
     *
     *
     * @param $code
     * @param $default
     * @return mixed
     */
    public static function text($code, $default)
    {
        return c("ThemeOption.{$code}", t("Theme_" . $code, $default));
    }
}
