/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useEffect, useMemo } from "react";
import { AutomationRulesAdditionalDataQuery } from "@dashboard/automationRules/AutomationRules.types";
import { useAutomationRules } from "@dashboard/automationRules/AutomationRules.context";
import { ICategory } from "@vanilla/addon-vanilla/categories/categoriesTypes";
import { ITag } from "@library/features/tags/TagsReducer";

interface IAutomationRulesSummaryQueryProps {
    categories?: ICategory[];
    categoryValue?: number | number[];
    tags?: ITag[];
    tagValue?: number[];
}

export default function AutomationRulesSummaryQuery(props: IAutomationRulesSummaryQueryProps) {
    const { categories, categoryValue, tags, tagValue } = props;
    const { setAdditionalDataQuery } = useAutomationRules();

    // lets check if we should fetch new data
    const additionalDataQuery = useMemo(() => {
        const query: AutomationRulesAdditionalDataQuery = {};
        if (categoryValue && categories) {
            const newCategoriesToFetch = Array.isArray(categoryValue)
                ? categoryValue.filter(
                      (categoryID) => !categories.find((category) => category.categoryID == categoryID),
                  )
                : !categories.find((category) => category.categoryID === categoryValue)
                ? [categoryValue]
                : [];
            query["categoriesQuery"] = { categoryID: newCategoriesToFetch };
        }
        if (tags && tagValue) {
            query["tagsQuery"] = {
                tagID: tagValue.filter((tagID) => !tags.find((tag) => tag.tagID === tagID)),
            };
        }
        return query;
    }, [categories, categoryValue, tags, tagValue]);

    useEffect(() => {
        if (
            (additionalDataQuery.categoriesQuery?.categoryID &&
                additionalDataQuery.categoriesQuery?.categoryID?.length > 0) ||
            (additionalDataQuery.tagsQuery?.tagID && additionalDataQuery.tagsQuery?.tagID?.length > 0)
        ) {
            setAdditionalDataQuery?.(additionalDataQuery);
        }
    }, [additionalDataQuery]);

    return <></>;
}
