/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IContributionItem } from "@library/contributionItems/ContributionItem";

export interface IReaction extends IContributionItem {
    tagID: number;
    urlcode: string;
    class: string;
    count: number;
}
