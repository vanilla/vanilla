<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web;

use Garden\EventManager;
use \Gdn_Request;
use Gdn;
use PocketsPlugin;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;
use Vanilla\AddonManager;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Contracts\LocaleInterface;
use Vanilla\Dashboard\Models\BannerImageModel;
use Vanilla\FeatureFlagHelper;
use Vanilla\Utility\ArrayUtils;
use Vanilla\Utility\HtmlUtils;
use Vanilla\Widgets\HomeWidgetContainerSchemaTrait;

/**
 * Class for enhancing a twig environment with various vanilla functions & methods.
 */
class TwigEnhancer
{
    use TwigRenderTrait;
    use HomeWidgetContainerSchemaTrait;

    /** @var AddonManager */
    private $addonManager;

    /** @var EventManager */
    private $eventManager;

    /** @var \Gdn_Session */
    private $session;

    /** @var ConfigurationInterface */
    private $config;

    /** @var LocaleInterface */
    private $locale;

    /** @var Gdn_Request */
    private $request;

    /** @var BannerImageModel */
    private $bannerImageModel;

    /** @var string|null The directory to cache compiled twig templates in. */
    private $compileCacheDirectory = null;

    /**
     * Caches of various template functions
     */
    private $configCache = [];
    private $translateCache = [];
    private $permissionCache = [];

    /**
     * DI.
     *
     * @param AddonManager $addonManager
     * @param EventManager $eventManager
     * @param \Gdn_Session $session
     * @param ConfigurationInterface $config
     * @param LocaleInterface $locale
     * @param Gdn_Request $request
     * @param BannerImageModel $bannerImageModel
     */
    public function __construct(
        AddonManager $addonManager,
        EventManager $eventManager,
        \Gdn_Session $session,
        ConfigurationInterface $config,
        LocaleInterface $locale,
        Gdn_Request $request,
        BannerImageModel $bannerImageModel = null
    ) {
        $this->addonManager = $addonManager;
        $this->eventManager = $eventManager;
        $this->session = $session;
        $this->config = $config;
        $this->locale = $locale;
        $this->request = $request;
        $this->bannerImageModel = $bannerImageModel;
    }

    /**
     * @return string|null
     */
    public function getCompileCacheDirectory(): ?string
    {
        return $this->compileCacheDirectory;
    }

    /**
     * @param string|null $compileCacheDirectory
     */
    public function setCompileCacheDirectory(?string $compileCacheDirectory): void
    {
        $this->compileCacheDirectory = $compileCacheDirectory;
    }

    /**
     * Add a few required method into the twig environment.
     *
     * @param \Twig\Environment $twig The twig environment to enhance.
     */
    public function enhanceEnvironment(\Twig\Environment $twig)
    {
        foreach ($this->getFunctionMappings() as $key => $callable) {
            if (is_object($callable) && $callable instanceof TwigFunction) {
                $twig->addFunction($callable);
            } else {
                if (is_int($key) && is_string($callable)) {
                    $key = $callable;
                }
                $twig->addFunction(new TwigFunction($key, $callable));
            }
        }

        $twig->addFilter(new \Twig\TwigFilter("minifyStylesheetContents", [$this, "minifyStylesheetContents"]));
        $twig->addFilter(new \Twig\TwigFilter("minifyScriptContents", [$this, "minifyScriptContents"]));
    }

    /**
     * Apply an alias for each addon.
     *
     * This is similar to the structure the frontend aliases use.
     * Generally, `@someAddonKey`will map to
     * - `ROOT/plugins/someAddonKey/views`
     * - `ROOT/themes/someAddonKey/views`
     * - `ROOT/applications/someAddonKey/views` depending on the addon type.
     *
     * @param FilesystemLoader $loader
     */
    public function enhanceFileSystem(FilesystemLoader $loader)
    {
        $addons = $this->addonManager->getEnabled();
        $loader->addPath(PATH_ROOT . "/resources/views", "resources");
        $loader->addPath(PATH_ROOT . "/library", "library");

        foreach ($addons as $addon) {
            $viewDirectory = PATH_ROOT . $addon->getSubdir() . "/views";
            if (file_exists($viewDirectory)) {
                $loader->addPath($viewDirectory, $addon->getKey());
            }
        }
    }

    /**
     * Fire an event of a particular name. All echoed output dring the event will be captured and returned.
     *
     * @param string $eventName The name of the event.
     * @param array $args The arguments to pass in the event.
     *
     * @return \Twig\Markup The echoed HTML wrapped as twig markup. All content here should be sanitized already.
     */
    public function fireEchoEvent(string $eventName, array $args = []): \Twig\Markup
    {
        ob_start();
        try {
            $this->eventManager->fire($eventName, $args);
            $echoedOutput = ob_get_contents();
        } finally {
            ob_end_clean();
        }
        return new \Twig\Markup($echoedOutput, "utf-8");
    }

