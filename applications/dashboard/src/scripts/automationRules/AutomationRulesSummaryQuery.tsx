/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useEffect, useMemo } from "react";
import { AutomationRulesAdditionalDataQuery } from "@dashboard/automationRules/AutomationRules.types";
import { useAutomationRules } from "@dashboard/automationRules/AutomationRules.context";
import { ICategory } from "@vanilla/addon-vanilla/categories/categoriesTypes";
import { ITag } from "@library/features/tags/TagsReducer";
import { IUser } from "@library/@types/api/users";

interface IAutomationRulesSummaryQueryProps {
    categories?: ICategory[];
    categoryValue?: number | number[];
    tags?: ITag[];
    tagValue?: number[];
    users?: IUser[];
    userValue?: number;
}

export default function AutomationRulesSummaryQuery(props: IAutomationRulesSummaryQueryProps) {
    const { categories, categoryValue, tags, tagValue, users, userValue } = props;
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
            if (newCategoriesToFetch.length > 0) {
                query["categoriesQuery"] = { categoryID: newCategoriesToFetch };
            }
        }
        if (tags && tagValue && tagValue.some((tagID) => !tags.find((tag) => tag.tagID === tagID))) {
            query["tagsQuery"] = {
                tagID: tagValue.filter((tagID) => !tags.find((tag) => tag.tagID === tagID)),
            };
        }
        if (users && userValue && !users.find((user) => user.userID == userValue)) {
            query["usersQuery"] = {
                userID: [userValue],
            };
        }
        return query;
    }, [categories, categoryValue, tags, tagValue, users, userValue]);

    useEffect(() => {
        if (
            additionalDataQuery.categoriesQuery?.categoryID ||
            additionalDataQuery.tagsQuery?.tagID ||
            additionalDataQuery.usersQuery?.userID
        ) {
            setAdditionalDataQuery?.(additionalDataQuery);
        }
    }, [additionalDataQuery]);

    return <></>;
}
