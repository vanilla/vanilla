<?php
/**
 * Theme system.
 *
 * @author Mark O'Sullivan <markm@vanillaforums.com>
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0
 */

/**
 * Allows access to theme controls from within views, to give themers a unified
 * toolset for interacting with Vanilla from within views.
 */
class Gdn_Theme {

    /** @var array  */
    protected static $_AssetInfo = array();

    protected static $_BulletSep = false;

    protected static $_BulletSection = false;

    /** @var array */
    protected static $_Section = array();

    /**
     *
     *
     * @param string $AssetContainer
     */
    public static function assetBegin($AssetContainer = 'Panel') {
        self::$_AssetInfo[] = array('AssetContainer' => $AssetContainer);
        ob_start();
    }

    /**
     *
     */
    public static function assetEnd() {
        if (count(self::$_AssetInfo) == 0) {
            return;
        }

        $Asset = ob_get_clean();
        $AssetInfo = array_pop(self::$_AssetInfo);

        Gdn::controller()->addAsset($AssetInfo['AssetContainer'], $Asset);
    }

    /**
     *
     *
     * @param $Data
     * @param bool $HomeLink
     * @param array $Options
     * @return string
     */
    public static function breadcrumbs($Data, $HomeLink = true, $Options = array()) {
        $Format = '<a href="{Url,html}" itemprop="url"><span itemprop="title">{Name,html}</span></a>';

        $Result = '';

        if (!is_array($Data)) {
            $Data = array();
        }


        if ($HomeLink) {
            $HomeUrl = val('HomeUrl', $Options);
            if (!$HomeUrl) {
                $HomeUrl = Url('/', true);
            }

            $Row = array('Name' => $HomeLink, 'Url' => $HomeUrl, 'CssClass' => 'CrumbLabel HomeCrumb');
            if (!is_string($HomeLink)) {
                $Row['Name'] = T('Home');
            }

            array_unshift($Data, $Row);
        }

        if (val('HideLast', $Options)) {
            // Remove the last item off the list.
            array_pop($Data);
        }

        $DefaultRoute = ltrim(val('Destination', Gdn::router()->getRoute('DefaultController'), ''), '/');

        $Count = 0;
        $DataCount = 0;
        $HomeLinkFound = false;

        foreach ($Data as $Row) {
            $DataCount++;

            if ($HomeLinkFound && Gdn::request()->urlCompare($Row['Url'], $DefaultRoute) === 0) {
                continue; // don't show default route twice.
            } else {
                $HomeLinkFound = true;
            }

            // Add the breadcrumb wrapper.
            if ($Count > 0) {
                $Result .= '<span itemprop="child" itemscope itemtype="http://data-vocabulary.org/Breadcrumb">';
            }

            $Row['Url'] = $Row['Url'] ? Url($Row['Url']) : '#';
            $CssClass = 'CrumbLabel '.val('CssClass', $Row);
            if ($DataCount == count($Data)) {
                $CssClass .= ' Last';
            }

            $Label = '<span class="'.$CssClass.'">'.formatString($Format, $Row).'</span> ';
            $Result = concatSep('<span class="Crumb">'.T('Breadcrumbs Crumb', '›').'</span> ', $Result, $Label);

            $Count++;
        }

        // Close the stack.
        for ($Count--; $Count > 0; $Count--) {
            $Result .= '</span>';
        }

        $Result = '<span class="Breadcrumbs" itemscope itemtype="http://data-vocabulary.org/Breadcrumb">'.$Result.'</span>';
        return $Result;
    }

    /**
     * Call before writing an item and it will optionally write a bullet seperator.
     *
     * @param string $Section The name of the section.
     * @param bool $Return whether or not to return the result or echo it.
     * @return string
     * @since 2.1
     */
    public static function bulletItem($Section, $Return = true) {
        $Result = '';

        if (self::$_BulletSection === false) {
            self::$_BulletSection = $Section;
        } elseif (self::$_BulletSection != $Section) {
            $Result = "<!-- $Section -->".self::$_BulletSep;
            self::$_BulletSection = $Section;
        }

        if ($Return) {
            return $Result;
        } else {
            echo $Result;
        }
    }

