/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useEffect, useMemo, useRef, useState } from "react";
import { DashboardFormControlGroup, DashboardFormControl } from "@dashboard/forms/DashboardFormControl";
import { cx } from "@emotion/css";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import { frameBodyClasses } from "@library/layout/frame/frameBodyStyles";
import FrameFooter from "@library/layout/frame/FrameFooter";
import { frameFooterClasses } from "@library/layout/frame/frameFooterStyles";
import FrameHeader from "@library/layout/frame/FrameHeader";
import ButtonLoader from "@library/loaders/ButtonLoader";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import { t } from "@vanilla/i18n";
import { IJsonSchemaFormHandle, JsonSchema, JsonSchemaForm, IFieldError } from "@vanilla/json-schema-forms";
import { FormikErrors, useFormik } from "formik";
import {
    ProfileFieldFormValues,
    ProfileField,
    ProfileFieldType,
    ProfileFieldMutability,
    ProfileFieldVisibility,
    ProfileFieldRegistrationOptions,
} from "@dashboard/userProfiles/types/UserProfiles.types";
import { useProfileFields } from "@dashboard/userProfiles/state/UserProfiles.hooks";

import {
    getTypeOptions,
    mapProfileFieldFormValuesToProfileField,
    mapProfileFieldToFormValues,
    EMPTY_PROFILE_FIELD_CONFIGURATION,
} from "@dashboard/userProfiles/utils";

import ProfileFieldFormClasses from "@dashboard/userProfiles/components/ProfileFieldForm.classes";
import { ErrorObject } from "ajv/dist/core";
import ErrorMessages from "@library/forms/ErrorMessages";
import { ErrorWrapper } from "@dashboard/appearance/pages/ErrorWrapper";
import { notEmpty } from "@vanilla/utils";
import { IError } from "@library/errorPages/CoreErrorMessages";

interface IProps {
    onSubmit: (values: ProfileField) => Promise<void>;
    isVisible: boolean;
    profileFieldConfiguration?: ProfileField;
    onExit: () => void;
    title: string;
}

