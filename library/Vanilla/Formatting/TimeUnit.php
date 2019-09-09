<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting;

/**
 * Some loosely specific time units.
 *
 * If you need exact time calculations, DON'T use these.
 */
interface TimeUnit {
    const ONE_MINUTE = 60;
    const ONE_HOUR = self::ONE_MINUTE * 60;
    const ONE_DAY = self::ONE_HOUR * 24;
    const ONE_WEEK = self::ONE_DAY * 7;
    const ONE_MONTH = self::ONE_WEEK * 4; // Close enough.
    const ONE_YEAR = self::ONE_MONTH * 12; // Close enough.
}