    /**
     * Fire an event of a particular name from a pluggable. All echoed output dring the event will be captured and returned.
     *
     * @param \Gdn_Pluggable $pluggable The pluggable to fire the event as.
     * @param string $eventName The name of the event.
     * @param array $args The arguments to pass in the event.
     * @param string $fireAs Fire the event from a different base name than the pluggable.
     *
     * @return \Twig\Markup The echoed HTML wrapped as twig markup. All content here should be sanitized already.
     * @deprecated 3.3 Use `fireEchoEvent` instead. The name generated from the pluggable is quite complicated.
     */
    public function firePluggableEchoEvent(
        \Gdn_Pluggable $pluggable,
        string $eventName,
        array &$args = [],
        string $fireAs = ""
    ): \Twig\Markup {
        ob_start();
        try {
            if ($fireAs !== "") {
                $pluggable->fireAs($fireAs);
            }
            $pluggable->fireEvent($eventName, $args);
            $echoedOutput = ob_get_contents();
        } finally {
            ob_end_clean();
        }
        return new \Twig\Markup($echoedOutput, "utf-8");
    }

    /**
     * Render a module with some parameters.
     *
     * @param string $moduleName The name of the module.
     * @param array $moduleParams The parameters to pass to the module.
     *
     * @return \Twig\Markup
     */
    public function renderModule(string $moduleName, array $moduleParams = []): \Twig\Markup
    {
        return new \Twig\Markup(\Gdn_Theme::module($moduleName, $moduleParams), "utf-8");
    }

    /**
     * Render a module with some parameters.
     *
     * @param string $assetName The name of the asset.
     *
     * @return \Twig\Markup
     */
    public function renderControllerAsset(string $assetName): \Twig\Markup
    {
        $controller = Gdn::controller();
        if (!$controller) {
            return new \Twig\Markup("Could not render an asset without a Gdn_Controller instance", "utf-8");
        }
        $result = $controller->renderAssetForTwig($assetName);
        return $result;
    }

    /**
     * Render a pocket if it exists.
     *
     * @param string $pocketName The name of the pocket.
     * @param array $pocketArgs Arguments to pass to PocketsPlugin::pocketString().
     *
     * @return \Twig\Markup
     */
    public function renderPocket(string $pocketName, array $pocketArgs = []): \Twig\Markup
    {
        if (!class_exists(PocketsPlugin::class)) {
            return new \Twig\Markup("", "utf-8");
        }

        $result = PocketsPlugin::pocketString($pocketName, $pocketArgs);
        return new \Twig\Markup($result, "utf-8");
    }

    /**
     * Get a config key. The result will then be cached for the instance of the twig enhancer.
     *
     * @param string $key Config key.
     * @param mixed $default Default value for the key if not defined
     *
     * @return mixed
     */
    public function getConfig(string $key, $default)
    {
        if (!key_exists($key, $this->configCache)) {
            $this->configCache[$key] = $this->config->get($key, $default);
        }
        return $this->configCache[$key];
    }

    /**
     * Get a translation. The result will then be cached for the instance of the twig enhancer.
     *
     * @param string $key The translation lookup key.
     * @param string|false $default Default value for the key if not defined. If false the key will be used as the default.
     *
     * @return string
     */
    public function getTranslation(string $key, $default = false): string
    {
        if (!key_exists($key, $this->translateCache)) {
            $this->translateCache[$key] = Gdn::translate($key, $default);
        }
        return $this->translateCache[$key];
    }

    /**
     * Check if a user has a permission or one of a group of permissions.
     *
     * @param string $permissionName The permission name.
     * @param int|null $id The permission ID.
     *
     * @return bool
     */
    public function hasPermission(string $permissionName, $id = null): bool
    {
        $key = "$permissionName-$id";
        if (!array_key_exists($key, $this->permissionCache)) {
            if ((!empty($id) && is_numeric($id)) || is_string($id)) {
                $category = \CategoryModel::categories($id);

                // Handle checking categories with the permissionCategoryID.
                $has = \CategoryModel::checkPermission($category, $permissionName, false);
            } else {
                $has = $this->session->checkPermission($permissionName, false, "", $id);
            }
            $this->permissionCache[$key] = $has;
        }
        return $this->permissionCache[$key];
    }

