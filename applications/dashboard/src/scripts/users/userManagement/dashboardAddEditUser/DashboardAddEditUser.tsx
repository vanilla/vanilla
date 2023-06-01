/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */
import { ProfileField } from "@dashboard/userProfiles/types/UserProfiles.types";
import dashboardAddEditUserClasses from "@dashboard/users/userManagement/dashboardAddEditUser/DashboardAddEditUser.classes";
import DashboardAddEditUserForm from "@dashboard/users/userManagement/dashboardAddEditUser/DashboardAddEditUserForm";
import {
    ADD_USER_EMPTY_INITIAL_VALUES,
    mapEditInitialValuesToSchemaFormat,
    userSchema,
} from "@dashboard/users/userManagement/dashboardAddEditUser/dashboardAddEditUtils";
import { cx } from "@emotion/css";
import { IUser } from "@library/@types/api/users";
import Translate from "@library/content/Translate";
import { useCurrentUserID, usePatchUser } from "@library/features/users/userHooks";
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
import { JsonSchemaForm } from "@vanilla/json-schema-forms";
import React, { useState } from "react";
import { IRole } from "@dashboard/roles/roleTypes";
import { useUpdateUser } from "@dashboard/users/userManagement/UserManagement.hooks";

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
    const { userData, ranks, passwordLength, isAddEditUserPage, newUserManagement } = props;
    const [isVisible, setIsVisible] = useState(props.forceModalVisibility ?? isAddEditUserPage ?? false);
    const [needsReload, setNeedsReload] = useState<boolean>(false);
    const [generatedNewPassword, setGeneratedNewPassword] = useState<string>("");
    const newPasswordID = useUniqueID("new_password");
    const classes = dashboardAddEditUserClasses(newPasswordID);
    const isEdit = !!userData;
    const [renderModal, setRenderModal] = useState(props.forceModalVisibility || false);
    const reloadPageMeassage = "Reload the page to see recent updates.";

    const onGeneratePasswordClick = () => {
        const password = gdn.generateString(passwordLength ?? 12);

        //not using ref here to avoid validation errors when it passed through schema
        const newPasswordField = document.getElementById(newPasswordID) as HTMLInputElement;
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

    const currentUseID = useCurrentUserID();
    const isOwnUser = currentUseID === userData?.userID;
    const schema = userSchema(isEdit, isOwnUser, ranks, onGeneratePasswordClick, newPasswordID);

    const commonProps = {
        schema: schema,
        isVisible: isVisible,
        setIsVisible: setIsVisible,
        formGroupWrapper: formGroupWrapper,
        profileFields: props.profileFields,
        setNeedsReload: setNeedsReload,
        newPasswordFieldID: newPasswordID,
        isAddEditUserPage: isAddEditUserPage,
        newUserManagement: newUserManagement,
    };

    const reloadMessageComponent = (
        <Message
            isFixed={true}
            icon={<Icon size="compact" icon={"status-warning"} className={messagesClasses().errorIcon} />}
            contents={
                <div className={messagesClasses().content}>
                    <Translate source={reloadPageMeassage} />
                </div>
            }
            confirmText={t("Reload")}
            onConfirm={() => {
                window.location.reload();
            }}
            stringContents={t(reloadPageMeassage)}
            className={classes.message}
        />
    );

    if (isEdit && userData?.userID) {
        return (
            <DashboardEditUser
                userID={userData?.userID}
                renderModal={renderModal}
                initialValues={mapEditInitialValuesToSchemaFormat(userData as IUserDataProps, ranks)}
                title={t("Edit User")}
                setRenderModal={setRenderModal}
                needsReload={needsReload}
                reloadMessageComponent={reloadMessageComponent}
                generatedNewPassword={generatedNewPassword}
                {...commonProps}
            />
        );
    }

    if (isAddEditUserPage && !isEdit) {
        return (
            <DashboardAddEditUserForm
                initialValues={ADD_USER_EMPTY_INITIAL_VALUES}
                title={t("Add User")}
                {...commonProps}
                isAddEditUserPage={isAddEditUserPage}
            />
        );
    }

    return (
        <header className={"header-block"}>
            <h1>{t(props.headingTitle ?? "Manage Users")}</h1>
            <Button
                onClick={() => {
                    setIsVisible(!isVisible);
                    setRenderModal(true); //we don't want this to be rendered multiple times as child components execute api calls
                }}
                buttonType={ButtonTypes.OUTLINE}
            >
                {t("Add User")}
            </Button>
            {needsReload && reloadMessageComponent}
            {!isEdit && renderModal && (
                <DashboardAddEditUserForm
                    initialValues={ADD_USER_EMPTY_INITIAL_VALUES}
                    title={t("Add User")}
                    {...commonProps}
                />
            )}
        </header>
    );
}

interface IDashboardEditUserProps extends React.ComponentProps<typeof DashboardAddEditUserForm> {
    renderModal?: boolean;
    setRenderModal: (v) => void;
    userID: IUser["userID"];
    setNeedsReload: (needsReload: boolean) => void;
    needsReload: boolean;
    reloadMessageComponent: React.ReactNode;
}

function DashboardEditUser(props: IDashboardEditUserProps) {
    const {
        userID,
        renderModal,
        setRenderModal,
        needsReload,
        reloadMessageComponent,
        isAddEditUserPage,
        newUserManagement,
        ...otherProps
    } = props;
    const { setIsVisible, isVisible } = otherProps;
    const { patchUser } = usePatchUser(userID);
    const { mutateAsync: updateUser } = useUpdateUser(userID);

    const button = (
        <Button
            onClick={() => {
                setIsVisible(!isVisible);
                setRenderModal(true); //we don't want this to be rendered multiple times as child components execute api calls
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
            {(renderModal || isAddEditUserPage) && (
                <DashboardAddEditUserForm
                    userID={userID}
                    requestFn={newUserManagement ? updateUser : patchUser}
                    isAddEditUserPage={isAddEditUserPage}
                    newUserManagement={newUserManagement}
                    {...otherProps}
                />
            )}
        </>
    );
}
