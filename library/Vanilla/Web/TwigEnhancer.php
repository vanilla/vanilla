<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web;

use Garden\EventManager;
use \Gdn_Request;
use Gdn;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;
use Vanilla\Contracts\AddonProviderInterface;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Contracts\LocaleInterface;
use Vanilla\Dashboard\Models\BannerImageModel;
use Vanilla\FeatureFlagHelper;
use Vanilla\Utility\HtmlUtils;

/**
 * Class for enhancing a twig environment with various vanilla functions & methods.
 */
class TwigEnhancer {

    /** @var AddonProviderInterface */
    private $addonProvider;

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
     * @param AddonProviderInterface $addonProvider
     * @param EventManager $eventManager
     * @param \Gdn_Session $session
     * @param ConfigurationInterface $config
     * @param LocaleInterface $locale
     * @param Gdn_Request $request
     * @param BannerImageModel $bannerImageModel
     */
    public function __construct(
        AddonProviderInterface $addonProvider,
        EventManager $eventManager,
        \Gdn_Session $session,
        ConfigurationInterface $config,
        LocaleInterface $locale,
        Gdn_Request $request,
        BannerImageModel $bannerImageModel
    ) {
        $this->addonProvider = $addonProvider;
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
    public function getCompileCacheDirectory(): ?string {
        return $this->compileCacheDirectory;
    }

    /**
     * @param string|null $compileCacheDirectory
     */
    public function setCompileCacheDirectory(?string $compileCacheDirectory): void {
        $this->compileCacheDirectory = $compileCacheDirectory;
    }

    /**
     * Add a few required method into the twig environment.
     *
     * @param \Twig\Environment $twig The twig environment to enhance.
     */
    public function enhanceEnvironment(\Twig\Environment $twig) {
        foreach ($this->getFunctionMappings() as $key => $callable) {
            if (is_int($key) && is_string($callable)) {
                $key = $callable;
            }
            $twig->addFunction(new TwigFunction($key, $callable));
        }
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
    public function enhanceFileSystem(FilesystemLoader $loader) {
        $addons = $this->addonProvider->getEnabled();
        $loader->addPath(PATH_ROOT . '/resources/views', 'resources');

        foreach ($addons as $addon) {
            $viewDirectory = PATH_ROOT . $addon->getSubdir() . '/views';
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
    public function fireEchoEvent(string $eventName, array &$args = []): \Twig\Markup {
        ob_start();
        $this->eventManager->fire($eventName, $args);
        $echoedOutput = ob_get_contents();
        ob_end_clean();
        return new \Twig\Markup($echoedOutput, 'utf-8');
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
    public function firePluggableEchoEvent(\Gdn_Pluggable $pluggable, string $eventName, array &$args = [], string $fireAs = ''): \Twig\Markup {
        ob_start();
        if ($fireAs  !== '') {
            $pluggable->fireAs($fireAs);
        }
        $pluggable->fireEvent($eventName, $args);
        $echoedOutput = ob_get_contents();
        ob_end_clean();
        return new \Twig\Markup($echoedOutput, 'utf-8');
    }

    /**
     * Render a module with some parameters.
     *
     * @param string $moduleName The name of the module.
     * @param array $moduleParams The parameters to pass to the module.
     *
     * @return \Twig\Markup
     */
    public function renderModule(string $moduleName, array $moduleParams = []): \Twig\Markup {
        return new \Twig\Markup(\Gdn_Theme::module($moduleName, $moduleParams), 'utf-8');
    }

    /**
     * Render a module with some parameters.
     *
     * @param string $assetName The name of the asset.
     *
     * @return \Twig\Markup
     */
    public function renderControllerAsset(string $assetName): \Twig\Markup {
        $controller = Gdn::controller();
        return $controller->renderAssetForTwig($assetName);
    }

    /**
     * Get a config key. The result will then be cached for the instance of the twig enhancer.
     *
     * @param string $key Config key.
     * @param mixed $default Default value for the key if not defined
     *
     * @return mixed
     */
    public function getConfig(string $key, $default) {
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
    public function getTranslation(string $key, $default = false): string {
        if (!key_exists($key, $this->translateCache)) {
            $this->translateCache[$key] =  Gdn::translate($key, $default);
        }
        return $this->translateCache[$key];
    }

    /**
     * Check if a user has a permission or one of a group of permissions.
     *
     * @param string $permissionName The permission name.
     *
     * @return bool
     */
    public function hasPermission(string $permissionName): bool {
        if (!key_exists($permissionName, $this->permissionCache)) {
            $this->permissionCache[$permissionName] = $this->session->checkPermission($permissionName);
        }
        return $this->permissionCache[$permissionName];
    }

    /**
     * Render out breadcrumbs from the controller.
     *
     * @param array $options
     *
     * @return \Twig\Markup
     */
    public function renderBreadcrumbs(array $options = []): \Twig\Markup {
        $breadcrumbs = Gdn::controller()->data('Breadcrumbs', []);
        $html = \Gdn_Theme::breadcrumbs($breadcrumbs, val('homelink', $options, true), $options);
        return new \Twig\Markup($html, 'utf-8');
    }

    /**
     * Return a mapping of twig function name -> callable.
     */
    private function getFunctionMappings(): array {
        return [
            // Lookups
            'getConfig' => [$this, 'getConfig'],
            'featureEnabled' => [FeatureFlagHelper::class, 'featureEnabled'],
            'getTranslation' => [$this, 'getTranslation'],
            't' => [$this, 'getTranslation'],
            'sprintf',

            // Utility`
            'sanitizeUrl' => [\Gdn_Format::class, 'sanitizeUrl'],
            'classNames' => [HtmlUtils::class, 'classNames'],

            // Application interaction.
            'renderControllerAsset' => [$this, 'renderControllerAsset'],
            'renderModule' => [$this, 'renderModule'],
            'renderBreadcrumbs' => [$this, 'renderBreadcrumbs'],
            'renderBanner' => [$this->bannerImageModel, 'renderBanner'],
            'fireEchoEvent' => [$this, 'fireEchoEvent'],
            'firePluggableEchoEvent' => [$this, 'firePluggableEchoEvent'],
            'helpAsset',

            // Session
            'hasPermission' => [$this, 'hasPermission'],
            'inSection' => [\Gdn_Theme::class, 'inSection'],

            // Routing.
            'url' => [$this->request, 'url'],
        ];
    }

}
