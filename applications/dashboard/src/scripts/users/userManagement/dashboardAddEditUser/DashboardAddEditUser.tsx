/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */
import { ProfileField } from "@dashboard/userProfiles/types/UserProfiles.types";
import dashboardAddEditUserClasses from "@dashboard/users/userManagement/dashboardAddEditUser/DashboardAddEditUser.classes";
import DashboardAddEditUserForm, {
    DashboardAddEditUserFormValues,
} from "@dashboard/users/userManagement/dashboardAddEditUser/DashboardAddEditUserForm";
import {
    mapUserDataToFormValues,
    getUserSchema,
    mergeUserSchemaWithProfileFieldsSchema,
    mappedFormValuesForApiRequest,
} from "@dashboard/users/userManagement/dashboardAddEditUser/dashboardAddEditUtils";
import { cx } from "@emotion/css";
import { IUser } from "@library/@types/api/users";
import Translate from "@library/content/Translate";
import { useCurrentUserID } from "@library/features/users/userHooks";
import Button from "@library/forms/Button";
import LinkAsButton from "@library/routing/LinkAsButton";
import { ButtonTypes } from "@library/forms/buttonTypes";
import gdn from "@library/gdn";
import { EditIcon } from "@library/icons/common";
import Message from "@library/messages/Message";
import { messagesClasses } from "@library/messages/messageStyles";
import { ToolTip } from "@library/toolTip/ToolTip";
import { useUniqueID } from "@library/utility/idUtils";
import { t } from "@vanilla/i18n";
import { Icon } from "@vanilla/icons";
import { JsonSchema, JsonSchemaForm } from "@vanilla/json-schema-forms";
import React, { useMemo, useState } from "react";
import { IRole } from "@dashboard/roles/roleTypes";
import { useAddUser, useUpdateUser } from "@dashboard/users/userManagement/UserManagement.hooks";
import SmartLink from "@library/routing/links/SmartLink";
import { useToast } from "@library/features/toaster/ToastContext";
import { usePermissionsContext } from "@library/features/users/PermissionsContext";
import { mapProfileFieldsToSchema } from "@library/editProfileFields/utils";
import {
    UserProfileFieldsContextProvider,
    useUserProfileFieldsContext,
} from "@dashboard/userProfiles/state/UserProfileFieldsContext";
import { IPatchUserParams, IPostUserParams } from "@library/features/users/UserActions";

export interface IUserDataProps extends Omit<Partial<IUser>, "roles"> {
    roles?: { [k: IRole["roleID"]]: IRole["name"] };
    password?: string;
}
export interface IDashboardAddEditProps {
    headingTitle?: string;
    ranks?: Record<number, string>;
    userData?: IUserDataProps;
    profileFields?: ProfileField[];
    passwordLength?: number;
    forceModalVisibility?: boolean; // for storybook purposes
    isAddEditUserPage?: boolean;
    newUserManagement?: boolean; // cleanup NewUserManagement, will be gone here https://higherlogic.atlassian.net/browse/VNLA-4044 once we permanently release that page
    extraActions?: React.ReactNode;
}

export function DashboardEditSelf(props: { text: string }) {
    return (
        <>
            {props.text}
            <LinkAsButton to={"/profile/account-privacy"} buttonType={ButtonTypes.TEXT_PRIMARY}>
                {t("Account & Privacy")}
            </LinkAsButton>
        </>
    );
}

