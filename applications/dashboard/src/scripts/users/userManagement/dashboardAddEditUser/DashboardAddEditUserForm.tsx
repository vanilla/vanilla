/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */
import { DashboardFormControl, DashboardFormControlGroup } from "@dashboard/forms/DashboardFormControl";
import { UserProfileFields } from "@dashboard/userProfiles/types/UserProfiles.types";
import dashboardAddEditUserClasses from "@dashboard/users/userManagement/dashboardAddEditUser/DashboardAddEditUser.classes";
import DashboardAddEditUserModal from "@dashboard/users/userManagement/dashboardAddEditUser/DashboardAddEditUserModal";
import { IUser } from "@library/@types/api/users";
import { mapUserProfileFieldsToFormValues } from "@library/editProfileFields/utils";
import { IError } from "@library/errorPages/CoreErrorMessages";
import { ErrorBoundary } from "@library/errorPages/ErrorBoundary";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import ErrorMessages from "@library/forms/ErrorMessages";
import { ErrorIcon } from "@library/icons/common";
import ConditionalWrap from "@library/layout/ConditionalWrap";
import ButtonLoader from "@library/loaders/ButtonLoader";
import Loader from "@library/loaders/Loader";
import Message from "@library/messages/Message";
import { t } from "@vanilla/i18n";
import { IFieldError, JsonSchema, JsonSchemaForm } from "@vanilla/json-schema-forms";
import { useFormik } from "formik";
import React, { useEffect, useLayoutEffect, useRef, useState } from "react";

interface IProps {
    title?: string;
    newPasswordFieldID?: string;
    initialValues?: DashboardAddEditUserFormValues;
    formGroupWrapper?: React.ComponentProps<typeof JsonSchemaForm>["FormGroupWrapper"];
    schema?: JsonSchema;
    handleSubmit: (values: DashboardAddEditUserFormValues) => Promise<IUser>;
    onSubmitSuccess?: (user: IUser) => void;
    renderInModal?: boolean;
    modalVisible?: boolean;
    handleCloseModal?: () => void;
}

export type DashboardAddEditUserFormValues = Omit<Partial<IUser>, "email" | "roles"> & {
    email: {
        email: string;
        emailConfirmed: boolean;
        bypassSpam: boolean;
    };
    passwordOptions?: {
        option?: string;
        newPassword?: string;
    };
    password?: string;
    privacy: {
        showEmail: boolean;
        private: boolean;
    };
    roles:
        | number[]
        | {
              roles: number[];
              banned: boolean;
          };
    profileFields?: UserProfileFields;
};

export const ADD_USER_EMPTY_INITIAL_VALUES: DashboardAddEditUserFormValues = {
    name: "",
    email: {
        email: "",
        emailConfirmed: false,
        bypassSpam: false,
    },
    password: "",
    rankID: undefined,
    roles: [],
    privacy: {
        showEmail: false,
        private: false,
    },
    profileFields: {},
};

export default function DashboardAddEditUserForm(props: IProps) {
    const {
        title,
        initialValues = ADD_USER_EMPTY_INITIAL_VALUES,
        formGroupWrapper,
        schema,
        handleSubmit,
        newPasswordFieldID,
        renderInModal = false,
        modalVisible = false,
        handleCloseModal,
        onSubmitSuccess,
    } = props;

    const { values, setValues, submitForm, isSubmitting, resetForm } = useFormik({
        initialValues: {
            ...initialValues,
            ...(!!schema?.properties.profileFields &&
                !!initialValues.profileFields && {
                    profileFields: mapUserProfileFieldsToFormValues(
                        schema.properties.profileFields as JsonSchema,
                        initialValues.profileFields,
                    ),
                }),
        },

        onSubmit: async function (values) {
            try {
                const submissionResult = await handleSubmit(values);
                onSubmitSuccess?.(submissionResult);
            } catch (err) {
                showErrors(err);
            }
        },
        onReset: () => {
            resetErrors();
        },
    });

    const [topLevelErrors, setTopLevelErrors] = useState<IError[]>([]);
    const [fieldErrors, setFieldErrors] = useState<Record<string, IFieldError[]>>({});
    const classes = dashboardAddEditUserClasses(newPasswordFieldID);

    useEffect(() => {
        resetFormAndErrors();
    }, [modalVisible]);

    function resetErrors() {
        setFieldErrors({});
        setTopLevelErrors([]);
    }

    function resetFormAndErrors() {
        resetForm();
        resetErrors();
    }

    const scrollRef = useRef<HTMLDivElement>(null);

    const showErrors = (error: any) => {
        setTopLevelErrors([
            {
                message: error.message,
            },
        ]);
        setFieldErrors(error.errors ?? []);
    };

    useLayoutEffect(() => {
        if (topLevelErrors && topLevelErrors.length > 0) {
            scrollRef.current?.scrollIntoView({ behavior: "smooth" });
        }
    }, [topLevelErrors]);

    if (!schema) {
        return <Loader />;
    }

    return (
        <ErrorBoundary>
            <ConditionalWrap
                component={DashboardAddEditUserModal}
                condition={renderInModal}
                componentProps={{
                    title,
                    handleClose: () => {
                        resetFormAndErrors();
                        handleCloseModal?.();
                    },
                    handleSubmit: async () => {
                        await submitForm();
                    },
                    isVisible: modalVisible,
                    isSubmitting,
                }}
            >
                <ConditionalWrap
                    condition={!renderInModal}
                    tag={"form"}
                    componentProps={{
                        onSubmit: (e) => {
                            e.preventDefault();
                            submitForm();
                        },
                        className: classes.form,
                        noValidate: true,
                        "aria-label": t(title ?? "Add/Edit User"),
                    }}
                >
                    <div ref={scrollRef}></div>
                    <div>
                        {topLevelErrors && topLevelErrors.length > 0 && (
                            <Message
                                type="error"
                                stringContents={topLevelErrors[0].message}
                                icon={<ErrorIcon />}
                                contents={<ErrorMessages errors={topLevelErrors} />}
                                className={classes.topLevelError}
                            />
                        )}
                        <JsonSchemaForm
                            fieldErrors={fieldErrors}
                            schema={schema!}
                            instance={values}
                            FormControlGroup={DashboardFormControlGroup}
                            FormControl={DashboardFormControl}
                            onChange={setValues}
                            FormGroupWrapper={formGroupWrapper}
                        />
                    </div>
                    {!renderInModal && (
                        <div className={classes.buttonContainer}>
                            <Button
                                className={classes.button}
                                buttonType={ButtonTypes.DASHBOARD_PRIMARY}
                                disabled={isSubmitting}
                                submit
                            >
                                {isSubmitting ? <ButtonLoader /> : t("Save")}
                            </Button>
                        </div>
                    )}
                </ConditionalWrap>
            </ConditionalWrap>
        </ErrorBoundary>
    );
}
