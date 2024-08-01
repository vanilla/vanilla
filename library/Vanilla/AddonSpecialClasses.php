<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla;

use Garden\EventHandlersInterface;

/**
 * Data class for holding special classes for intializing an addon.
 */
final class AddonSpecialClasses
{
    private const SPECIAL_NAMESPACE_PREFIX = "Addon\\";
    private const DATA_KEYS = [
        "structureClasses",
        "containerRulesClasses",
        "addonConfigurationClasses",
        "eventHandlersClasses",
    ];

    /** @var array<class-string<AddonStructure>> */
    private $structureClasses = [];

    /** @var array<class-string<AddonContainerRules>> */
    private $containerRulesClasses = [];

    /** @var array<class-string<AddonConfigurationDefaults>> */
    private $addonConfigurationClasses = [];

    /** @var array<class-string<EventHandlersInterface>> */
    private $eventHandlersClasses = [];

    /**
     * Private constuctor. Must be instantiated from a method here.
     *
     * @param array $data An array of the data properties of the instance.
     */
    private function __construct(array $data = [])
    {
        foreach (self::DATA_KEYS as $key) {
            $this->{$key} = $data[$key] ?? [];
        }
    }

    /**
     * Locate special addon classes based on an addon.
     *
     * WARNING! This will invoke the autoloader heavily to inspect the various classes.
     * DO NOT instantiate these regularly in course of a request.
     *
     * These should only be instantiated through an addon where they will be cached regularly.
     *
     * @param Addon $addon
     *
     * @return AddonSpecialClasses
     */
    public static function fromAddon(Addon $addon): AddonSpecialClasses
    {
        $instance = new AddonSpecialClasses();
        foreach ($addon->getClasses() as $class) {
            // Easy bailout.
            if (!str_ends_with($class->getNamespace() ?? "", self::SPECIAL_NAMESPACE_PREFIX)) {
                // We only care about these specific classes.
                continue;
            }

            // These classes in this special namespace really shouldn't have any side effects.
            // We need them to be loaded so that we can do checks against them.
            try {
                require_once $class->getFilePath();
            } catch (\Throwable $throwable) {
                trigger_error($throwable->getMessage(), E_USER_WARNING);
            }

            try {
                $fullClassName = $class->className;
                if (is_a($fullClassName, AddonContainerRules::class, true)) {
                    $instance->containerRulesClasses[] = $fullClassName;
                } elseif (is_a($fullClassName, AddonConfigurationDefaults::class, true)) {
                    $instance->addonConfigurationClasses[] = $fullClassName;
                } elseif (is_a($fullClassName, AddonStructure::class, true)) {
                    $instance->structureClasses[] = $fullClassName;
                } elseif (is_a($fullClassName, EventHandlersInterface::class, true)) {
                    $instance->eventHandlersClasses[] = $fullClassName;
                }
            } catch (\Throwable $e) {
                // Catch any errors that might occur during autoloading.
                // We don't want our scanning to blow up the site before the cache is built.
                // If there is an invalid class in some corner just log it and proceed.
                trigger_error(
                    "Error while scanning class: `{$fullClassName}`.\n" . formatException($e),
                    E_USER_WARNING
                );
                continue;
            }
        }

        return $instance;
    }

    /**
     * Support {@link var_export()} for caching.
     *
     * @param array $array The array to load.
     * @return AddonSpecialClasses Returns a new definition with the properties from {@link $array}.
     */
    public static function __set_state(array $array): AddonSpecialClasses
    {
        return new AddonSpecialClasses($array);
    }

    /**
     * @return array<class-string<AddonStructure>>
     */
    public function getStructureClasses(): array
    {
        return $this->structureClasses;
    }

    /**
     * @return array<class-string<AddonContainerRules>>
     */
    public function getContainerRulesClasses(): array
    {
        return $this->containerRulesClasses;
    }

    /**
     * @return array<class-string<AddonConfigurationDefaults>>
     */
    public function getAddonConfigurationClasses(): array
    {
        return $this->addonConfigurationClasses;
    }

    /**
     * @return array<class-string<EventHandlersInterface>>
     */
    public function getEventHandlersClasses(): array
    {
        return $this->eventHandlersClasses;
    }
}
