<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web;

use Garden\EventManager;
use Gdn;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;
use Vanilla\Contracts\AddonProviderInterface;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Contracts\LocaleInterface;
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

    /**
     * DI.
     *
     * @param AddonProviderInterface $addonProvider
     * @param EventManager $eventManager
     * @param \Gdn_Session $session
     * @param ConfigurationInterface $config
     * @param LocaleInterface $locale
     */
    public function __construct(
        AddonProviderInterface $addonProvider,
        EventManager $eventManager,
        \Gdn_Session $session,
        ConfigurationInterface $config,
        LocaleInterface $locale
    ) {
        $this->addonProvider = $addonProvider;
        $this->eventManager = $eventManager;
        $this->session = $session;
        $this->config = $config;
        $this->locale = $locale;
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
     * @return string The echoed HTML from the event. All content here should be sanitized already.
     */
    public function fireEchoEvent(string $eventName, array &$args = []): string {
        ob_start();
        $this->eventManager->fire($eventName, $args);
        $echoedOutput = ob_get_contents();
        ob_end_clean();
        return $echoedOutput;
    }

    private $configCache = [];

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

    private $translateCache = [];

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

    private $permissionCache = [];

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
            // Utility
            'sanitizeUrl' => [\Gdn_Format::class, 'sanitizeUrl'],
            'classNames' => [HtmlUtils::class, 'classNames'],
            'fireEchoEvent' => [$this, 'fireEchoEvent'],
            // Session
            'hasPermission' => [$this, 'hasPermission'],
            'inSection' => [\Gdn_Theme::class, 'inSection'],
            // Routing.
            'url',
            // User related.
            'userPhoto',
            'userPhotoUrl',
            'userUrl',
            'helpAsset',
        ];
    }
}
