/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

export function trimTrailingCommas(selector) {
    return selector.trim().replace(new RegExp("[,]+$"), "");
}