    /**
     * Call before starting a row of bullet-seperated items.
     *
     * @param strng|bool $Sep The seperator used to seperate each section.
     * @since 2.1
     */
    public static function bulletRow($Sep = false) {
        if (!$Sep) {
            if (!self::$_BulletSep) {
                self::$_BulletSep = ' '.Bullet().' ';
            }
        } else {
            self::$_BulletSep = $Sep;
        }
        self::$_BulletSection = false;
    }


    /**
     * Returns whether or not the page is in the current section.
     *
     * @param string|array $Section
     */
    public static function inSection($Section) {
        $Section = (array)$Section;
        foreach ($Section as $Name) {
            if (isset(self::$_Section[$Name])) {
                return true;
            }
        }
        return false;
    }

    /**
     *
     *
     * @param $Path
     * @param bool $Text
     * @param null $Format
     * @param array $Options
     * @return mixed|null|string
     */
    public static function link($Path, $Text = false, $Format = null, $Options = array()) {
        $Session = Gdn::session();
        $Class = val('class', $Options, '');
        $WithDomain = val('WithDomain', $Options);
        $Target = val('Target', $Options, '');
        if ($Target == 'current') {
            $Target = trim(url('', true), '/ ');
        }

        if (is_null($Format)) {
            $Format = '<a href="%url" class="%class">%text</a>';
        }

        switch ($Path) {
            case 'activity':
                touchValue('Permissions', $Options, 'Garden.Activity.View');
                break;
            case 'category':
                $Breadcrumbs = Gdn::controller()->data('Breadcrumbs');
                if (is_array($Breadcrumbs) && count($Breadcrumbs) > 0) {
                    $Last = array_pop($Breadcrumbs);
                    $Path = val('Url', $Last);
                    $DefaultText = val('Name', $Last, T('Back'));
                } else {
                    $Path = '/';
                    $DefaultText = c('Garden.Title', T('Back'));
                }
                if (!$Text) {
                    $Text = $DefaultText;
                }
                break;
            case 'dashboard':
                $Path = 'dashboard/settings';
                touchValue('Permissions', $Options, array('Garden.Settings.Manage', 'Garden.Settings.View'));
                if (!$Text) {
                    $Text = t('Dashboard');
                }
                break;
            case 'home':
                $Path = '/';
                if (!$Text) {
                    $Text = t('Home');
                }
                break;
            case 'inbox':
                $Path = 'messages/inbox';
                touchValue('Permissions', $Options, 'Garden.SignIn.Allow');
                if (!$Text) {
                    $Text = t('Inbox');
                }
                if ($Session->isValid() && $Session->User->CountUnreadConversations) {
                    $Class = trim($Class.' HasCount');
                    $Text .= ' <span class="Alert">'.$Session->User->CountUnreadConversations.'</span>';
                }
                if (!$Session->isValid() || !Gdn::applicationManager()->checkApplication('Conversations')) {
                    $Text = false;
                }
                break;
            case 'forumroot':
                $Route = Gdn::router()->getDestination('DefaultForumRoot');
                if (is_null($Route)) {
                    $Path = '/';
                } else {
                    $Path = combinePaths(array('/', $Route));
                }
                break;
            case 'profile':
                touchValue('Permissions', $Options, 'Garden.SignIn.Allow');
                if (!$Text && $Session->isValid()) {
                    $Text = $Session->User->Name;
                }
                if ($Session->isValid() && $Session->User->CountNotifications) {
                    $Class = trim($Class.' HasCount');
                    $Text .= ' <span class="Alert">'.$Session->User->CountNotifications.'</span>';
                }
                break;
            case 'user':
                $Path = 'profile';
                touchValue('Permissions', $Options, 'Garden.SignIn.Allow');
                if (!$Text && $Session->isValid()) {
                    $Text = $Session->User->Name;
                }

                break;
            case 'photo':
                $Path = 'profile';
                TouchValue('Permissions', $Options, 'Garden.SignIn.Allow');
                if (!$Text && $Session->isValid()) {
                    $IsFullPath = strtolower(substr($Session->User->Photo, 0, 7)) == 'http://' || strtolower(substr($Session->User->Photo, 0, 8)) == 'https://';
                    $PhotoUrl = ($IsFullPath) ? $Session->User->Photo : Gdn_Upload::url(changeBasename($Session->User->Photo, 'n%s'));
                    $Text = img($PhotoUrl, array('alt' => $Session->User->Name));
                }

                break;
            case 'drafts':
                TouchValue('Permissions', $Options, 'Garden.SignIn.Allow');
                if (!$Text) {
                    $Text = t('My Drafts');
                }
                if ($Session->isValid() && $Session->User->CountDrafts) {
                    $Class = trim($Class.' HasCount');
                    $Text .= ' <span class="Alert">'.$Session->User->CountDrafts.'</span>';
                }
                break;
            case 'discussions/bookmarked':
                TouchValue('Permissions', $Options, 'Garden.SignIn.Allow');
                if (!$Text) {
                    $Text = t('My Bookmarks');
                }
                if ($Session->isValid() && $Session->User->CountBookmarks) {
                    $Class = trim($Class.' HasCount');
                    $Text .= ' <span class="Count">'.$Session->User->CountBookmarks.'</span>';
                }
                break;
            case 'discussions/mine':
                TouchValue('Permissions', $Options, 'Garden.SignIn.Allow');
                if (!$Text) {
                    $Text = t('My Discussions');
                }
                if ($Session->isValid() && $Session->User->CountDiscussions) {
                    $Class = trim($Class.' HasCount');
                    $Text .= ' <span class="Count">'.$Session->User->CountDiscussions.'</span>';
                }
                break;
            case 'register':
                if (!$Text) {
                    $Text = t('Register');
                }
                $Path = registerUrl($Target);
                break;
            case 'signin':
            case 'signinout':
                // The destination is the signin/signout toggle link.
                if ($Session->isValid()) {
                    if (!$Text) {
                        $Text = T('Sign Out');
                    }
                    $Path = signOutUrl($Target);
                    $Class = concatSep(' ', $Class, 'SignOut');
                } else {
                    if (!$Text) {
                        $Text = t('Sign In');
                    }

                    $Path = signInUrl($Target);
                    if (signInPopup() && strpos(Gdn::Request()->Url(), 'entry') === false) {
                        $Class = concatSep(' ', $Class, 'SignInPopup');
                    }
                }
                break;
        }

        if ($Text == false && strpos($Format, '%text') !== false) {
            return '';
        }

        if (val('Permissions', $Options) && !$Session->checkPermission($Options['Permissions'], false)) {
            return '';
        }

        $Url = Gdn::request()->url($Path, $WithDomain);

        if ($TK = val('TK', $Options)) {
            if (in_array($TK, array(1, 'true'))) {
                $TK = 'TransientKey';
            }
            $Url .= (strpos($Url, '?') === false ? '?' : '&').$TK.'='.urlencode(Gdn::session()->transientKey());
        }

        if (strcasecmp(trim($Path, '/'), Gdn::request()->path()) == 0) {
            $Class = concatSep(' ', $Class, 'Selected');
        }

        // Build the final result.
        $Result = $Format;
        $Result = str_replace('%url', $Url, $Result);
        $Result = str_replace('%text', $Text, $Result);
        $Result = str_replace('%class', $Class, $Result);

        return $Result;
    }