export default function DashboardAddEditUser(props: IDashboardAddEditProps) {
    const { userData, ranks, passwordLength, isAddEditUserPage, newUserManagement, profileFields } = props;
    const [needsReload, setNeedsReload] = useState<boolean>(false);
    const [generatedNewPassword, setGeneratedNewPassword] = useState<string>("");
    const newPasswordFieldID = useUniqueID("new_password");
    const classes = dashboardAddEditUserClasses(newPasswordFieldID);
    const isEdit = !!userData;
    const [modalVisible, setModalVisible] = useState(props.forceModalVisibility || false);
    const toast = useToast();

    const onGeneratePasswordClick = () => {
        const password = gdn.generateString(passwordLength ?? 12);

        //not using ref here to avoid validation errors when it passed through schema
        const newPasswordField = document.getElementById(newPasswordFieldID) as HTMLInputElement;
        if (newPasswordField) {
            newPasswordField.value = password;
            setGeneratedNewPassword(password);
        }
    };

    const formGroupNames = ["email", "privacy"];

    if (isEdit) {
        formGroupNames.push("roles", "passwordOptions");
    }

    const formGroupWrapper: React.ComponentProps<typeof JsonSchemaForm>["FormGroupWrapper"] = function (props) {
        if (
            props.groupName &&
            formGroupNames.map((name) => name.toLowerCase()).includes(props.groupName.toLowerCase())
        ) {
            return props.groupName === "privacy" ? (
                //need to do some custom stuff here to achieve inline header for a form group
                <div className={cx(classes.unifiedFormGroupWrapper, "form-group")}>
                    <span className={"label-wrap"}>{props.header}</span>
                    <div className={cx(classes.unifiedFormGroup, "input-wrap")}>{props.children}</div>
                </div>
            ) : (
                <div className={classes.unifiedFormGroup}>{props.children}</div>
            );
        }
        return <>{props.children}</>;
    };

    const currentUserID = useCurrentUserID();
    const isOwnUser = currentUserID === userData?.userID;
    const userSchema = getUserSchema(isEdit, isOwnUser, ranks, onGeneratePasswordClick, newPasswordFieldID);

    const { hasPermission } = usePermissionsContext();

    const profileFieldsSchema = useMemo<JsonSchema | undefined>(() => {
        const userCanEdit = hasPermission("users.edit");
        if (profileFields) {
            return mapProfileFieldsToSchema(profileFields, { userCanEdit });
        }
    }, [profileFields]);

    const { mutateAsync: addUser } = useAddUser();

    const schema = profileFieldsSchema
        ? mergeUserSchemaWithProfileFieldsSchema(userSchema, profileFieldsSchema)
        : undefined;

    function handleCloseModal() {
        setModalVisible(false);
    }

    const reloadPageMessage = t("Reload the page to see recent updates.");
    const reloadMessageComponent = (
        <Message
            isFixed
            icon={<Icon size="compact" icon={"status-warning"} className={messagesClasses().errorIcon} />}
            contents={<div className={messagesClasses().content}>{reloadPageMessage}</div>}
            confirmText={t("Reload")}
            onConfirm={() => {
                window.location.reload();
            }}
            stringContents={reloadPageMessage}
            className={classes.message}
        />
    );

    const commonFormProps = {
        renderInModal: !isAddEditUserPage,
        generatedNewPassword: generatedNewPassword,
        modalVisible,
        handleCloseModal,
        schema,
        formGroupWrapper,
        newPasswordFieldID,
    };

    if (isEdit && userData?.userID) {
        const userID = userData!.userID;

        const commonEditFormProps = {
            ...commonFormProps,
            userID,
            initialValues: mapUserDataToFormValues(userData as IUserDataProps, ranks),
            title: t("Edit User"),
            onSubmitSuccess: function () {
                toast.addToast({
                    dismissible: true,
                    autoDismiss: true,
                    body: t(`User successfully updated.`),
                });
                if (!newUserManagement) {
                    setNeedsReload(true);
                }
                setModalVisible(false);
            },
        };

        const button = (
            <Button
                onClick={() => {
                    setModalVisible(true);
                }}
                buttonType={ButtonTypes.ICON_COMPACT}
                disabled={needsReload}
            >
                <EditIcon />
            </Button>
        );

        return (
            <>
                {needsReload && !isAddEditUserPage && (
                    <ToolTip label={t("Reload the page to access recent updates.")}>
                        <span>{button}</span>
                    </ToolTip>
                )}
                {needsReload && !isAddEditUserPage && reloadMessageComponent}
                {!needsReload && !isAddEditUserPage && button}
                {(modalVisible || isAddEditUserPage) && (
                    <>
                        {userData?.profileFields ? (
                            <DashboardEditUser_New
                                {...commonEditFormProps}
                                profileFieldsData={userData.profileFields ?? {}}
                            />
                        ) : (
                            <UserProfileFieldsContextProvider userID={userID}>
                                <DashboardEditUser_Legacy {...commonEditFormProps} />
                            </UserProfileFieldsContextProvider>
                        )}
                    </>
                )}
            </>
        );
    }

    const commonAddFormProps = {
        ...commonFormProps,
        title: t("Add User"),
        handleSubmit: async function (values: DashboardAddEditUserFormValues) {
            const formattedValues = mappedFormValuesForApiRequest(values, schema) as IPostUserParams;
            return await addUser(formattedValues);
        },
        onSubmitSuccess: function (data: IUser) {
            toast.addToast({
                dismissible: true,
                body: (
                    <Translate
                        source={"User <0/> successfully added."}
                        c0={<SmartLink to={`/profile/${data.name}`}>{data.name}</SmartLink>}
                    />
                ),
            });
            if (!newUserManagement) {
                setNeedsReload(true);
            }
            handleCloseModal();
        },
    };

    if (isAddEditUserPage && !isEdit) {
        return <DashboardAddEditUserForm {...commonAddFormProps} renderInModal={false} />;
    }

    return (
        <header className={"header-block"}>
            <h1>{t(props.headingTitle ?? "Manage Users")}</h1>
            <div className={classes.headerActions}>
                {props.extraActions}
                <Button
                    onClick={() => {
                        setModalVisible(true); //we don't want this to be rendered multiple times as child components execute api calls
                    }}
                    buttonType={ButtonTypes.OUTLINE}
                >
                    {t("Add User")}
                </Button>
            </div>
            {needsReload && reloadMessageComponent}
            {!isEdit && <DashboardAddEditUserForm {...commonAddFormProps} renderInModal={true} />}
        </header>
    );
}