function mapAjvErrorsToFormikErrors(ajvErrors: ErrorObject[]): FormikErrors<any> {
    return Object.fromEntries(
        ajvErrors
            .filter((error) => !!error.instancePath && !!error.message)
            .map((error) => [error.instancePath, error.message])
            .map(([instancePath, message]) => {
                const key = instancePath!.slice(1, instancePath!.length).replace(/\//g, ".");
                return [key, message];
            }),
    );
}

export default function ProfileFieldForm(props: IProps) {
    const { onSubmit, isVisible, onExit, profileFieldConfiguration, title } = props;

    const classFrameFooter = frameFooterClasses();
    const classesFrameBody = frameBodyClasses();

    const schemaFormRef = useRef<IJsonSchemaFormHandle | null>(null);

    const allProfileFields = useProfileFields();

    const existingApiNames = (allProfileFields.data ?? []).map((field) => field.apiName);

    const titleID = `profileFormField_${profileFieldConfiguration?.apiName ?? "new"}`;

    const [topLevelErrors, setTopLevelErrors] = useState<IError[]>([]);
    const [fieldErrors, setFieldErrors] = useState<Record<string, IFieldError[]>>({});

    const { values, submitForm, setValues, setFieldValue, isSubmitting, resetForm, dirty } =
        useFormik<ProfileFieldFormValues>({
            initialValues: mapProfileFieldToFormValues(profileFieldConfiguration ?? EMPTY_PROFILE_FIELD_CONFIGURATION),
            onSubmit: async (values, { setSubmitting }) => {
                try {
                    setTopLevelErrors([]);
                    await onSubmit(mapProfileFieldFormValuesToProfileField(values));
                } catch (e) {
                    setFieldErrors(e.errors);

                    // API has limitations for setting the field on certain fields, mainly dropdownOptions, set these ones at the top of the form
                    if (e.errors[""]) {
                        setTopLevelErrors(e.errors[""]);
                    }

                    return;
                } finally {
                    setSubmitting(false);
                }
            },
            validate: () => {
                const result = schemaFormRef?.current?.validate();
                const mappedErrors = mapAjvErrorsToFormikErrors(result?.errors ?? []);
                return mappedErrors ?? {};
            },
            validateOnChange: false,
        });

    useEffect(() => {
        setTopLevelErrors([]);
        resetForm({
            values: mapProfileFieldToFormValues(profileFieldConfiguration ?? EMPTY_PROFILE_FIELD_CONFIGURATION),
        });
    }, [profileFieldConfiguration]);

    const typeOptions = useMemo(() => {
        return getTypeOptions(profileFieldConfiguration?.apiName ? profileFieldConfiguration?.dataType : undefined);
    }, [profileFieldConfiguration]);

    const schema = useMemo<JsonSchema>(() => {
        const isEditingExistingProfileField = !!profileFieldConfiguration?.apiName;
        const isCoreField = !!profileFieldConfiguration?.isCoreField;

        const visibilityIsNeitherPublicNorPrivate = ![
            ProfileFieldVisibility.PUBLIC,
            ProfileFieldVisibility.PRIVATE,
        ].includes(values.visibility.visibility);

        const requiresDropdownOptions = [
            ProfileFieldType.MULTI_SELECT_DROPDOWN,
            ProfileFieldType.NUMERIC_DROPDOWN,
            ProfileFieldType.SINGLE_SELECT_DROPDOWN,
        ].includes(values.type);

        const schemaRequired = ["type", "apiName", "label", "registrationOptions", "mutability"];
        if (requiresDropdownOptions) {
            schemaRequired.push("dropdownOptions");
        }

        return {
            type: "object",

            properties: {
                type: {
                    type: "string",
                    // disable the type field when it cannot be modified
                    disabled: Object.keys(typeOptions).length <= 1 || isCoreField,
                    "x-control": {
                        inputType: "dropDown",
                        label: t("Type"),
                        description: t("After creation you will be limited to fields of the same type."),
                        choices: {
                            staticOptions: typeOptions,
                        },
                    },
                },
                apiName: {
                    type: "string",
                    minLength: 1,
                    // disable the apiName field when editing an existing profile field, since it cannot be modified
                    "x-control": {
                        label: t("API Label"),
                        description: t("A unique label name that cannot be changed once saved."),
                        inputType: "textBox",
                    },
                    ...(existingApiNames.length > 0
                        ? isEditingExistingProfileField
                            ? {
                                  disabled: true,
                              }
                            : {
                                  not: {
                                      enum: existingApiNames,
                                  },
                              }
                        : {}),
                    errorMessage: [
                        {
                            keyword: "minLength",
                            message: t("API Label is required"),
                        },
                        {
                            keyword: "not",
                            message: t("Please enter a unique API Label, this one has been used before"),
                        },
                    ],
                },
                label: {
                    type: "string",
                    minLength: 1,
                    "x-control": {
                        label: t("Label"),
                        inputType: "textBox",
                    },
                    errorMessage: [
                        {
                            keyword: "minLength",
                            message: t("Label is required"),
                        },
                    ],
                },
                description: {
                    type: "string",
                    "x-control": {
                        label: t("Description"),
                        description: t("The description is shown as helper text to users."),
                        inputType: "textBox",
                    },
                },
                ...(requiresDropdownOptions && {
                    dropdownOptions: {
                        type: "string",
                        minLength: 1,
                        "x-control": {
                            label: t("Options"),
                            description: t("One item per line."),
                            inputType: "textBox",
                            type: "textarea",
                        },
                    },
                }),
                visibility: {
                    type: "object",
                    "x-control": {
                        label: t("Visibility"),
                    },
                    properties: {
                        visibility: {
                            type: "string",
                            disabled: isCoreField,
                            "x-control": {
                                inputType: "dropDown",
                                label: t("Visibility"),
                                helperText:
                                    (values.visibility.visibility === ProfileFieldVisibility.PRIVATE &&
                                        t("This is private information and will not be shared with other members.")) ||
                                    (values.visibility.visibility === ProfileFieldVisibility.INTERNAL &&
                                        t(
                                            "This information will only be shown to users with permission to view internal info.",
                                        )),
                                choices: {
                                    staticOptions: {
                                        [ProfileFieldVisibility.PUBLIC]: t("Public"),
                                        [ProfileFieldVisibility.PRIVATE]: t("Private"),
                                        [ProfileFieldVisibility.INTERNAL]: t("Internal"),
                                    },
                                },
                            },
                        },
                        posts: {
                            type: "boolean",
                            disabled:
                                ![ProfileFieldType.TEXT_INPUT, ProfileFieldType.SINGLE_SELECT_DROPDOWN].includes(
                                    values.type,
                                ) || isCoreField,
                            "x-control": {
                                inputType: "checkBox",
                                label: t("Show on posts"),
                            },
                        },
                    },
                    required: ["visibility"],
                },
                mutability: {
                    type: "string",
                    disabled: isCoreField,
                    "x-control": {
                        inputType: "dropDown",
                        label: t("Editing"),
                        choices: {
                            staticOptions:
                                values.registrationOptions === ProfileFieldRegistrationOptions.REQUIRED
                                    ? { [ProfileFieldMutability.ALL]: t("Allow") }
                                    : {
                                          ...(!visibilityIsNeitherPublicNorPrivate && {
                                              [ProfileFieldMutability.ALL]: t("Allow"),
                                          }),
                                          [ProfileFieldMutability.RESTRICTED]: t("Restrict"),
                                          [ProfileFieldMutability.NONE]: t("Block"),
                                      },
                        },
                    },
                },
                registrationOptions: {
                    type: "string",
                    disabled: isCoreField,
                    "x-control": {
                        inputType: "dropDown",
                        label: t("Registration Options"),
                        choices: {
                            staticOptions: {
                                ...(!visibilityIsNeitherPublicNorPrivate && {
                                    [ProfileFieldRegistrationOptions.REQUIRED]: "Required",
                                    [ProfileFieldRegistrationOptions.OPTIONAL]: "Optional",
                                }),
                                [ProfileFieldRegistrationOptions.HIDDEN]: "Hidden",
                            },
                        },
                    },
                },
            },
            required: schemaRequired,
        };
    }, [
        profileFieldConfiguration,
        typeOptions,
        values.mutability,
        values.visibility.visibility,
        values.type,
        values.registrationOptions,
    ]);

    const formGroupNames = ["visibility"];

    const classes = ProfileFieldFormClasses();

    useEffect(() => {
        // These dropdown values are being filtered based off the value of visibilty set to internal. Need to update the dropdown to what is available
        if (values.visibility.visibility === ProfileFieldVisibility.INTERNAL) {
            setFieldValue("registrationOptions", ProfileFieldRegistrationOptions.HIDDEN);
            if (values.mutability === ProfileFieldMutability.ALL) {
                setFieldValue("mutability", ProfileFieldMutability.RESTRICTED);
            }
        }
    }, [values.visibility, values.mutability, setFieldValue]);

    useEffect(() => {
        // Can only be visible on posts if it is a textInput
        if (![ProfileFieldType.TEXT_INPUT, ProfileFieldType.SINGLE_SELECT_DROPDOWN].includes(values.type)) {
            setFieldValue("visibility.posts", false);
        }
    }, [values.type, setFieldValue]);

    useEffect(() => {
        // To mark a field as required, mutability must be all
        if (values.registrationOptions === ProfileFieldRegistrationOptions.REQUIRED) {
            setFieldValue("mutability", ProfileFieldMutability.ALL);
        }
    }, [values.registrationOptions, setFieldValue]);

    const formGroupWrapper: React.ComponentProps<typeof JsonSchemaForm>["FormGroupWrapper"] = function (props) {
        if (
            props.groupName &&
            formGroupNames.map((name) => name.toLowerCase()).includes(props.groupName.toLowerCase())
        ) {
            return <div className={classes.formGroup}>{props.children}</div>;
        }
        return <>{props.children}</>;
    };

    return (
        <Modal
            isVisible={isVisible}
            size={ModalSizes.LARGE}
            exitHandler={() => {
                onExit();
            }}
            titleID={titleID}
        >
            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    submitForm();
                }}
            >
                <Frame
                    header={
                        <FrameHeader
                            titleID={titleID}
                            closeFrame={() => {
                                onExit();
                            }}
                            title={title}
                        />
                    }
                    body={
                        <FrameBody>
                            <div className={cx("frameBody-contents", classesFrameBody.contents)}>
                                {topLevelErrors.length > 0 && (
                                    <ErrorWrapper message={topLevelErrors[0].message}>
                                        <ErrorMessages errors={topLevelErrors.filter(notEmpty)} />
                                    </ErrorWrapper>
                                )}
                                <JsonSchemaForm
                                    fieldErrors={fieldErrors}
                                    schema={schema}
                                    instance={values}
                                    FormControlGroup={DashboardFormControlGroup}
                                    FormControl={DashboardFormControl}
                                    onChange={setValues}
                                    FormGroupWrapper={formGroupWrapper}
                                    ref={schemaFormRef}
                                />
                            </div>
                        </FrameBody>
                    }
                    footer={
                        <FrameFooter justifyRight={true}>
                            <Button
                                className={classFrameFooter.actionButton}
                                buttonType={ButtonTypes.TEXT}
                                onClick={() => {
                                    onExit();
                                }}
                                disabled={isSubmitting}
                            >
                                {t("Cancel")}
                            </Button>
                            <Button
                                submit
                                disabled={!dirty || isSubmitting}
                                className={classFrameFooter.actionButton}
                                buttonType={ButtonTypes.TEXT_PRIMARY}
                            >
                                {isSubmitting ? <ButtonLoader /> : t("Save")}
                            </Button>
                        </FrameFooter>
                    }
                />
            </form>
        </Modal>
    );
}
