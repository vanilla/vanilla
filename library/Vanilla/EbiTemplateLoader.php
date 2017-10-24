<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Vanilla;

use Ebi\TemplateLoaderInterface;

class EbiTemplateLoader implements TemplateLoaderInterface {
    use PluralizationTrait;

    private $addonManager;

    /**
     * @var Addon
     */
    private $currentAddon;

    private $pathCache = [];

    public function __construct(AddonManager $addonManager) {
        $this->addonManager = $addonManager;
    }

    /**
     * Return the cache key of a component.
     *
     * @param string $component The name of the component.
     * @return string Returns the unique key of the component.
     */
    public function cacheKey($component) {
        $path = $this->componentPath($component);

        if ($path) {
            $partial = stringBeginsWith($path, PATH_ROOT, true, true).filemtime($path);
            return $partial;
        } else {
            return null;
        }
    }

    /**
     * Get a component's physical path.
     *
     * @param string $component The component name.
     * @return string Returns a root-relative path or an empty string if the component isn't found.
     */
    public function componentPath($component) {
        if (isset($this->pathCache[$component])) {
            return $this->pathCache[$component];
        }

        // Look for a namespace.
        $parts = explode(':', $component, 2);
        if (count($parts) === 2) {
            list($namespace, $component) = $parts;

            $addons = $this->searchAddonsFromNamespace($namespace);
        } else {
            $addons = $this->searchAddonsFromComponent($component);
        }


        $suffixes = ['master' => 'masters'];

        $subPaths = [];
        if ($pos = strrpos($component, '-')) {
            $sx = substr($component, $pos + 1);
            if (isset($suffixes[$sx])) {
                $subPaths[] = "/views/{$suffixes[$sx]}/$component.html";
            } elseif ($pos = strpos($component, '-')) {
                $folder = substr($component, 0, $pos);
                $subPaths[] = "/views/$folder/$component.html";
            }
        }

        $subPaths[] = "/views/helpers/$component.html";

        foreach ($addons as $addon) {
            foreach ($subPaths as $subPath) {
                /* @var Addon $addon */
                $path = $addon->path($subPath);

                if (file_exists($path)) {
                    return $path;
                }
            }
        }

        return '';

    }

    /**
     * Get the addon that owns a component.
     *
     * Components must be named with a prefix that matches a controller that the addon owns in order to be located properly.
     *
     * @param string $component The name of the component to find.
     * @return null|Addon Returns an addon or **null** if it could not be found.
     */
    private function getComponentAddon($component) {
        if (preg_match('`^(?:[^:]+:)?([^-]+)`', $component, $m)) {
            $resource = $m[1];
            $plural = $this->plural($resource);

            // Look for the controller that owns the component.
            $controllers = [$plural.'ApiController', $plural.'Controller'];
            foreach ($controllers as $controller) {
                $classes = $this->addonManager->findClasses("*\\$controller");
                foreach ($classes as $class) {
                    $addon = $this->addonManager->lookupByClassname($class);
                    return $addon;
                }
            }
        }
        return null;
    }

    /**
     * Get a list of addons to search for a template.
     *
     * The addons return will be the following:
     *
     * - Theme
     * - Parent themes
     * - Base addon
     *
     * @param Addon|null $base The base addon that contains the template.
     * @return Addon[] Returns an array of addons.
     */
    public function searchAddons(Addon $base = null) {
        $result = [];

        for ($addon = $this->addonManager->getTheme();
            $addon !== null;
            $addon->getInfoValue('parent') && $addon = $this->addonManager->lookupTheme($addon->getInfoValue('parent'))) {

            // Prevent infinite loops.
            if (in_array($addon, $result)) {
                break;
            }

            $result[] = $addon;
        }

        if ($addon = $base ?: $this->getCurrentAddon()) {
            $result[] = $addon;
        }

        return $result;
    }

    /**
     * Get a list of addons to search from a namespaced XML element.
     *
     * When specifying a component in an Ebi template the following notation can be used:
     *
     * ```html
     * <!-- Include the component from the example addon, but theme it. -->
     * <example:component />
     *
     * <!-- Explicitly include the component from the example addon with no theme allowed. -->
     * <example-addon:component />
     * ```
     *
     * @param string $namespace The namespace to search.
     * @return Addon[] Returns an array of addons.
     */
    private function searchAddonsFromNamespace($namespace) {
        // Look for a suffix.
        if (false !== $pos = strrpos($namespace, '-')) {
            $suffix = substr($namespace, $pos + 1);
            if (in_array($suffix, [Addon::TYPE_ADDON, Addon::TYPE_THEME, Addon::TYPE_LOCALE])) {
                $addon = $this->addonManager->lookupByType(substr($namespace, 0, $pos), $suffix);
                return [$addon];
            }
        }

        // If there is no namespace then grab then assume this is an addon.
        $addon = $this->addonManager->lookupAddon($namespace);
        return $addon ? $this->searchAddons($addon) : [];
    }

    private function searchAddonsFromComponent($component) {
        $addon = $this->getComponentAddon($component);
        return $this->searchAddons($addon);
    }

    /**
     * Return the template source of a component.
     *
     * @param string $component The name of the component.
     * @return string Returns the template source of the component.
     */
    public function load($component) {
        $path = $this->componentPath($component);

        if (empty($path)) {
            return null;
        } else {
            return file_get_contents($path);
        }
    }

    /**
     * Get the currentAddon.
     *
     * @return Addon Returns the currentAddon.
     */
    public function getCurrentAddon() {
        return $this->currentAddon;
    }

    /**
     * Set the current addon.
     *
     * @param Addon|null $addon The addon to set or **null** to unset the current addon.
     * @return $this
     */
    public function setCurrentAddon(Addon $addon = null) {
        $this->currentAddon = $addon;
        return $this;
    }

    /**
     * Get the addonManager.
     *
     * @return AddonManager Returns the addonManager.
     */
    public function getAddonManager() {
        return $this->addonManager;
    }
}
