/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { ISearchDomain, SearchService } from "@library/search/SearchService";
import { onReady, t } from "@library/utility/appUtils";
import { TypeMemberIcon } from "@library/icons/searchIcons";
import { IMemberSearchTypes } from "@dashboard/components/panels/memberSearchTypes";
import { MembersSearchFilterPanel } from "@dashboard/components/panels/MembersSearchFilterPanel";
import { MemberTable } from "@dashboard/components/MemberTable";
import Member, { IMemberResultProps } from "@dashboard/components/Member";
import { hasUserViewPermission } from "@library/features/users/modules/hasUserViewPermission";
import { IUser } from "@library/@types/api/users";
import { ISearchResult } from "@library/search/searchTypes";
import { dateRangeToString } from "@library/search/utils";
import { hasPermission } from "@library/features/users/Permission";
import { isDateRange } from "@dashboard/components/panels/FilteredProfileFields";

interface IMemberSearchResult {
    userInfo?: IUser;
}

export function registerMemberSearchDomain() {
    onReady(() => {
        if (!hasUserViewPermission(hasPermission)) {
            // User doesn't have permission to search members.
            return;
        }
        const membersSearchDomain: ISearchDomain<IMemberSearchTypes, IMemberSearchResult, IMemberResultProps> = {
            key: "members",
            name: t("Members"),
            sort: 4,
            icon: <TypeMemberIcon />,
            getAllowedFields: () => {
                return ["username", "email", "roleIDs", "rankIDs", "profileFields"];
            },
            transformFormToQuery: function (form) {
                const query = {
                    name: form.username || "",
                    profileFields: {},
                };

                // format date ranges
                for (let key in form) {
                    const property = form[key];
                    if (isDateRange(property)) {
                        query[key] = dateRangeToString(property);
                    }
                }

                // format date ranges nested in profile fields
                for (let key in form.profileFields) {
                    const profileField = form.profileFields[key];
                    if (isDateRange(profileField)) {
                        query.profileFields[key] = dateRangeToString(profileField);
                    } else {
                        query.profileFields[key] = form.profileFields[key];
                    }
                }

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
                        content: t("Recently Active"),
                        value: "-dateLastActive",
                    },
                    {
                        content: t("Name"),
                        value: "name",
                    },
                    {
                        content: t("Oldest Members"),
                        value: "dateInserted",
                    },
                    {
                        content: t("Newest Members"),
                        value: "-dateInserted",
                    },
                ];
                if (SearchService.supportsExtensions()) {
                    sorts.push({
                        content: t("Posts"),
                        value: "-countPosts",
                    });
                }
                return sorts;
            },
            isIsolatedType: () => true,
            ResultComponent: Member as React.ComponentType<IMemberResultProps>,
            mapResultToProps: (result: IMemberSearchResult): IMemberResultProps => ({
                userInfo: result.userInfo,
            }),
        };

        SearchService.addPluggableDomain(membersSearchDomain);
    });
}
