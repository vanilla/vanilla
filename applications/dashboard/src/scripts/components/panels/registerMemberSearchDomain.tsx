/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { ISearchDomain, SearchService } from "@library/search/SearchService";
import { t } from "@library/utility/appUtils";
import { TypeMemberIcon } from "@library/icons/searchIcons";
import { IMemberSearchTypes } from "@dashboard/components/panels/memberSearchTypes";
import { MembersSearchFilterPanel } from "@dashboard/components/panels/MembersSearchFilterPanel";
import { MemberTable } from "@dashboard/components/MemberTable";
import Member, { IMemberResultProps } from "@dashboard/components/Member";
import { IUser } from "@library/@types/api/users";
import { dateRangeToString } from "@library/search/utils";
import { isDateRange } from "@dashboard/components/panels/FilteredProfileFields";
import getMemberSearchFilterSchema from "@dashboard/components/panels/getMemberSearchFilterSchema";

interface IMemberSearchResult {
    userInfo?: IUser;
}

export function registerMemberSearchDomain() {
    const membersSearchDomain: ISearchDomain<IMemberSearchTypes, IMemberSearchResult, IMemberResultProps> = {
        key: "members",
        name: t("Members"),
        sort: 4,
        icon: <TypeMemberIcon />,
        getAllowedFields: (permissionChecker) => {
            return ["username", "registered", "roleIDs", "profileFields"].concat(
                permissionChecker("personalInfo.view") ? ["email"] : [],
            );
        },
        getFilterSchema: (permissionChecker) => getMemberSearchFilterSchema(permissionChecker),
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
        ResultComponent: Member,
        mapResultToProps: (result: IMemberSearchResult): IMemberResultProps => ({
            userInfo: result.userInfo,
        }),
    };

    SearchService.addPluggableDomain(membersSearchDomain);
}
