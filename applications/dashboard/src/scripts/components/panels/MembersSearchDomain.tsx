/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { SearchService } from "@library/search/SearchService";
import { t } from "@library/utility/appUtils";
import { TypeMemberIcon } from "@library/icons/searchIcons";
import { IMemberSearchTypes } from "@dashboard/components/panels/memberSearchTypes";
import { MembersSearchFilterPanel } from "@dashboard/components/panels/MembersSearchFilterPanel";
import { MemberTable } from "@dashboard/components/MemberTable";
import Member, { IMemberResult } from "@dashboard/components/Member";
import { IUser } from "@library/@types/api/users";
import { dateRangeToString } from "@library/search/SearchUtils";
import { isDateRange } from "@dashboard/components/panels/FilteredProfileFields";
import getMemberSearchFilterSchema from "@dashboard/components/panels/getMemberSearchFilterSchema";
import { PermissionChecker } from "@library/features/users/Permission";
import { ISearchForm, ISearchResult } from "@library/search/searchTypes";
import { JsonSchema } from "@vanilla/json-schema-forms";
import SearchDomain from "@library/search/SearchDomain";
import COMMUNITY_SEARCH_SOURCE from "@library/search/CommunitySearchSource";

type MemberSearchResult = ISearchResult & IMemberResult;

class MembersSearchDomain extends SearchDomain<IMemberSearchTypes, MemberSearchResult, IMemberResult> {
    public key = "members";
    public sort = 4;

    get name() {
        return t("Members");
    }

    public icon = (<TypeMemberIcon />);

    public recordTypes = ["user"];

    public isIsolatedType = true;

    protected allowedFields = ["username", "dateInserted", "roleIDs", "profileFields"];

    public getAllowedFields(permissionChecker: PermissionChecker) {
        return this.allowedFields
            .concat(permissionChecker("personalInfo.view") ? ["email"] : [])
            .concat((this.additionalFilterSchemaFields ?? []).map(({ fieldName }) => fieldName));
    }

    public getFilterSchema = (permissionChecker: PermissionChecker): JsonSchema => {
        const initialSchema = getMemberSearchFilterSchema(permissionChecker);

        const extraFields = this.additionalFilterSchemaFields;

        const extraProperties = Object.fromEntries(
            extraFields.map(({ fieldName, schema }) => {
                return [fieldName, schema];
            }),
        );

        return {
            ...initialSchema,
            properties: {
                ...initialSchema.properties,
                ...(extraProperties as JsonSchema),
            },
        };
    };

    public transformFormToQuery = function (form: ISearchForm<IMemberSearchTypes>) {
        const query = {
            ...form,
            name: form.username || "",
            profileFields: {},
            scope: "site",
            expand: [],
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
    };

    public PanelComponent = MembersSearchFilterPanel;
    public ResultWrapper = MemberTable;

    public defaultFormValues = {
        username: "",
        name: "",
        email: "",
        dateInserted: undefined,
        roleIDs: [],
    };

    public get sortValues() {
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
    }

    public ResultComponent = Member;

    public mapResultToProps(result: MemberSearchResult): IMemberResult {
        return {
            userInfo: result.userInfo,
        };
    }
}

const MEMBERS_SEARCH_DOMAIN = new MembersSearchDomain();
export default MEMBERS_SEARCH_DOMAIN;
