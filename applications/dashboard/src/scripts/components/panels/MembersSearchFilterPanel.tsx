/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import AdvancedMembersFilters from "@dashboard/components/panels/AdvancedMembersFilters";
import { FilteredProfileFields } from "@dashboard/components/panels/FilteredProfileFields";
import mapProfileFieldsToSchemaForFilterForm from "@dashboard/components/panels/mapProfileFieldsToSchemaForFilterForm";
import { IMemberSearchTypes } from "@dashboard/components/panels/memberSearchTypes";
import { useProfileFields } from "@dashboard/userProfiles/state/UserProfiles.hooks";
import { ProfileField } from "@dashboard/userProfiles/types/UserProfiles.types";
import { FormControl, FormControlGroup } from "@library/forms/FormControl";
import InputBlock from "@library/forms/InputBlock";
import { FilterFrame } from "@library/search/panels/FilterFrame";
import { useSearchForm } from "@library/search/SearchContext";
import { t } from "@vanilla/i18n";
import { JsonSchema, JsonSchemaForm } from "@vanilla/json-schema-forms";
import { useFormik } from "formik";
import React, { useEffect, useMemo } from "react";

interface IProps {}

export function MembersSearchFilterPanel(_props: IProps) {
    const { form, updateForm, search, getFiltersSchemaForDomain } = useSearchForm<IMemberSearchTypes>();

    const quickFiltersSchema = getFiltersSchemaForDomain("members");

    const profileFieldConfigs = useProfileFields({ enabled: true }, { filterPermissions: true });

    const searchableProfileFields = useMemo<ProfileField[]>(() => {
        if (profileFieldConfigs.data) {
            return profileFieldConfigs.data.filter((profileField) => profileField.displayOptions.search);
        }
        return [];
    }, [profileFieldConfigs]);

    const profileFieldsFiltersSchema = useMemo<JsonSchema | undefined>(() => {
        if (searchableProfileFields) {
            return mapProfileFieldsToSchemaForFilterForm(searchableProfileFields);
        }
    }, [searchableProfileFields]);

    const combinedSchema: JsonSchema = {
        ...quickFiltersSchema,
        properties: {
            ...quickFiltersSchema.properties,
            profileFields: profileFieldsFiltersSchema,
        },
    };

    const formKeys = [...new Set([...Object.keys(combinedSchema.properties)])];

    const shouldRenderAdvancedFilters = Object.keys(profileFieldsFiltersSchema?.properties ?? {}).length > 0;

    const valuesFromSearchForm = {
        ...Object.fromEntries(formKeys.map((formKey) => [formKey, form[formKey] ?? undefined])),
        profileFields: form["profileFields"] ?? {},
    };

    const { values, submitForm, setValues, setFieldValue, resetForm, isSubmitting } = useFormik({
        initialValues: valuesFromSearchForm as any, //most of the form is dynamic... but tighten it up if possible
        onSubmit: async () => {
            await search();
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
                        schema={combinedSchema}
                        values={values}
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

            <JsonSchemaForm
                schema={quickFiltersSchema}
                instance={values}
                onChange={(quickFiltersValues) => {
                    setValues({ ...values, ...quickFiltersValues });
                }}
                FormControl={FormControl}
                FormControlGroup={FormControlGroup}
            />
        </FilterFrame>
    );
}
