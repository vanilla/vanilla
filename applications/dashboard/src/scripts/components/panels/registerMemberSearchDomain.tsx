/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { SearchService } from "@library/search/SearchService";
import { onReady, t } from "@library/utility/appUtils";
import { TypeMemberIcon } from "@library/icons/searchIcons";
import { ISearchForm } from "@library/search/searchTypes";
import { IMemberSearchTypes } from "@dashboard/components/panels/memberSearchTypes";
import { MembersSearchFilterPanel } from "@dashboard/components/panels/MembersSearchFilterPanel";
import { MemberTable } from "@dashboard/components/MemberTable";
import Member from "@dashboard/components/Member";
import { hasUserViewPermission } from "@library/features/users/modules/hasUserViewPermission";

export function registerMemberSearchDomain() {
    onReady(() => {
        if (!hasUserViewPermission()) {
            // User doesn't have permission to search members.
            return;
        }
        SearchService.addPluggableDomain({
            key: "members",
            name: t("Members"),
            sort: 4,
            icon: <TypeMemberIcon />,
            getAllowedFields: () => {
                return ["username", "email", "roleIDs", "rankIDs"];
            },
            transformFormToQuery: (form: ISearchForm<IMemberSearchTypes>) => {
                const query = {
                    query: form.query || "",
                    email: form.email || "",
                    name: form.username || "",
                    roleIDs: form.roleIDs,
                    rankIDs: form.rankIDs,
                };

                return query;
            },
            getRecordTypes: () => {
                return ["user"];
            },
            PanelComponent: MembersSearchFilterPanel,
            resultHeader: null,
            ResultWrapper: MemberTable,
            getDefaultFormValues: () => {
                return {
                    username: "",
                    name: "",
                    email: "",
                    dateInserted: undefined,
                    roleIDs: [],
                };
            },
            getSortValues: () => {
                const sorts = [
                    {
                        content: "Recently Active",
                        value: "-dateLastActive",
                    },
                    {
                        content: "Name",
                        value: "name",
                    },
                    {
                        content: "Oldest Members",
                        value: "dateInserted",
                    },
                    {
                        content: "Newest Members",
                        value: "-dateInserted",
                    },
                ];
                if (SearchService.supportsExtensions()) {
                    sorts.push({
                        content: "Posts",
                        value: "-countPosts",
                    });
                }
                return sorts;
            },
            isIsolatedType: () => true,
            ResultComponent: Member,
        });
    });
}
