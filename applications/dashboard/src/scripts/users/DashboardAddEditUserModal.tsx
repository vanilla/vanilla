/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */
import { DashboardFormControl, DashboardFormControlGroup } from "@dashboard/forms/DashboardFormControl";
import { useProfileFieldByUserID } from "@dashboard/userProfiles/state/UserProfiles.hooks";
import { ProfileField, ProfileFieldDataType } from "@dashboard/userProfiles/types/UserProfiles.types";
import dashboardAddEditUserClasses from "@dashboard/users/DashboardAddEditUser.classes";
import { mappedFormValuesForApiRequest, mergeProfileFieldsSchema } from "@dashboard/users/dashboardAddEditUtils";
import { cx } from "@emotion/css";
import { LoadStatus } from "@library/@types/api/core";
import { IUser } from "@library/@types/api/users";
import { formatDateStringIgnoringTimezone, mapProfileFieldsToSchema } from "@library/editProfileFields/utils";
import { IError } from "@library/errorPages/CoreErrorMessages";
import { ErrorBoundary } from "@library/errorPages/ErrorBoundary";
import { useToast } from "@library/features/toaster/ToastContext";
import { IPatchUserParams } from "@library/features/users/UserActions";
import { usePostUser } from "@library/features/users/userHooks";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import ErrorMessages from "@library/forms/ErrorMessages";
import { ErrorIcon } from "@library/icons/common";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import { frameBodyClasses } from "@library/layout/frame/frameBodyStyles";
import FrameFooter from "@library/layout/frame/FrameFooter";
import { frameFooterClasses } from "@library/layout/frame/frameFooterStyles";
import FrameHeader from "@library/layout/frame/FrameHeader";
import ButtonLoader from "@library/loaders/ButtonLoader";
import Loader from "@library/loaders/Loader";
import Message from "@library/messages/Message";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import { t } from "@vanilla/i18n";
import { IFieldError, IJsonSchemaFormHandle, JsonSchema, JsonSchemaForm } from "@vanilla/json-schema-forms";
import React, { useEffect, useLayoutEffect, useMemo, useRef, useState } from "react";

interface IProps {
    userID?: IUser["userID"];
    isVisible: boolean;
    setIsVisible: (visibility) => void;
    initialValues?: JsonSchema;
    title?: string;
    schema: JsonSchema;
    formGroupWrapper?: React.ComponentProps<typeof JsonSchemaForm>["FormGroupWrapper"];
    profileFields?: ProfileField[];
    requestFn?: (params: IPatchUserParams) => Promise<any>;
    setNeedsReload: (needsReload: boolean) => void;
    newPasswordFieldID?: string;
    generatedNewPassword?: string;
}

export default function DashboardAddEditUserModal(props: IProps) {
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
    } = props;
    const isEdit = userID != null;
    const [values, setValues] = useState<JsonSchema>(initialValues ?? {});
    const [isSubmitting, setIsSubmitting] = useState<boolean>(false);
    const [topLevelErrors, setTopLevelErrors] = useState<IError[]>([]);
    const [fieldErrors, setFieldErrors] = useState<Record<string, IFieldError[]>>({});
    const classes = dashboardAddEditUserClasses(newPasswordFieldID);
    const schemaFormRef = useRef<IJsonSchemaFormHandle | null>(null);
    const titleID = `${title}_modal`;
    const userProfileFields = useProfileFieldByUserID(userID as number);
    const scrollRef = useRef<HTMLDivElement>(null);

    const profileFieldSchema = useMemo<JsonSchema | null>(() => {
        if (profileFields) {
            return mapProfileFieldsToSchema(profileFields, true, true);
        }
        return null;
    }, [profileFields]);

    const finalSchema = mergeProfileFieldsSchema(schema, profileFieldSchema);
    const userProfileFieldsLoaded = userProfileFields.status === LoadStatus.SUCCESS && userProfileFields.data;

    useEffect(() => {
        if (userProfileFieldsLoaded) {
            //some tweaks here until dates are the right format in BE, see comment for formatDateStringIgnoringTimezone() in its origin file
            const finalUserProfileFieldsData = { ...userProfileFields.data };
            Object.keys(finalUserProfileFieldsData).forEach((userField) => {
                if (
                    finalUserProfileFieldsData[userField] &&
                    profileFields?.find(
                        (profileField) =>
                            profileField.apiName === userField && profileField.dataType === ProfileFieldDataType.DATE,
                    )
                ) {
                    finalUserProfileFieldsData[userField] = formatDateStringIgnoringTimezone(
                        finalUserProfileFieldsData[userField],
                    );
                }
            });

            setValues({ ...values, profileFields: finalUserProfileFieldsData });
        }
    }, [initialValues, userProfileFields.status]);

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
    const toast = useToast();

    useEffect(() => {
        if (!isVisible) {
            setValues(initialValues ?? {});
            setFieldErrors({});
            setTopLevelErrors([]);
            setIsSubmitting(false);
        }
    }, [isVisible, initialValues]);

    const handleSubmit = async () => {
        try {
            setIsSubmitting(true);

            const mappedFormValues = mappedFormValuesForApiRequest(values) as Partial<IUser> | IPatchUserParams;
            if (requestFn) {
                await requestFn(mappedFormValues as IPatchUserParams);
            } else {
                await postUser(mappedFormValues as Partial<IUser>);
            }
            setIsSubmitting(false);
            setIsVisible(false);
            props.setNeedsReload(true);
            toast.addToast({
                dismissible: true,
                autoDismiss: true,
                body: <>{t(`User successfully ${isEdit ? "updated" : "added"}.`)}</>,
            });
        } catch (err) {
            setTopLevelErrors([
                {
                    message: err.message,
                },
            ]);
            setFieldErrors(err.errors ?? []);
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
            <Modal
                isVisible={isVisible}
                size={ModalSizes.XL}
                exitHandler={() => {
                    setIsVisible(false);
                }}
                titleID={titleID}
            >
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        handleSubmit();
                    }}
                    noValidate
                    className={classes.modalForm}
                >
                    <Frame
                        header={
                            <FrameHeader
                                closeFrame={() => {
                                    setIsVisible(false);
                                }}
                                title={t(title ?? "Add/Edit User")}
                            />
                        }
                        body={
                            <FrameBody>
                                <div ref={scrollRef}></div>
                                <div className={cx("frameBody-contents", frameBodyClasses().contents)}>
                                    {topLevelErrors && topLevelErrors.length > 0 && (
                                        <Message
                                            type="error"
                                            stringContents={topLevelErrors[0].message}
                                            icon={<ErrorIcon />}
                                            contents={<ErrorMessages errors={topLevelErrors} />}
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
                            </FrameBody>
                        }
                        footer={
                            <FrameFooter justifyRight={true}>
                                <Button
                                    className={frameFooterClasses().actionButton}
                                    buttonType={ButtonTypes.TEXT}
                                    onClick={() => {
                                        setIsVisible(false);
                                    }}
                                    disabled={isSubmitting}
                                >
                                    {t("Cancel")}
                                </Button>
                                <Button
                                    disabled={isSubmitting}
                                    className={frameFooterClasses().actionButton}
                                    buttonType={ButtonTypes.TEXT_PRIMARY}
                                    submit
                                >
                                    {isSubmitting ? <ButtonLoader /> : t("Save")}
                                </Button>
                            </FrameFooter>
                        }
                    />
                </form>
            </Modal>
        </ErrorBoundary>
    );
}
