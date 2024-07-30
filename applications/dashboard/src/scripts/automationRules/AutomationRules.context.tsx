/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useProfileFields } from "@dashboard/userProfiles/state/UserProfiles.hooks";
import { ProfileField } from "@dashboard/userProfiles/types/UserProfiles.types";
import React, { ReactNode, useContext, useMemo, useState } from "react";
import { IAutomationRulesCatalog, IAutomationRule, AutomationRulesAdditionalDataQuery } from "./AutomationRules.types";
import { useAutomationRulesCatalog, useGetAdditionalData } from "@dashboard/automationRules/AutomationRules.hooks";
import { useRoles } from "@dashboard/roles/roleHooks";
import { IRole } from "@dashboard/roles/roleTypes";
import { IGetCategoryListParams, useCategoryList } from "@library/categoriesWidget/CategoryList.hooks";
import { ICategory } from "@vanilla/addon-vanilla/categories/categoriesTypes";
import { ITag } from "@library/features/tags/TagsReducer";
import { IGetTagsParams, useTagList } from "@library/features/tags/TagsHooks";
import { ICollection } from "@library/featuredCollections/Collections.variables";
import { useCollectionList } from "@library/featuredCollections/collectionsHooks";
import { useStatusOptions } from "@library/features/discussions/filters/discussionListFilterHooks";
import { IGroupOption } from "@library/forms/select/Tokens.loadable";
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
    setAdditionalDataQuery?: (query?: { categoriesQuery?: IGetCategoryListParams; tagsQuery?: IGetTagsParams }) => void;
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
});

export function AutomationRulesProvider(props: { children: ReactNode; isEscalationRulesMode?: boolean }) {
    const categoriesData = useCategoryList({ limit: 500 });
    const profileFieldsConfig = useProfileFields({ enabled: true });
    const automationRulesCatalogData = useAutomationRulesCatalog(props.isEscalationRulesMode);
    const rolesData = useRoles();
    const tagsData = useTagList({ limit: 500 });
    const collectionsData = useCollectionList();
    const discussionStatusesData = useStatusOptions();
    const usersData = useGetUsers({ limit: 500 });

    //store initial rules list order
    const [initialOrderedRulesIDs, setInitialOrderedRulesIDs] = useState<Array<IAutomationRule["automationRuleID"]>>(
        [],
    );

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
        return categoriesData.data;
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
                profileFields: profileFields,
                automationRulesCatalog: automationRulesCatalog,
                rolesByID: rolesByID,
                tags: tags,
                collections: collections,
                categories: categories,
                ideaStatusesByID: ideaStatusesByID,
                users: users,
                initialOrderedRulesIDs: initialOrderedRulesIDs,
                setInitialOrderedRulesIDs: setInitialOrderedRulesIDs,
                setAdditionalDataQuery: setAdditionalDataQuery,
            }}
        >
            {props.children}
        </AutomationRulesContext.Provider>
    );
}

export function useAutomationRules() {
    return useContext(AutomationRulesContext);
}
