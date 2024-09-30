/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useEffect, useMemo } from "react";
import {
    AutomationRulesAdditionalDataQuery,
    DataFromOptionalSource,
} from "@dashboard/automationRules/AutomationRules.types";
import { useAutomationRules } from "@dashboard/automationRules/AutomationRules.context";
import { ICategory } from "@vanilla/addon-vanilla/categories/categoriesTypes";
import { ITag } from "@library/features/tags/TagsReducer";
import { IUser } from "@library/@types/api/users";
import { RecordID } from "@vanilla/utils";

interface IAutomationRulesSummaryQueryProps {
    categories?: ICategory[];
    categoryValue?: number | number[];
    tags?: ITag[];
    tagValue?: number[];
    users?: IUser[];
    userValue?: number;
    dataToLookupFromOptionalSource?: {
        currentValue?: RecordID | RecordID[];
        queryKey: string;
        sourceToLookup: string;
    };
    optionalDataSources?: Record<string, DataFromOptionalSource>;
}

export default function AutomationRulesSummaryQuery(props: IAutomationRulesSummaryQueryProps) {
    const {
        categories,
        categoryValue,
        tags,
        tagValue,
        users,
        userValue,
        dataToLookupFromOptionalSource,
        optionalDataSources,
    } = props;
    const { setAdditionalDataQuery, updateOptionalDataSources } = useAutomationRules();

    const currentValue = dataToLookupFromOptionalSource?.currentValue;
    const queryKey = dataToLookupFromOptionalSource?.queryKey;
    const dataSource = optionalDataSources?.[dataToLookupFromOptionalSource?.sourceToLookup ?? ""];

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
        // optional data source
        if (currentValue && dataSource?.data && queryKey) {
            const newDataToFetch = Array.isArray(currentValue)
                ? currentValue.filter((value) => !dataSource?.data.find((entry) => entry[queryKey] == value))
                : !dataSource?.data?.find((entry) => entry[queryKey] == currentValue)
                ? [currentValue]
                : [];
            if (newDataToFetch.length > 0) {
                query[dataToLookupFromOptionalSource?.sourceToLookup] = { [queryKey]: newDataToFetch };
            }
        }
        return query;
    }, [categories, categoryValue, tags, tagValue, users, userValue, currentValue, dataSource]);

    const updateDataFromOptionalSource = async (queryParams: AutomationRulesAdditionalDataQuery) => {
        if (dataSource?.dataFetcher) {
            const newData: any[] = await dataSource.dataFetcher?.(
                queryParams[dataToLookupFromOptionalSource?.sourceToLookup ?? ""],
            );
            updateOptionalDataSources?.(dataToLookupFromOptionalSource?.sourceToLookup ?? "", newData);
        }
    };

    useEffect(() => {
        if (
            additionalDataQuery.categoriesQuery?.categoryID ||
            additionalDataQuery.tagsQuery?.tagID ||
            additionalDataQuery.usersQuery?.userID
        ) {
            setAdditionalDataQuery?.(additionalDataQuery);
        } else if (Object.keys(additionalDataQuery).length > 0) {
            updateDataFromOptionalSource(additionalDataQuery);
        }
    }, [
        additionalDataQuery.categoriesQuery?.categoryID,
        additionalDataQuery.tagsQuery?.tagID,
        additionalDataQuery.usersQuery?.userID,
        currentValue,
    ]);

    return <></>;
}
