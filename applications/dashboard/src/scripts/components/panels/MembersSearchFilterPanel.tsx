/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import AdvancedMembersFilters from "@dashboard/components/panels/AdvancedMembersFilters";
import { FilteredProfileFields } from "@dashboard/components/panels/FilteredProfileFields";
import mapProfileFieldsToSchemaForFilterForm from "@dashboard/components/panels/mapProfileFieldsToSchemaForFilterForm";
import {
    MemberSearchQuickFilters,
    useMemberSearchQuickFiltersSchema,
} from "@dashboard/components/panels/MemberSearchQuickFilters";
import { IMemberSearchTypes } from "@dashboard/components/panels/memberSearchTypes";
import { useProfileFields } from "@dashboard/userProfiles/state/UserProfiles.hooks";
import { ProfileField } from "@dashboard/userProfiles/types/UserProfiles.types";
import InputBlock from "@library/forms/InputBlock";
import { FilterFrame } from "@library/search/panels/FilterFrame";
import { useSearchForm } from "@library/search/SearchContext";
import { t } from "@vanilla/i18n";
import { JsonSchema } from "@vanilla/json-schema-forms";
import { useFormik } from "formik";
import React, { useEffect, useMemo } from "react";

export function useCombinedMemberSearchSchema(): JsonSchema {
    const quickFiltersSchema = useMemberSearchQuickFiltersSchema();
    const profileFieldConfigs = useProfileFields({ enabled: true }, { filterPermissions: true });

    const searchableProfileFields = useMemo<ProfileField[]>(() => {
        if (profileFieldConfigs.data) {
            return profileFieldConfigs.data.filter((profileField) => profileField.displayOptions.search);
        }
        return [];
    }, [profileFieldConfigs]);

    const profileFieldsFiltersSchema = useMemo<JsonSchema | null>(() => {
        if (searchableProfileFields) {
            return mapProfileFieldsToSchemaForFilterForm(searchableProfileFields);
        }
        return null;
    }, [searchableProfileFields]);

    {
        /* FIXME: the default ROLE filter and the RANKS filter(mounted by ranks plugin) aren't part of the schema */
    }
    const combinedSchema: JsonSchema = {
        ...quickFiltersSchema,
        properties: {
            ...(quickFiltersSchema.properties ?? {}),
            profileFields: profileFieldsFiltersSchema,
        },
    };

    return combinedSchema;
}

interface IProps {}

export function MembersSearchFilterPanel(_props: IProps) {
    const { form, updateForm, search, getCurrentDomain, getFilterComponentsForDomain } =
        useSearchForm<IMemberSearchTypes>();

    const schema = useCombinedMemberSearchSchema();

    const { profileFields, ...rest } = schema.properties;
    const profileFieldsSchema = profileFields as JsonSchema;
    const quickFiltersSchema = { ...schema } as JsonSchema;
    quickFiltersSchema.properties = rest;

    const formKeys = [
        ...new Set([
            ...getCurrentDomain().getAllowedFields(),
            ...Object.keys(quickFiltersSchema.properties),
            "profileFields",
        ]),
    ];

    const shouldRenderAdvancedFilters = Object.keys(profileFieldsSchema.properties).length > 0;

    const valuesFromSearchForm = {
        ...Object.fromEntries(formKeys.map((formKey) => [formKey, form[formKey] ?? undefined])),
        profileFields: form["profileFields"] ?? {},
    };

    const { values, submitForm, setValues, setFieldValue, resetForm, isSubmitting } = useFormik({
        initialValues: valuesFromSearchForm as any, //most of the form is dynamic... but tighten it up if possible
        onSubmit: () => {
            search();
        },
    });

    useEffect(() => {
        updateForm({ ...Object.fromEntries(formKeys.map((formKey) => [formKey, values[formKey] ?? undefined])) });
    }, [values]);

    const allFormValuesEmpty = useMemo(
        () =>
            !Object.values(values).some((value: any) =>
                typeof value === "object" ? Object.values(value).some((val) => !!val) : !!value,
            ),
        [values],
    );

    return (
        <FilterFrame
            title={t("Filter Results")}
            handleSubmit={submitForm}
            handleClearAll={() => resetForm({ values: {} })}
            disableClearAll={allFormValuesEmpty}
        >
            {shouldRenderAdvancedFilters && (
                <InputBlock>
                    <AdvancedMembersFilters
                        schema={schema}
                        values={values}
                        // FIXME: ROLES and RANKS are not in the form (bc they're missing from the schema), but they get cleared by the clear all button in the modal
                        onSubmit={async (newValues) => {
                            await setValues(newValues);
                            await submitForm();
                        }}
                    />

                    <FilteredProfileFields
                        values={values.profileFields}
                        onChange={(values) => {
                            setFieldValue("profileFields", values);
                        }}
                    />
                </InputBlock>
            )}

            <MemberSearchQuickFilters
                schema={quickFiltersSchema}
                values={values}
                onChange={(quickFiltersValues) => {
                    setValues({ ...values, ...quickFiltersValues });
                }}
            />

            <FormContext.Provider
                value={{
                    values,
                    onChange: (newValues) => {
                        setValues({ ...values, ...newValues });
                    },
                }}
            >
                {/* FIXME: the extra filters values don't affect clear all button disabled state, bc they're not in the schema. */}
                {getFilterComponentsForDomain("members")}
            </FormContext.Provider>
        </FilterFrame>
    );
}

// This context is intended to allow plugins to extend the members search form.
interface IFormContextValue {
    values: {
        [key: string]: any;
    };
    onChange: (values: { [key: string]: any }) => void;
}

export const FormContext = React.createContext<IFormContextValue>({
    values: {},
    onChange: (_values) => null,
});
