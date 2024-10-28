<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2024 Higher Logic Inc.
 * @license Proprietary
 */

class MockAkismetPlugin extends AkismetPlugin
{
    const SPAMMER_USERNAME = "akismet‑guaranteed‑spam";
    const SPAMMER_EMAIL = "akismet-guaranteed-spam@example.com";

    /**
     * Mock the Akismet check.
     *
     * @param $recordType
     * @param $data
     * @return bool
     */
    public function checkAkismet($recordType, $data)
    {
        if ($data["Email"] === self::SPAMMER_EMAIL || $data["Username"] === self::SPAMMER_USERNAME) {
            return true;
        }

        return false;
    }
}
