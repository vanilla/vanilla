/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useState, useEffect, useMemo, ReactNode } from "react";
import { accountSettingsClasses } from "@library/accountSettings/AccountSettings.classes";
import Heading from "@library/layout/Heading";
import { t } from "@vanilla/i18n";
import CheckBox from "@library/forms/Checkbox";
import { IUser } from "@library/@types/api/users";
import { AccountSettingsDetail, AccountSettingType } from "@library/accountSettings/AccountSettingsDetail";
import { usePatchUser } from "@library/features/users/userHooks";
import { LoadStatus } from "@library/@types/api/core";
import { useToast } from "@library/features/toaster/ToastContext";
import { IPatchUserParams } from "@library/features/users/UserActions";
import { AccountSettingsModal } from "@library/accountSettings/AccountSettingsModal";
import ConditionalWrap from "@library/layout/ConditionalWrap";
import { ToolTip } from "@library/toolTip/ToolTip";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { Icon } from "@vanilla/icons";
import { StatusIndicator } from "@library/accountSettings/StatusIndicator";
import { ApproveIcon } from "@library/icons/common";
import { LoadingRectangle } from "@library/loaders/LoadingRectangle";
import { AccountSettingProvider, useAccountSettings } from "@library/accountSettings/AccountSettingsContext";
import { ErrorPageBoundary } from "@library/errorPages/ErrorPageBoundary";

export interface IAccountSettingsProps {
    /** The userID of the user to be edited */
    userID: IUser["userID"];
}

enum PrivacyOptions {
    PROFILE = "private",
    EMAIL = "showEmail",
}

/**
 * Be mindful that this page has been wrapped in a context below
 */