    /**
     * Make an asset URL.
     *
     * @param string $url
     */
    public function assetUrl(string $url)
    {
        return asset($url, true, true);
    }

    /**
     * Render out breadcrumbs from the controller.
     *
     * @param array $options
     *
     * @return \Twig\Markup
     */
    public function renderBreadcrumbs(array $options = []): \Twig\Markup
    {
        $breadcrumbs = Gdn::controller()->data("Breadcrumbs", []);
        $html = \Gdn_Theme::breadcrumbs($breadcrumbs, val("homelink", $options, true), $options);
        return new \Twig\Markup($html, "utf-8");
    }

    /**
     * Wrap a function so it's output turns into twig markup.
     *
     * @param callable $callable
     * @return callable
     */
    public function wrapInTwigMarkup(callable $callable): callable
    {
        return function (...$args) use ($callable) {
            $result = call_user_func_array($callable, $args);
            if (!is_string($result)) {
                $result = "";
            }
            return new \Twig\Markup($result, "utf-8");
        };
    }

    /**
     * @return string
     */
    public function renderNoop(): string
    {
        return "";
    }

    /**
     * @param string $scriptContents
     * @return \Twig\Markup
     */
    public function minifyScriptContents(string $scriptContents): \Twig\Markup
    {
        $isMinificationEnabled = $this->config->get("minify.scripts", true);
        if ($isMinificationEnabled) {
            $minifier = new \MatthiasMullie\Minify\JS($scriptContents);
            $minified = $minifier->minify();
            return new \Twig\Markup($minified, "utf-8");
        }
        return new \Twig\Markup($scriptContents, "utf-8");
    }

    /**
     * @param string $stylesheetContents
     * @return \Twig\Markup
     */
    public function minifyStylesheetContents(string $stylesheetContents): \Twig\Markup
    {
        $isMinificationEnabled = $this->config->get("minify.styles", true);
        if ($isMinificationEnabled) {
            $minifier = new \MatthiasMullie\Minify\CSS($stylesheetContents);
            $minified = $minifier->minify();
            return new \Twig\Markup($minified, "utf-8");
        }
        return new \Twig\Markup($stylesheetContents, "utf-8");
    }

    /**
     * Return a mapping of twig function name -> callable.
     */
    private function getFunctionMappings(): array
    {
        return [
            // Lookups
            "getConfig" => [$this, "getConfig"],
            "featureEnabled" => [FeatureFlagHelper::class, "featureEnabled"],
            "getTranslation" => [$this, "getTranslation"],
            "t" => [$this, "getTranslation"],
            "sprintf",
            "empty",

            // Utility`
            "sanitizeUrl" => [\Gdn_Format::class, "sanitizeUrl"],
            "classNames" => [HtmlUtils::class, "classNames"],
            "renderHtml" => new TwigFunction(
                "renderHtml",
                function (?string $body, ?string $format = null) {
                    return Gdn::formatService()->renderHTML((string) $body, $format);
                },
                ["is_safe" => ["html"]]
            ),
            "str_repeat" => "str_repeat",
            "isArray" => [ArrayUtils::class, "isArray"],
            "isAssociativeArray" => [ArrayUtils::class, "isAssociative"],

            // View rendering
            "renderSeoLinkList" => $this->wrapInTwigMarkup([$this, "renderSeoLinkList"]),
            "renderSeoUser" => $this->wrapInTwigMarkup([$this, "renderSeoUser"]),
            "renderWidgetContainerSeoContent" => $this->wrapInTwigMarkup([$this, "renderWidgetContainerSeoContent"]),

            // Application interaction.
            "renderControllerAsset" => [$this, "renderControllerAsset"],
            "renderModule" => [$this, "renderModule"],
            "renderBreadcrumbs" => [$this, "renderBreadcrumbs"],
            "renderPocket" => [$this, "renderPocket"],
            "renderBanner" => $this->bannerImageModel
                ? [$this->bannerImageModel, "renderBanner"]
                : [$this, "renderNoop"],
            "fireEchoEvent" => [$this, "fireEchoEvent"],
            "firePluggableEchoEvent" => [$this, "firePluggableEchoEvent"],
            "helpAsset",

            // Session
            "hasPermission" => [$this, "hasPermission"],
            "inSection" => [\Gdn_Theme::class, "inSection"],
            "isSignedIn" => [$this->session, "isValid"],

            // Routing.
            "assetUrl" => [$this, "assetUrl"],
            "url" => [$this->request, "url"],

            // Minification
            "minifyStylesheetContents" => [$this, "minifyStylesheetContents"],
            "minifyScriptContents" => [$this, "minifyScriptContents"],
        ];
    }
}