// this one uses data from Redux, and assumes it's wrapped in a UserProfileFieldsContextProvider which fetches the required user profile fields data
// it will soon be made obsolete.
function DashboardEditUser_Legacy(
    props: Omit<React.ComponentProps<typeof DashboardAddEditUserForm>, "handleSubmit"> & {
        userID: IUser["userID"];
        initialValues: NonNullable<React.ComponentProps<typeof DashboardAddEditUserForm>["initialValues"]>;
    },
) {
    const { userProfileFields } = useUserProfileFieldsContext();
    let { userID, initialValues, ...otherProps } = props;
    const { schema } = otherProps;
    const { mutateAsync: updateUser } = useUpdateUser(userID);

    initialValues = {
        ...initialValues,
        profileFields: {
            ...initialValues?.profileFields,
            ...(userProfileFields.data ?? {}),
        },
    };

    async function handleSubmit(values: DashboardAddEditUserFormValues) {
        const formattedValues = mappedFormValuesForApiRequest(values, schema);
        return await updateUser(formattedValues as IPatchUserParams);
    }
    return userProfileFields.data ? (
        <DashboardAddEditUserForm {...otherProps} initialValues={initialValues} handleSubmit={handleSubmit} />
    ) : (
        <></>
    );
}

// this one uses data from React-Query and assumes we have the user profile fields data available up-front.
function DashboardEditUser_New(
    props: Omit<React.ComponentProps<typeof DashboardAddEditUserForm>, "handleSubmit"> & {
        userID: IUser["userID"];
        initialValues: NonNullable<React.ComponentProps<typeof DashboardAddEditUserForm>["initialValues"]>;
        profileFieldsData: IUser["profileFields"];
    },
) {
    let { profileFieldsData, userID, initialValues, ...otherProps } = props;
    const { schema } = otherProps;
    const { mutateAsync: updateUser } = useUpdateUser(userID);

    initialValues = {
        ...initialValues,
        profileFields: {
            ...initialValues?.profileFields,
            ...(profileFieldsData ?? {}),
        },
    };

    async function handleSubmit(values: DashboardAddEditUserFormValues) {
        const formattedValues = mappedFormValuesForApiRequest(values, schema);
        return await updateUser(formattedValues as IPatchUserParams);
    }
    return <DashboardAddEditUserForm {...otherProps} initialValues={initialValues} handleSubmit={handleSubmit} />;
}