    /**
     * Renders the banner logo, or just the banner title if the logo is not defined.
     *
     * @param array $Properties
     */
    public static function logo($Properties = array()) {
        $Logo = C('Garden.Logo');

        if ($Logo) {
            $Logo = ltrim($Logo, '/');

            // Fix the logo path.
            if (stringBeginsWith($Logo, 'uploads/')) {
                $Logo = substr($Logo, strlen('uploads/'));
            }

            // Set optional title text.
            if (empty($Properties['title']) && C('Garden.LogoTitle')) {
                $Properties['title'] = C('Garden.LogoTitle');
            }
        }

        // Use the site title as alt if none was given.
        $Title = C('Garden.Title', 'Title');
        if (empty($Properties['alt'])) {
            $Properties['alt'] = $Title;
        }

        echo $Logo ? Img(Gdn_Upload::url($Logo), $Properties) : $Title;
    }

    /**
     * Returns the mobile banner logo. If there is no mobile logo defined then this will just return
     * the regular logo or the mobile title.
     *
     * @return string
     */
    public static function mobileLogo() {
        $Logo = C('Garden.MobileLogo', C('Garden.Logo'));
        $Title = C('Garden.MobileTitle', C('Garden.Title', 'Title'));

        if ($Logo) {
            return Img(Gdn_Upload::url($Logo), array('alt' => $Title));
        } else {
            return $Title;
        }
    }

