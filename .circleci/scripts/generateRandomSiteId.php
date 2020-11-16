<?php
/**
 * Script for generating a random siteID.
 *
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

$baseID = 13310000;
$randID = rand(0, 299);
$siteID = $baseID + $randID;
echo $baseID + $randID;
