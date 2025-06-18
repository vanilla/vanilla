/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useProfileFields } from "@dashboard/userProfiles/state/UserProfiles.hooks";
import { ProfileField } from "@dashboard/userProfiles/types/UserProfiles.types";
import React, { ReactNode, useContext, useMemo, useState } from "react";
import {
    IAutomationRulesCatalog,
    IAutomationRule,
    AutomationRulesAdditionalDataQuery,
    DataFromOptionalSource,
} from "@dashboard/automationRules/AutomationRules.types";
import { useAutomationRulesCatalog, useGetAdditionalData } from "@dashboard/automationRules/AutomationRules.hooks";
import { useRoles } from "@dashboard/roles/roleHooks";
import { IRole } from "@dashboard/roles/roleTypes";
import { useCategoryList } from "@library/categoriesWidget/CategoryList.hooks";
import { ICategory } from "@vanilla/addon-vanilla/categories/categoriesTypes";
import { ITag } from "@library/features/tags/TagsReducer";
import { useTagList } from "@library/features/tags/TagsHooks";
import { ICollection } from "@library/featuredCollections/Collections.variables";
import { useCollectionList } from "@library/featuredCollections/collectionsHooks";
import { useStatusOptions } from "@library/features/discussions/filters/discussionListFilterHooks";
import { IGroupOption } from "@library/forms/select/Tokens.types";
import { t } from "@vanilla/i18n";
import { useGetUsers } from "@dashboard/users/userManagement/UserManagement.hooks";
import { IUser } from "@library/@types/api/users";

export interface IAutomationRulesContext {
    profileFields?: ProfileField[];
    automationRulesCatalog?: IAutomationRulesCatalog;
    rolesByID?: Record<number, IRole>;
    tags?: ITag[];
    collections?: ICollection[];
    categories?: ICategory[];
    ideaStatusesByID?: Record<number, string>;
    users?: IUser[];
    initialOrderedRulesIDs?: Array<IAutomationRule["automationRuleID"]>;
    setInitialOrderedRulesIDs?: (initialOrderedRulesIDs: Array<IAutomationRule["automationRuleID"]>) => void;
    setAdditionalDataQuery?: (query?: AutomationRulesAdditionalDataQuery) => void;
    optionalDataSources?: Record<string, DataFromOptionalSource>;
    updateOptionalDataSources?: (dataSourceType: string, newData: DataFromOptionalSource["data"]) => void;
}

/**
 * Data holder for automation rules.
 */
export const AutomationRulesContext = React.createContext<IAutomationRulesContext>({
    profileFields: undefined,
    automationRulesCatalog: undefined,
    rolesByID: undefined,
    tags: undefined,
    collections: undefined,
    categories: undefined,
    ideaStatusesByID: undefined,
    users: undefined,
    initialOrderedRulesIDs: undefined,
    setInitialOrderedRulesIDs: () => {},
    setAdditionalDataQuery: () => {},
    optionalDataSources: undefined,
    updateOptionalDataSources: () => {},
});

/**
 * This is responsible for adding additional data and data-fetcher optional sources, e.g. groups, can be enabled or not
 */

let dataFromOptionalSource: Record<string, DataFromOptionalSource> = {};
AutomationRulesProvider.addDataFromOptionalSource = (dataSourceType: string, data: DataFromOptionalSource) => {
    dataFromOptionalSource[dataSourceType] = data;
};

export function AutomationRulesProvider(props: { children: ReactNode; isEscalationRulesMode?: boolean }) {
    const categoriesData = useCategoryList({ limit: 500 });
    const profileFieldsConfig = useProfileFields({ enabled: true });
    const automationRulesCatalogData = useAutomationRulesCatalog(props.isEscalationRulesMode);
    const rolesData = useRoles();
    const tagsData = useTagList({ limit: 500 });
    const collectionsData = useCollectionList();
    const discussionStatusesData = useStatusOptions();
    const usersData = useGetUsers({ limit: 500 });

    // store initial rules list order
    const [initialOrderedRulesIDs, setInitialOrderedRulesIDs] = useState<Array<IAutomationRule["automationRuleID"]>>(
        [],
    );

    // store optional data sources
    const [optionalDataSources, setOptionalDataSources] =
        useState<Record<string, DataFromOptionalSource>>(dataFromOptionalSource);

    // these two are used to fetch and append additional data to initial list, in case there are more prefetched 500
    const [additionalDataQuery, setAdditionalDataQuery] = useState<AutomationRulesAdditionalDataQuery | undefined>();
    useGetAdditionalData(additionalDataQuery ?? {}, { limit: 500 });

    const profileFields = useMemo(() => {
        return profileFieldsConfig.data;
    }, [profileFieldsConfig]);

    const automationRulesCatalog = useMemo(() => {
        return automationRulesCatalogData.data;
    }, [automationRulesCatalogData]);

    const rolesByID = useMemo(() => {
        return rolesData.data;
    }, [rolesData]);

    const categories = useMemo(() => {
        return categoriesData.data?.result;
    }, [categoriesData]);

    const tags = useMemo(() => {
        return tagsData.data;
    }, [tagsData]);

    const collections = useMemo(() => {
        return collectionsData.data;
    }, [collectionsData]);

    const users = useMemo(() => {
        return usersData.data?.users;
    }, [usersData]);

    const ideaStatusesByID = useMemo(() => {
        if (discussionStatusesData) {
            const ideas = [...(discussionStatusesData as IGroupOption[])].find((option) => option.label === t("Ideas"));
            if (ideas && ideas?.options?.length > 0) {
                return Object.fromEntries(ideas.options.map((status) => [status.value, status.label]));
            }
        }
    }, [discussionStatusesData]);

    return (
        <AutomationRulesContext.Provider
            value={{
                profileFields,
                automationRulesCatalog,
                rolesByID,
                tags,
                collections,
                categories,
                ideaStatusesByID,
                users,
                initialOrderedRulesIDs,
                setInitialOrderedRulesIDs,
                setAdditionalDataQuery,
                optionalDataSources,
                updateOptionalDataSources: (dataSourceType: string, newData: DataFromOptionalSource["data"]) => {
                    setOptionalDataSources({
                        ...optionalDataSources,
                        [dataSourceType]: {
                            ...optionalDataSources[dataSourceType],
                            data: [...optionalDataSources[dataSourceType].data, ...newData],
                        },
                    });
                },
            }}
        >
            {props.children}
        </AutomationRulesContext.Provider>
    );
}

export function useAutomationRules() {
    return useContext(AutomationRulesContext);
}
