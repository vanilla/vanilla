/*
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { categoryLookup } from "@dashboard/moderation/communityManagmentUtils";
import { IFilterLookupApi } from "@dashboard/moderation/components/FilterBlock";
import CategoryScopeModalContent from "@dashboard/tagging/components/CategoryScopeModalContent";
import { ITagItem } from "@dashboard/tagging/taggingSettings.types";

export interface ITagScope {
    id: string;
    singular: string;
    plural: string;

    description: string;
    placeholder: string;
    getIDs: (tag: ITagItem) => Array<number | string>;

    filterLookupApi: IFilterLookupApi;

    ModalContentComponent: React.ComponentType<{ tag: ITagItem }>;
}

const categoryScope: ITagScope = {
    id: "category",
    singular: "Category",
    plural: "Categories",
    description: "Select the categories to associate this status with.",
    placeholder: "Select one or more categories",
    getIDs: (tag: ITagItem) => tag.scope?.allowedCategoryIDs ?? [],
    filterLookupApi: categoryLookup,
    ModalContentComponent: CategoryScopeModalContent,
};

export class TagScopeService {
    static scopes = {
        categoryIDs: categoryScope,
    } as Record<string, ITagScope>;

    static addScope = function (key: string, scope: ITagScope) {
        if (!(key in TagScopeService.scopes)) TagScopeService.scopes[key] = scope;
    };
}
