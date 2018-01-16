<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
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
     * @param string $configScope Scope under with the configurations are sets ('Vanilla', 'Conversations').
     * @param string $type Type of record that will be used to configure to trait.
     * @param bool $skipAdmins Whether to skip flood control for admins/moderators or not. Default is true.
     *
     * @return \Vanilla\CacheInterface
     */
    public static function configure($instance, $configScope, $type, $skipAdmins = true) {
        $session = Gdn::session();

        // The CheckSpam and SpamCheck attributes are deprecated and should be removed in 2018.
        if (property_exists($instance, 'CheckSpam')) {
            deprecated(__CLASS__.'->CheckSpam', __CLASS__.'->setFloodControlEnabled()');
            $instance->setFloodControlEnabled($instance->CheckSpam);
        }
        if (property_exists($instance, 'SpamCheck')) {
            deprecated(__CLASS__.'->SpamCheck', __CLASS__.'->setFloodControlEnabled()');
            $instance->setFloodControlEnabled($instance->SpamCheck);
        }

        if (!Gdn::session()->isValid()) {
            $instance->setFloodControlEnabled(false);

        // Let's deactivate flood control if the user is an admin :)
        } elseif ($skipAdmins && ($session->User->Admin || $session->checkPermission('Garden.Moderation.Manage'))) {
            $instance->setFloodControlEnabled(false);
        }

        // Return early since flood control is not enabled.
        if (!$instance->isFloodControlEnabled()) {
            return new UserAttributeCacheAdapter($session, Gdn::userModel());
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

            if ($session->getAttribute('Time'.$type.'SpamCheck')) {
                // Remove old attribute used in the conversationModel
                Gdn::userModel()->saveAttribute($session->UserID, 'Time'.$type.'SpamCheck', null);
            }
        }

        $instance
            ->setPostCountThreshold(c($configScope.'.'.$type.'.SpamCount', 2))
            ->setTimeSpan(c($configScope.'.'.$type.'.SpamTime', 60))
            ->setLockTime(c($configScope.'.'.$type.'.SpamLock', 60))
            ->setKeyCurrentPostCount($keyPostCount)
            ->setKeyLastDateChecked($keyLastDateChecked)
        ;

        return $storageObject;
    }
}
