/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { LayoutRecordType, LayoutViewFragment } from "@dashboard/layout/layoutSettings/LayoutSettings.types";

export const GLOBAL_LAYOUT_VIEW: LayoutViewFragment = {
    recordType: LayoutRecordType.GLOBAL,
    recordID: -1,
};

/**
 * @deprecated
 */
export const ROOT_LAYOUT_VIEW: LayoutViewFragment = {
    recordType: LayoutRecordType.ROOT,
    recordID: -2,
};
