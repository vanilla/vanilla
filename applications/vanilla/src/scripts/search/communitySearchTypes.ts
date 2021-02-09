/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IComboBoxOption } from "@vanilla/library/src/scripts/features/search/SearchBar";

export interface ICommunitySearchTypes {
    categoryID?: number;
    categoryIDs?: number[];
    categoryOption?: IComboBoxOption<number>;
    categoryOptions?: IComboBoxOption[];
    followedCategories?: boolean;
    includeChildCategories?: boolean;
    includeArchivedCategories?: boolean;
    tags?: string[];
    tagsOptions?: IComboBoxOption[];
}
