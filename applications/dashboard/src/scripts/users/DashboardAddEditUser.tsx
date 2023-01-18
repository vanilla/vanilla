/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */
import { ProfileField } from "@dashboard/userProfiles/types/UserProfiles.types";
import dashboardAddEditUserClasses from "@dashboard/users/DashboardAddEditUser.classes";
import DashboardAddEditUserModal from "@dashboard/users/DashboardAddEditUserModal";
import {
    ADD_USER_EMPTY_INITIAL_VALUES,
    mapEditInitialValuesToSchemaFormat,
    userSchema,
} from "@dashboard/users/dashboardAddEditUtils";
import { cx } from "@emotion/css";
import { IUser } from "@library/@types/api/users";
import Translate from "@library/content/Translate";
import { usePatchUser } from "@library/features/users/userHooks";
import Button from "@library/forms/Button";
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

export interface IUserDataProps extends Partial<IUser> {
    password?: string;
}
export interface IDashboardAddEditProps {
    headingTitle?: string;
    ranks?: Record<number, string>;
    userData?: IUserDataProps;
    profileFields?: ProfileField[];
    passwordLength?: number;
    forceModalVisibility?: boolean; // for storybook purposes
}

export default function DashboardAddEditUser(props: IDashboardAddEditProps) {
    const { userData, ranks, passwordLength } = props;
    const [isVisible, setIsVisible] = useState(props.forceModalVisibility || false);
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

    const schema = userSchema(isEdit, ranks, onGeneratePasswordClick, newPasswordID);
    const commonProps = {
        schema: schema,
        isVisible: isVisible,
        setIsVisible: setIsVisible,
        formGroupWrapper: formGroupWrapper,
        profileFields: props.profileFields,
        setNeedsReload: setNeedsReload,
        newPasswordFieldID: newPasswordID,
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

    return (
        <header className={"header-block"}>
            {props.headingTitle && <h1>{t(props.headingTitle ?? "Manage Users")}</h1>}
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
                <DashboardAddEditUserModal
                    initialValues={ADD_USER_EMPTY_INITIAL_VALUES}
                    title={t("Add User")}
                    {...commonProps}
                />
            )}
        </header>
    );
}

interface IDashboardEditUserProps extends React.ComponentProps<typeof DashboardAddEditUserModal> {
    renderModal?: boolean;
    setRenderModal: (v) => void;
    userID: IUser["userID"];
    setNeedsReload: (needsReload: boolean) => void;
    needsReload: boolean;
    reloadMessageComponent: React.ReactNode;
}

function DashboardEditUser(props: IDashboardEditUserProps) {
    const { userID, renderModal, setRenderModal, needsReload, reloadMessageComponent, ...otherProps } = props;
    const { setIsVisible, isVisible } = otherProps;
    const { patchUser } = usePatchUser(userID);

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
            {needsReload && (
                <ToolTip label={t("Reload the page to access recent updates.")}>
                    <span>{button}</span>
                </ToolTip>
            )}
            {needsReload && reloadMessageComponent}
            {!needsReload && button}
            {renderModal && <DashboardAddEditUserModal userID={userID} requestFn={patchUser} {...otherProps} />}
        </>
    );
}