export function AccountSettingsImpl() {
    const classes = accountSettingsClasses();
    const { viewingUserID, viewingUser, isRegistrationConnect, canEditUsernames, canEditUsers, isViewingSelf } =
        useAccountSettings();
    const { patchUser, patchStatus } = usePatchUser(viewingUserID);
    const toast = useToast();

    const [username, setUsername] = useState<IUser["name"] | ReactNode>(
        <LoadingRectangle width={100} className={classes.loadingRectAdjustments} />,
    );
    const [email, setEmail] = useState<IUser["email"] | ReactNode>(
        <LoadingRectangle width={160} className={classes.loadingRectAdjustments} />,
    );
    const [password, setPassword] = useState<ReactNode>(
        <LoadingRectangle width={126} className={classes.loadingRectAdjustments} />,
    );
    const [emailConfirmed, setEmailConfirmed] = useState<IUser["emailConfirmed"] | null>(null);
    const [showEmail, setShowEmail] = useState<IUser["showEmail"]>(false);
    const [showProfile, setShowProfile] = useState<IUser["private"]>(false);

    const [editType, setEditType] = useState<AccountSettingType | null>(null);
    const [visibility, setVisibility] = useState<boolean>(false);

    const handleEditClick = (type: AccountSettingType) => {
        setEditType(type);
        setVisibility(true);
    };

    useEffect(() => {
        if (viewingUser) {
            setUsername(viewingUser?.name ?? username);
            setEmail(viewingUser?.email ?? email);
            setEmailConfirmed(viewingUser?.emailConfirmed ?? emailConfirmed);
            setShowEmail(viewingUser?.showEmail ?? showEmail);
            setShowProfile(!viewingUser?.private ?? showProfile);
            setPassword(
                <span aria-label={t("masked password")} className="password">
                    ﹡﹡﹡﹡﹡﹡﹡﹡﹡﹡﹡﹡
                </span>,
            );
        }
    }, [viewingUser]);

    const togglePrivacy = async (propName: PrivacyOptions, value: boolean) => {
        const patchParams: IPatchUserParams = {
            userID: viewingUserID,
            ...(propName === PrivacyOptions.PROFILE && { private: !value }),
            ...(propName === PrivacyOptions.EMAIL && { showEmail: value }),
        };

        const toastMessage = await patchUser(patchParams)
            .then((data) => {
                switch (propName) {
                    case PrivacyOptions.PROFILE: {
                        return data.private
                            ? "Your profile will not be publicly displayed."
                            : "Your profile will be publicly displayed.";
                    }
                    case PrivacyOptions.EMAIL: {
                        return data.showEmail
                            ? "Your email will be publicly displayed."
                            : "Your email will not be publicly displayed.";
                    }
                    default: {
                        return null;
                    }
                }
            })
            .catch((error) => {
                toast.addToast({
                    autoDismiss: false,
                    body: <>{t("An error occurred updating your privacy setting.")}</>,
                });
            });

        toastMessage &&
            toast.addToast({
                autoDismiss: true,
                body: <>{t(toastMessage)}</>,
            });
    };

    const wrappedEditButton = (condition: boolean, tooltip: string, ariaLabel: string, type: AccountSettingType) => {
        return (
            <ConditionalWrap condition={!condition} component={ToolTip} componentProps={{ label: tooltip }}>
                {/* This span is required for the conditional tooltip */}
                <span>
                    <Button
                        buttonType={ButtonTypes.ICON}
                        className={classes.infoEdit}
                        ariaLabel={ariaLabel}
                        onClick={() => handleEditClick(type)}
                        disabled={!condition}
                    >
                        <Icon icon="dashboard-edit" />
                    </Button>
                </span>
            </ConditionalWrap>
        );
    };

    const emailConfirmationStatus = useMemo(() => {
        if (emailConfirmed !== null) {
            return (
                <span className={classes.emailVerify}>
                    <StatusIndicator
                        icon={
                            emailConfirmed ? (
                                <ApproveIcon className={classes.verified} />
                            ) : (
                                <Icon icon="status-warning" className={classes.unverified} />
                            )
                        }
                        statusText={emailConfirmed ? t("Confirmed") : t("Needs Confirmation")}
                    />
                </span>
            );
        }
        return null;
    }, [emailConfirmed]);

    return (
        <section>
            <Heading depth={1} renderAsDepth={1}>
                {t("Account & Privacy Settings")}
            </Heading>
            <Heading depth={2} className={classes.subtitle}>
                {t("Your Account")}
            </Heading>
            <AccountSettingsDetail
                label={t("Username")}
                value={username}
                afterValue={wrappedEditButton(
                    canEditUsernames || isViewingSelf,
                    t("You don't have the permission to edit your username"),
                    t("Edit username"),
                    AccountSettingType.USERNAME,
                )}
            />
            <AccountSettingsDetail
                label={t("Email")}
                value={email}
                afterLabel={emailConfirmationStatus}
                afterValue={wrappedEditButton(
                    (!isRegistrationConnect && canEditUsers) || isViewingSelf,
                    t("You don't have the permission to edit your email"),
                    t("Edit email"),
                    AccountSettingType.EMAIL,
                )}
            />
            <AccountSettingsDetail
                label={t("Password")}
                value={password}
                afterValue={wrappedEditButton(
                    (!isRegistrationConnect && canEditUsers) || isViewingSelf,
                    t("You don't have the permission to edit your password"),
                    t("Change password"),
                    AccountSettingType.PASSWORD,
                )}
            />
            <Heading depth={2} className={classes.subtitle}>
                {t("Privacy")}
            </Heading>
            <CheckBox
                label={t("Display my profile publicly")}
                labelBold={false}
                checked={showProfile}
                disabled={patchStatus === LoadStatus.LOADING}
                onChange={(event) => togglePrivacy(PrivacyOptions.PROFILE, event.target.checked)}
                className={classes.fitWidth}
                hugLeft
            />
            <CheckBox
                id="email-privacy"
                label={t("Display my email publicly")}
                labelBold={false}
                checked={showEmail}
                disabled={patchStatus === LoadStatus.LOADING}
                onChange={(event) => togglePrivacy(PrivacyOptions.EMAIL, event.target.checked)}
                className={classes.fitWidth}
                hugLeft
            />
            <AccountSettingsModal
                key={editType} // Force a remount when this changes to clear out form state.
                editType={editType}
                visibility={visibility}
                onVisibilityChange={setVisibility}
            />
        </section>
    );
}

export function AccountSettings(props: IAccountSettingsProps) {
    return (
        <AccountSettingProvider userID={props.userID}>
            <ErrorPageBoundary>
                <AccountSettingsImpl />
            </ErrorPageBoundary>
        </AccountSettingProvider>
    );
}

export default AccountSettings;
