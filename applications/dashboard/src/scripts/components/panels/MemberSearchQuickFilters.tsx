/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc
 * @license Proprietary
 */

import React, { useMemo } from "react";
import {
    ProfileField,
    ProfileFieldDataType,
    ProfileFieldFormType,
    ProfileFieldMutability,
    ProfileFieldRegistrationOptions,
    ProfileFieldVisibility,
} from "@dashboard/userProfiles/types/UserProfiles.types";
import { JsonSchema, JsonSchemaForm } from "@vanilla/json-schema-forms";
import mapProfileFieldsToSchemaForFilterForm from "@dashboard/components/panels/mapProfileFieldsToSchemaForFilterForm";
import { FormControl, FormControlGroup } from "@library/forms/FormControl";
import { PermissionMode } from "@library/features/users/Permission";
import { usePermissionsContext } from "@library/features/users/PermissionsContext";
import { t } from "@vanilla/i18n";
import { MultiRoleInput } from "@dashboard/roles/MultiRoleInput";

interface IProps {
    values: Record<string, any>;
    schema: JsonSchema;
    onChange: (values: Record<string, any>, form?: Record<string, any>) => void;
}

export function MemberSearchQuickFilters(props: IProps) {
    const { schema, values, onChange } = props;

    return (
        <>
            <JsonSchemaForm
                schema={schema}
                instance={values}
                onChange={onChange}
                FormControl={FormControl}
                FormControlGroup={FormControlGroup}
            />

            {/* TODO: Remove after dynamic quick filters list includes roles schema with dropdown options
            Using roles in JsonSchema is not updating properly from URL Query. Updating dropdownOptions to value-label pairs may fix the issue. */}
            <MultiRoleInput
                label={t("Role")}
                value={values.roleIDs ?? []}
                onChange={(ids: number[]) => {
                    onChange({ ...values, roleIDs: ids });
                }}
            />
        </>
    );
}

export function useMemberSearchQuickFiltersSchema() {
    const { hasPermission } = usePermissionsContext();

    const schema = useMemo<JsonSchema>(() => {
        const hasEmailPermission = hasPermission("personalInfo.view", { mode: PermissionMode.GLOBAL });

        // TODO: Use profile fields from API marked as quick filters
        // Temporary list of quick filter profile fields
        // We treat these default filters like ProfileFields.
        const memberFilterFields: ProfileField[] = [
            {
                apiName: "username",
                label: "Username",
                dataType: ProfileFieldDataType.TEXT,
                formType: ProfileFieldFormType.TEXT,
            },
            hasEmailPermission
                ? {
                      apiName: "email",
                      label: "Email",
                      dataType: ProfileFieldDataType.TEXT,
                      formType: ProfileFieldFormType.TEXT,
                      visibility: ProfileFieldVisibility.INTERNAL,
                  }
                : null,
            {
                apiName: "registered",
                label: "Registered",
                dataType: ProfileFieldDataType.DATE,
                formType: ProfileFieldFormType.DATE,
            },
        ]
            .filter((field) => Boolean(field))
            .map((field) => {
                return {
                    description: "",
                    registrationOptions: ProfileFieldRegistrationOptions.OPTIONAL,
                    visibility: ProfileFieldVisibility.PUBLIC,
                    mutability: ProfileFieldMutability.RESTRICTED,
                    displayOptions: {
                        userCards: false,
                        posts: false,
                        search: true,
                    },
                    ...field,
                    label: t(field?.label || ""),
                } as ProfileField;
            });
        const tempSchema = mapProfileFieldsToSchemaForFilterForm(memberFilterFields);

        return tempSchema;
    }, [hasPermission]);

    return schema;
}
