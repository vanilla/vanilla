/**
 * @author Taylor Chance <tchance@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useMemo, useState, useRef, useEffect } from "react";
import { LoadingRectangle, LoadingSpacer } from "@library/loaders/LoadingRectangle";
import Heading from "@library/layout/Heading";
import {
    useProfileFieldsByUserID,
    useProfileFields,
    usePatchProfileFieldByUserID,
} from "@dashboard/userProfiles/state/UserProfiles.hooks";
import {
    mapProfileFieldsToSchema,
    mapUserProfileFieldsToFormValues,
    formatValuesforAPI,
    transformUserProfileFieldsData,
} from "./utils";
import { IJsonSchemaFormHandle, JsonSchema, JsonSchemaForm } from "@vanilla/json-schema-forms";
import { FormControl, FormControlGroup } from "@library/forms/FormControl";
import { ButtonTypes } from "@library/forms/buttonTypes";
import Button from "@library/forms/Button";
import { useToast } from "@library/features/toaster/ToastContext";
import { IFieldError, LoadStatus } from "@library/@types/api/core";
import ErrorMessages from "@library/forms/ErrorMessages";
import { t } from "@vanilla/i18n";
import { editProfileFieldsClasses } from "@library/editProfileFields/EditProfileFieldsStyles";
import { cx } from "@emotion/css";
import { useFormik } from "formik";
import { UserProfileFields } from "@dashboard/userProfiles/types/UserProfiles.types";
import { usePermissionsContext } from "@library/features/users/PermissionsContext";
import { mapValidationErrorsToFormikErrors } from "@vanilla/json-schema-forms/src/utils";

function SkeletonForm() {
    return (
        <>
            <LoadingRectangle height={21} width={"25%"} />
            <LoadingSpacer height={12} />
            <LoadingRectangle height={36} />
            <LoadingSpacer height={16} />
        </>
    );
}

function EditFieldsForm(props: { userID: number; formSchema: JsonSchema; profileFields: UserProfileFields }) {
    const { userID, formSchema, profileFields } = props;

    const [fieldErrors, setFieldErrors] = useState<Record<string, IFieldError[]>>({});
    const initialValues = mapUserProfileFieldsToFormValues(formSchema, profileFields);

    const { values, setValues, isSubmitting, resetForm, submitForm } = useFormik({
        initialValues,
        onSubmit: async (values) => {
            try {
                await patchProfileFieldByUserID(formatValuesforAPI(values, formSchema));
                toast.addToast({
                    autoDismiss: true,
                    body: <>{t("Changes saved.")}</>,
                });
                setFieldErrors({});
            } catch (e) {
                setFieldErrors(e.errors);
                return;
            }
        },
        validate: () => {
            const result = schemaFormRef?.current?.validate();
            return mapValidationErrorsToFormikErrors(result?.errors ?? []);
        },
        validateOnChange: false,
        enableReinitialize: true,
    });

    const toast = useToast();
    const schemaFormRef = useRef<IJsonSchemaFormHandle | null>(null);
    const classes = editProfileFieldsClasses();
    const patchProfileFieldByUserID = usePatchProfileFieldByUserID(userID);

    return !Object.keys(values).length ? (
        <></>
    ) : (
        <form
            role="form"
            onSubmit={(e) => {
                e.preventDefault();
                e.stopPropagation();
                void submitForm();
            }}
        >
            <JsonSchemaForm
                fieldErrors={fieldErrors}
                schema={formSchema}
                instance={values}
                FormControl={FormControl}
                FormControlGroup={FormControlGroup}
                onChange={setValues}
                disabled={isSubmitting}
                ref={schemaFormRef}
            />
            <Button
                type="submit"
                buttonType={ButtonTypes.PRIMARY}
                disabled={isSubmitting}
                className={cx(classes.submitButton)}
            >
                {t("Save")}
            </Button>
        </form>
    );
}

export function EditProfileFields(props: { userID: number; isOwnProfile: boolean }) {
    const { userID, isOwnProfile } = props;
    const { hasPermission } = usePermissionsContext();
    const profileFieldConfigs = useProfileFields(
        { enabled: true },
        {
            filterPermissions: true,
            isOwnProfile: isOwnProfile,
        },
    );
    const userProfileFields = useProfileFieldsByUserID(userID);

    const profileFieldSchema = useMemo<JsonSchema | null>(() => {
        const userCanEdit = hasPermission("users.edit");
        if (profileFieldConfigs.data) {
            return mapProfileFieldsToSchema(profileFieldConfigs.data, { userCanEdit });
        }
        return null;
    }, [profileFieldConfigs]);

    const userProfileFieldsLoaded = userProfileFields.status === LoadStatus.SUCCESS && userProfileFields.data;

    const userProfileFieldsData = useMemo(() => {
        if (userProfileFieldsLoaded) {
            return transformUserProfileFieldsData(userProfileFields.data ?? {}, profileFieldConfigs.data ?? []);
        }
    }, [userProfileFields.status, profileFieldConfigs.status]);

    return (
        <section>
            <Heading depth={1} renderAsDepth={1}>
                {t("Edit Profile Fields")}
            </Heading>

            {!profileFieldSchema || !userProfileFields.data ? (
                <div>
                    <LoadingSpacer height={36} />
                    <SkeletonForm />
                    <SkeletonForm />
                    <SkeletonForm />
                </div>
            ) : (
                <EditFieldsForm
                    userID={userID}
                    formSchema={profileFieldSchema}
                    profileFields={userProfileFieldsData ?? {}}
                />
            )}
        </section>
    );
}
