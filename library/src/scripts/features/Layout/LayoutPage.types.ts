/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { ILayout } from "@library/features/Layout/Layout";
import { RecordID } from "@vanilla/utils";

export interface ILayoutQuery {
    layoutViewType: string;
    params: {
        siteSectionID?: string;
        [key: string]: any;
    };
}

export interface ILayoutSpec extends ILayout {
    layoutViewType: string;
    layoutID: RecordID;
}
