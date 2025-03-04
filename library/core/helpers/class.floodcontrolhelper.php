<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Vanilla\Utility\DebugUtils;

/**
 * Class FloodControlHelper
 *
 * Used to configure FloodControlTrait parameters.
 */
class FloodControlHelper
{
    /**
     * Configure a flood controlled class.
     *
     * @psalm-suppress UndefinedDocblockClass
     *
     * @param \Vanilla\FloodControlTrait $instance
     * @param string $configScope Scope under with the configurations are sets ('Vanilla', 'Conversations').
     * @param string $type Type of record that will be used to configure to trait.
     * @param bool $skipAdmins Whether to skip flood control for admins/moderators or not. Default is true.
     *
     * @return \Psr\SimpleCache\CacheInterface
     */
    public static function configure($instance, $configScope, $type, $skipAdmins = true)
    {
        // The CheckSpam and SpamCheck attributes are deprecated and should be removed in 2018.
        if (property_exists($instance, "CheckSpam")) {
            deprecated(__CLASS__ . "->CheckSpam", __CLASS__ . "->setFloodControlEnabled()");
            $instance->setFloodControlEnabled($instance->CheckSpam);
        }
        if (property_exists($instance, "SpamCheck")) {
            deprecated(__CLASS__ . "->SpamCheck", __CLASS__ . "->setFloodControlEnabled()");
            $instance->setFloodControlEnabled($instance->SpamCheck);
        }

        $storageObject = new \Vanilla\Cache\CacheCacheAdapter(Gdn::cache());

        $keyPostCount = $instance->getDefaultKeyCurrentPostCount();
        $keyLastDateChecked = $instance->getDefaultKeyLastDateChecked();
        // Add the type in the key in case that a model do multiple types (activityModel for example).
        foreach ([&$keyPostCount, &$keyLastDateChecked] as &$key) {
            $key = str_replace("%s.%s", "%s." . strtolower($type) . ".%s", $key);
        }

        $instance
            ->setPostCountThreshold(c($configScope . "." . $type . ".SpamCount", 2))
            ->setTimeSpan(c($configScope . "." . $type . ".SpamTime", 60))
            ->setLockTime(c($configScope . "." . $type . ".SpamLock", 60))
            ->setKeyCurrentPostCount($keyPostCount)
            ->setKeyLastDateChecked($keyLastDateChecked)
            ->setSkipAdmins($skipAdmins);

        return $storageObject;
    }
}
