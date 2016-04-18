<?php

/**
 * Class StaticInitializer
 *
 * Fires an event for initializing static members in the calling class by invoking the initStatic method.
 *
 * @author Becky Van Bussel <becky@vanillaforums.com>
 * @copyright 2016 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @since 2.2
 */
trait StaticInitializer {

    /**
     * @var bool Whether the InitStatic event was fired.
     */
    protected static $initStaticFired = false;

    /**
     * Fires an event for initializing static members in the calling class.
     *
     * @return bool Whether the event was fired.
     * @throws Exception
     */
    public static function initStatic() {
        if (!self::$initStaticFired) {
            self::$initStaticFired = true;
            Gdn::pluginManager()->fireAs(get_called_class());
            Gdn::pluginManager()->fireEvent('InitStatic');
            return true;
        }
        return false;
    }
}
