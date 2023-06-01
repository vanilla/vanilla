/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */
import { DashboardFormControl, DashboardFormControlGroup } from "@dashboard/forms/DashboardFormControl";
import { useProfileFieldByUserID } from "@dashboard/userProfiles/state/UserProfiles.hooks";
import { ProfileField } from "@dashboard/userProfiles/types/UserProfiles.types";
import dashboardAddEditUserClasses from "@dashboard/users/userManagement/dashboardAddEditUser/DashboardAddEditUser.classes";
import DashboardAddEditUserModal from "@dashboard/users/userManagement/dashboardAddEditUser/DashboardAddEditUserModal";
import {
    mappedFormValuesForApiRequest,
    mergeProfileFieldsSchema,
} from "@dashboard/users/userManagement/dashboardAddEditUser/dashboardAddEditUtils";
import { useAddUser } from "@dashboard/users/userManagement/UserManagement.hooks";
import { LoadStatus } from "@library/@types/api/core";
import { IUser } from "@library/@types/api/users";
import Translate from "@library/content/Translate";
import { mapProfileFieldsToSchema, transformUserProfileFieldsData } from "@library/editProfileFields/utils";
import { IError } from "@library/errorPages/CoreErrorMessages";
import { ErrorBoundary } from "@library/errorPages/ErrorBoundary";
import { useToast } from "@library/features/toaster/ToastContext";
import { usePermissionsContext } from "@library/features/users/PermissionsContext";
import { IPatchUserParams } from "@library/features/users/UserActions";
import { usePostUser } from "@library/features/users/userHooks";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import ErrorMessages from "@library/forms/ErrorMessages";
import { ErrorIcon } from "@library/icons/common";
import ButtonLoader from "@library/loaders/ButtonLoader";
import Loader from "@library/loaders/Loader";
import Message from "@library/messages/Message";
import SmartLink from "@library/routing/links/SmartLink";
import { MutateOptions } from "@tanstack/react-query";
import { t } from "@vanilla/i18n";
import { IFieldError, IJsonSchemaFormHandle, JsonSchema, JsonSchemaForm } from "@vanilla/json-schema-forms";
import React, { useEffect, useLayoutEffect, useMemo, useRef, useState } from "react";

interface IProps {
    userID?: IUser["userID"];
    isVisible: boolean;
    setIsVisible: (visibility) => void;
    initialValues?: Record<string, any>;
    title?: string;
    schema: JsonSchema;
    formGroupWrapper?: React.ComponentProps<typeof JsonSchemaForm>["FormGroupWrapper"];
    profileFields?: ProfileField[];
    requestFn?: (
        params: IPatchUserParams,
        options?: MutateOptions<any, unknown, Partial<IUser>, unknown> | undefined,
    ) => Promise<any>;
    setNeedsReload: (needsReload: boolean) => void;
    newPasswordFieldID?: string;
    generatedNewPassword?: string;
    isAddEditUserPage?: boolean;
    newUserManagement?: boolean; // cleanup NewUserManagement, will be gone here https://higherlogic.atlassian.net/browse/VNLA-4044 once we permanently release that page
}

