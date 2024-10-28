/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IDiscussion } from "@dashboard/@types/api/discussion";
import apiv2 from "@library/apiv2";
import { ICategoryItem } from "@library/categoriesWidget/CategoryItem";
import { useQuery } from "@tanstack/react-query";
import { RecordID } from "@vanilla/utils";

interface SuggestedContent {
    discussions?: IDiscussion[];
    categories?: ICategoryItem[];
}
export interface SuggestedContentQueryParams {
    suggestedFollowsLimit?: number;
    suggestedContentLimit?: number;
    suggestedContentExcerptLength?: number;
    excludedCategoryIDs?: RecordID[];
    fields?: string[];
}

export function useSuggestedContentQuery(params: SuggestedContentQueryParams, initialData?: SuggestedContent) {
    return useQuery<SuggestedContent>({
        queryKey: ["suggestedContent", params],
        queryFn: async () => {
            const response = await apiv2.get("/interests/suggested-content", {
                params,
            });
            return response.data;
        },
        initialData,
        keepPreviousData: true,
    });
}
