/**
 * @author Taylor Chance <tchance@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useMemo, useState, useRef, useEffect } from "react";
import { LoadingRectangle, LoadingSpacer } from "@library/loaders/LoadingRectangle";
import Heading from "@library/layout/Heading";
import {
    useProfileFieldByUserID,
    useProfileFields,
    usePatchProfileFieldByUserID,
} from "@dashboard/userProfiles/state/UserProfiles.hooks";
import { mapProfileFieldsToSchema, mapUserProfileFieldValuesToSchema, formatValuesforAPI } from "./utils";
import { IJsonSchemaFormHandle, JsonSchema, JsonSchemaForm } from "@vanilla/json-schema-forms";
import { FormControl, FormControlGroup } from "@library/forms/FormControl";
import { ButtonTypes } from "@library/forms/buttonTypes";
import Button from "@library/forms/Button";
import { useToast } from "@library/features/toaster/ToastContext";
import { IFieldError } from "@library/@types/api/core";
import ErrorMessages from "@library/forms/ErrorMessages";
import { t } from "@vanilla/i18n";
import { editProfileFieldsClasses } from "@library/editProfileFields/EditProfileFieldsStyles";
import { cx } from "@emotion/css";

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

function EditFieldsForm(props: { userID: number; formSchema: JsonSchema; profileFields }) {
    const { userID, formSchema, profileFields } = props;
    const [values, setValues] = useState<JsonSchema>({});

    useEffect(() => {
        if (profileFields) {
            setValues(mapUserProfileFieldValuesToSchema(formSchema, profileFields));
        }
    }, [formSchema, profileFields]);

    const [isSubmitting, setIsSubmitting] = useState<boolean>(false);
    const [errors, setErrors] = useState<IFieldError[]>([]);
    const toast = useToast();
    const schemaFormRef = useRef<IJsonSchemaFormHandle | null>(null);
    const classes = editProfileFieldsClasses();
    const patchProfileFieldByUserID = usePatchProfileFieldByUserID(userID);

    const handleSubmit = async () => {
        try {
            setIsSubmitting(true);
            const validate = schemaFormRef?.current?.validate();
            if (validate?.errors?.length) {
                setIsSubmitting(false);
                return;
            }
            await patchProfileFieldByUserID(formatValuesforAPI(values, formSchema));
            toast.addToast({
                autoDismiss: true,
                body: <>{t("Changes saved.")}</>,
            });
            setIsSubmitting(false);
        } catch (err) {
            setErrors([{ message: err, field: "API" }]);
            setIsSubmitting(false);
        }
    };

    return !Object.keys(values).length ? (
        <></>
    ) : (
        <form
            onSubmit={(e) => {
                e.preventDefault();
                e.stopPropagation();

                handleSubmit();
            }}
        >
            {errors && (
                <div className={cx(classes.errorMessage)}>
                    <ErrorMessages errors={errors} />
                </div>
            )}

            <JsonSchemaForm
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
                Save
            </Button>
        </form>
    );
}

export function EditProfileFields(props: { userID: number }) {
    const { userID } = props;
    const profileFieldConfigs = useProfileFields({ filterEnabled: true });
    const userProfileFields = useProfileFieldByUserID(userID);

    const profileFieldSchema = useMemo<JsonSchema | null>(() => {
        if (profileFieldConfigs.data) {
            return mapProfileFieldsToSchema(profileFieldConfigs.data);
        }
        return null;
    }, [profileFieldConfigs]);

    return (
        <section>
            <Heading depth={1} renderAsDepth={1}>
                Edit Profile Fields
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
                    profileFields={userProfileFields.data}
                />
            )}
        </section>
    );
}
