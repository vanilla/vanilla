<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

/**
 * Class FloodControlHelper
 *
 * Used to configure FloodControlTrait parameters.
 */
class FloodControlHelper {
    /**
     * @param \Vanilla\FloodControlTrait $instance
     * @param string $type Type of record that will be used to configure to trait.
     *
     * @return \Vanilla\CacheInterface
     */
    public static function configure($instance, $type) {
        $session = Gdn::session();

        // Let's deactivate flood control if the user is an admin :)
        if (!Gdn::session()->isValid() || $session->User->Admin || $session->checkPermission('Garden.Moderation.Manage')) {
            $instance->setFloodControlEnabled(false);
            return new UserAttributeCacheAdapter(Gdn::session(), Gdn::userModel());
        }

        if (c('Cache.Enabled')) {
            $storageObject = new CacheCacheAdapter(Gdn::cache());

            $keyPostCount = $instance->getDefaultKeyCurrentPostCount();
            $keyLastDateChecked = $instance->getDefaultKeyLastDateChecked();
            // Add the type in the key in case that a model do multiple types (activityModel for example).
            foreach([&$keyPostCount, &$keyLastDateChecked] as &$key) {
                $key = str_replace('%s.%s', '%s.'.strtolower($type).'.%s', $key);
            }
        } else {
            // Convert old keys to new ones
            $storageObject = new UserAttributeCacheAdapter($session, Gdn::userModel());

            $keyPostCount = 'Count'.$type.'SpamCheck';
            $keyLastDateChecked = 'Date'.$type.'SpamCheck';
        }

        $instance
            ->setPostCountThreshold(c('Vanilla.'.$type.'.SpamCount', 1))
            ->setTimeSpan(c('Vanilla.'.$type.'.SpamTime', 60))
            ->setLockTime(c('Vanilla.'.$type.'.SpamLock', 60))
            ->setKeyCurrentPostCount($keyPostCount)
            ->setKeyLastDateChecked($keyLastDateChecked)
        ;

        return $storageObject;
    }
}