    /**
     *
     *
     * @param $Name
     * @param array $Properties
     * @return mixed|string
     */
    public static function module($Name, $Properties = array()) {
        if (isset($Properties['cache'])) {
            $Key = isset($Properties['cachekey']) ? $Properties['cachekey'] : 'module.'.$Name;

            $Result = Gdn::cache()->get($Key);
            if ($Result !== Gdn_Cache::CACHEOP_FAILURE) {
//            Trace('Module: '.$Result, $Key);
                return $Result;
            }
        }

        try {
            if (!class_exists($Name)) {
                if (debug()) {
                    $Result = "Error: $Name doesn't exist";
                } else {
                    $Result = "<!-- Error: $Name doesn't exist -->";
                }
            } else {
                $Module = new $Name(Gdn::controller(), '');
                $Module->Visible = true;

                // Add properties passed in from the controller.
                $ControllerProperties = Gdn::controller()->data('_properties.'.strtolower($Name), array());
                $Properties = array_merge($ControllerProperties, $Properties);

                foreach ($Properties as $Name => $Value) {
                    $Module->$Name = $Value;
                }

                $Result = $Module->toString();
            }
        } catch (Exception $Ex) {
            if (debug()) {
                $Result = '<pre class="Exception">'.htmlspecialchars($Ex->getMessage()."\n".$Ex->getTraceAsString()).'</pre>';
            } else {
                $Result = $Ex->getMessage();
            }
        }

        if (isset($Key)) {
//         Trace($Result, "Store $Key");
            Gdn::cache()->store($Key, $Result, array(Gdn_Cache::FEATURE_EXPIRY => $Properties['cache']));
        }

        return $Result;
    }

    /**
     *
     *
     * @return string
     */
    public static function pagename() {
        $Application = Gdn::dispatcher()->application();
        $Controller = Gdn::dispatcher()->controller();
        switch ($Controller) {
            case 'discussions':
            case 'discussion':
            case 'post':
                return 'discussions';

            case 'inbox':
                return 'inbox';

            case 'activity':
                return 'activity';

            case 'profile':
                $Args = Gdn::dispatcher()->controllerArguments();
                if (!sizeof($Args) || (sizeof($Args) && $Args[0] == Gdn::session()->UserID)) {
                    return 'profile';
                }
                break;
        }

        return 'unknown';
    }

    /**
     * The current section the site is in. This can be one or more values. Think of it like a server-side css-class.
     *
     * @since 2.1
     *
     * @param string $Section The name of the section.
     * @param string $Method One of: add, remove, set, get.
     */
    public static function section($Section, $Method = 'add') {
        $Section = array_fill_keys((array)$Section, true);


        switch (strtolower($Method)) {
            case 'add':
                self::$_Section = array_merge(self::$_Section, $Section);
                break;
            case 'remove':
                self::$_Section = array_diff_key(self::$_Section, $Section);
                break;
            case 'set':
                self::$_Section = $Section;
                break;
            case 'get':
            default:
                return array_keys(self::$_Section);
        }
    }

    /**
     *
     *
     * @param $Code
     * @param $Default
     * @return mixed
     */
    public static function text($Code, $Default) {
        return C("ThemeOption.{$Code}", t('Theme_'.$Code, $Default));
    }
}
