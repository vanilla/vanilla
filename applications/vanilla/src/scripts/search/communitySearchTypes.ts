import { IComboBoxOption } from "@vanilla/library/src/scripts/features/search/SearchBar";

/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

export interface ICommunitySearchTypes {
    categoryID?: number;
    categoryOption?: IComboBoxOption<number>;
    followedCategories?: boolean;
    includeChildCategories?: boolean;
    includeArchivedCategories?: boolean;
}