export default function DashboardAddEditUserForm(props: IProps) {
    const {
        userID,
        isVisible,
        setIsVisible,
        title,
        schema,
        initialValues,
        formGroupWrapper,
        profileFields,
        requestFn,
        newPasswordFieldID,
        generatedNewPassword,
        isAddEditUserPage,
        newUserManagement,
    } = props;
    const isEdit = userID != null;
    const [values, setValues] = useState(initialValues ?? {});
    const [isSubmitting, setIsSubmitting] = useState<boolean>(false);
    const [topLevelErrors, setTopLevelErrors] = useState<IError[]>([]);
    const [fieldErrors, setFieldErrors] = useState<Record<string, IFieldError[]>>({});
    const classes = dashboardAddEditUserClasses(newPasswordFieldID);
    const schemaFormRef = useRef<IJsonSchemaFormHandle | null>(null);
    const scrollRef = useRef<HTMLDivElement>(null);
    const { hasPermission } = usePermissionsContext();

    // cleanup NewUserManagement, will be gone here https://higherlogic.atlassian.net/browse/VNLA-4044 once we permanently release that page
    //as useGetUsers() should already have user profile fields for each user
    const userProfileFields = useProfileFieldByUserID(userID as number);

    const profileFieldSchema = useMemo<JsonSchema | null>(() => {
        const userCanEdit = hasPermission("users.edit");
        if (profileFields) {
            return mapProfileFieldsToSchema(profileFields, { userCanEdit });
        }
        return null;
    }, [profileFields]);

    const finalSchema = mergeProfileFieldsSchema(schema, profileFieldSchema);
    const userProfileFieldsLoaded = userProfileFields.status === LoadStatus.SUCCESS && userProfileFields.data;

    const userProfileFieldsData = useMemo(() => {
        if (userProfileFieldsLoaded) {
            return transformUserProfileFieldsData(userProfileFields.data ?? {}, profileFields ?? []);
        }
    }, [userProfileFields.status]);

    useEffect(() => {
        if (userProfileFieldsLoaded) {
            setValues({ ...values, profileFields: userProfileFieldsData });
        }
    }, [userProfileFields.status]);

    useEffect(() => {
        if (generatedNewPassword) {
            setValues({
                ...values,
                passwordOptions: {
                    ...values.passwordOptions,
                    newPassword: generatedNewPassword,
                },
            });
        }
    }, [generatedNewPassword]);

    const postUser = usePostUser();
    const { mutateAsync: addUser } = useAddUser();

    const toast = useToast();
    const toastBody = <>{t(`User successfully ${isEdit ? "updated" : "added"}.`)}</>;

    const showErrors = (error: any) => {
        setTopLevelErrors([
            {
                message: error.message,
            },
        ]);
        setFieldErrors(error.errors ?? []);
    };

    const closeModalAndShowToast = (customToastBody?: React.ReactNode) => {
        const hasCustomToastBody = !!customToastBody;
        setIsSubmitting(false);
        setIsVisible(false);
        toast.addToast({
            dismissible: true,
            autoDismiss: !hasCustomToastBody,
            body: customToastBody ?? toastBody,
        });
    };

    useEffect(() => {
        if (!isVisible) {
            if (!isAddEditUserPage) {
                setValues(initialValues ? { ...initialValues, profileFields: userProfileFieldsData } : {});
            }
            setFieldErrors({});
            setTopLevelErrors([]);
            setIsSubmitting(false);
        }
    }, [isVisible, initialValues]);

    // cleanup NewUserManagement, this became a bit messy, but should be simplified cleared up
    // will be done here https://higherlogic.atlassian.net/browse/VNLA-4044 once we permanently release that page
    const handleSubmit = async () => {
        try {
            setIsSubmitting(true);
            const mappedFormValues = mappedFormValuesForApiRequest(values) as Partial<IUser> | IPatchUserParams;
            if (requestFn) {
                await requestFn(mappedFormValues as IPatchUserParams);
            } else {
                !newUserManagement
                    ? await postUser(mappedFormValues as Partial<IUser>)
                    : await addUser(mappedFormValues, {
                          onSuccess(data) {
                              const customToastBody = (
                                  <Translate
                                      source={"User <0/> successfully added."}
                                      c0={<SmartLink to={`/profile/${data.name}`}>{data.name}</SmartLink>}
                                  />
                              );
                              closeModalAndShowToast(customToastBody);
                          },
                          onError(error) {
                              showErrors(error);
                          },
                      });
            }

            if (!newUserManagement || requestFn) {
                closeModalAndShowToast();
            }
            if (!newUserManagement) {
                props.setNeedsReload(true);
            }
        } catch (err) {
            showErrors(err);
        } finally {
            setIsSubmitting(false);
        }
    };

    useLayoutEffect(() => {
        if (topLevelErrors && topLevelErrors.length > 0) {
            scrollRef.current?.scrollIntoView({ behavior: "smooth" });
        }
    }, [topLevelErrors]);

    if (!profileFieldSchema) {
        return <Loader />;
    }

    return (
        <ErrorBoundary>
            {isAddEditUserPage ? (
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        handleSubmit();
                    }}
                    noValidate
                    className={classes.modalForm}
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
                            schema={finalSchema}
                            instance={values}
                            FormControlGroup={DashboardFormControlGroup}
                            FormControl={DashboardFormControl}
                            onChange={setValues}
                            FormGroupWrapper={formGroupWrapper}
                            ref={schemaFormRef}
                        />
                    </div>
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
                </form>
            ) : (
                <DashboardAddEditUserModal
                    values={values}
                    setValues={setValues}
                    handleSubmit={handleSubmit}
                    topLevelErrors={topLevelErrors}
                    fieldErrors={fieldErrors}
                    schema={finalSchema}
                    isSubmitting={isSubmitting}
                    isVisible={isVisible}
                    setIsVisible={setIsVisible}
                    formGroupWrapper={formGroupWrapper}
                    title={title}
                />
            )}
        </ErrorBoundary>
    );
}
