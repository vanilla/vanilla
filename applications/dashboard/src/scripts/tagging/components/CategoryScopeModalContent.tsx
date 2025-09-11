/*
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ITagItem } from "@dashboard/tagging/taggingSettings.types";
import { IApiError } from "@library/@types/api/core";
import apiv2 from "@library/apiv2";
import Translate from "@library/content/Translate";
import { INavigationVariableItem } from "@library/headers/navigationVariables";
import Loader from "@library/loaders/Loader";
import { QuickLinksView } from "@library/navigation/QuickLinks.view";
import SimplePagerModel, { ILinkPages } from "@library/navigation/SimplePagerModel";
import { useQuery } from "@tanstack/react-query";
import { ICategory } from "@vanilla/addon-vanilla/categories/categoriesTypes";
import { t } from "@vanilla/i18n";

interface CategoryLinkResponse {
    results: INavigationVariableItem[];
    pagination: ILinkPages;
}

export default function CategoryScopeModalContent(props: { tag: ITagItem }) {
    const { tag } = props;

    const allowedCategoryIDs = (tag.scope?.allowedCategoryIDs ?? []).map((id) => `${id}`);

    const categoryQuery = useQuery<any, IApiError, CategoryLinkResponse>({
        queryFn: async () => {
            const response = await apiv2.get<ICategory[]>("/categories", {
                params: {
                    categoryID: allowedCategoryIDs,
                },
            });

            const links: INavigationVariableItem[] = response.data.map((category: ICategory) => {
                return {
                    id: `${category.categoryID}`,
                    name: category.name,
                    url: category.url,
                };
            });

            const pagination = SimplePagerModel.parseHeaders(response.headers);
            return { results: links, pagination: pagination };
        },
        queryKey: ["categoryList", allowedCategoryIDs],
    });

    const hasResults = categoryQuery.data?.results && categoryQuery.data?.results.length > 0;

    return (
        <>
            {categoryQuery.isLoading ? (
                <Loader small />
            ) : hasResults ? (
                <QuickLinksView links={categoryQuery.data?.results ?? []} />
            ) : (
                <p>
                    <Translate source={"No categories assigned to <0/>"} c0={tag.name} />
                </p>
            )}
        </>
    );
}
