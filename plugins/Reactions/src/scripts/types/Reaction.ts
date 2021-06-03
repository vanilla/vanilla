/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IBadge } from "@library/badge/Badge";

export interface IReaction extends IBadge {
    tagID: number;
    urlcode: string;
    class: string;
    count: number;
}
