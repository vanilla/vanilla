/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IComboBoxOption } from "@library/features/search/SearchBar";

export interface IDiscussionSearchTypes {
    categoryIDs?: number[];
    followedCategories?: boolean;
    includeChildCategories?: boolean;
    includeArchivedCategories?: boolean;
    tags?: string[];
    tagsOptions?: IComboBoxOption[];
    tagOperator?: "and" | "or";
    startDate?: string;
    endDate?: string;
    startDateUpdated?: string;
    endDateUpdated?: string;
    types?: string[];
}
