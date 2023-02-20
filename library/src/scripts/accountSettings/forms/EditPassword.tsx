/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, {
    useState,
    useMemo,
    ReactNode,
    useImperativeHandle,
    MutableRefObject,
    forwardRef,
    useEffect,
} from "react";
import InputBlock from "@library/forms/InputBlock";
import PasswordInput from "@library/forms/PasswordInput";
import { ApproveIcon } from "@library/icons/common";
import { t } from "@vanilla/i18n";
import { IAccountModalForm, IAccountSettingFormHandle } from "@library/accountSettings/AccountSettingsModal";
import Translate from "@library/content/Translate";
import { StatusIndicator } from "@library/accountSettings/StatusIndicator";
import { accountSettingsClasses } from "@library/accountSettings/AccountSettings.classes";
import { IError } from "@library/errorPages/CoreErrorMessages";
import { usePatchUser } from "@library/features/users/userHooks";
import { LoadStatus } from "@library/@types/api/core";
import { useToast } from "@library/features/toaster/ToastContext";
import Message from "@library/messages/Message";
import { useAccountSettings } from "@library/accountSettings/AccountSettingsContext";

interface IProps extends IAccountModalForm {}

export const EditPassword = forwardRef(function PasswordEditImpl(
    props: IProps,
    ref: MutableRefObject<IAccountSettingFormHandle>,
) {
    const { setIsSaving, setIsSuccess, setIsFormDirty } = props;
    const { viewingUserID, isViewingSelf, canEditUsers, minPasswordLength } = useAccountSettings();
    const { patchUser, patchErrors, patchStatus } = usePatchUser(viewingUserID);
    const classes = accountSettingsClasses();

    const [currentPassword, setCurrentPassword] = useState<string>("");
    const [newPassword, setNewPassword] = useState<string>("");
    const [confirmPassword, setConfirmPassword] = useState<string>("");
    const toast = useToast();

    const skipCurrentPassword = useMemo(() => {
        // If you are editing your own information, you need to provide a confirmation password
        if (isViewingSelf) {
            return false;
        }
        // If you are editing another members information, it depends on your permissions
        return canEditUsers;
    }, [canEditUsers, isViewingSelf]);

    // Sync dirty state
    useEffect(() => {
        const isDirty = [!!currentPassword.length, !!newPassword.length, !!confirmPassword.length].some(
            (fieldValues) => fieldValues === true,
        );
        setIsFormDirty(isDirty);
    }, [confirmPassword, currentPassword, newPassword, setIsFormDirty]);

    const confirmNote = useMemo<string | ReactNode | null>(() => {
        if (newPassword.length > 0 && confirmPassword === "") {
            return t("This must match the new password field");
        }

        if (confirmPassword.length > 0 && newPassword.length > 0 && confirmPassword === newPassword) {
            return (
                <StatusIndicator
                    className={classes.passwordMatchAdjustments}
                    statusText={t("Passwords Match")}
                    icon={<ApproveIcon className={classes.verified} />}
                />
            );
        }

        return null;
    }, [newPassword, confirmPassword]);

    const confirmError = useMemo<IError[]>(() => {
        let errors: IError[] = [];

        if (confirmPassword !== "" && confirmPassword !== newPassword) {
            errors.push({
                message: t("New password does not match. Please reconfirm your new password."),
            });
        }

        return errors;
    }, [confirmPassword, newPassword]);

    // Sync save status with parent modal
    useEffect(() => {
        switch (patchStatus) {
            case LoadStatus.LOADING:
                setIsSaving(true);
                break;
            case LoadStatus.SUCCESS:
                setIsSaving(false);
                setIsSuccess(true);
                toast.addToast({
                    autoDismiss: true,
                    body: <>{t("Password changed successfully.")}</>,
                });
                break;

            default:
                setIsSaving(false);
                setIsSuccess(false);
                break;
        }
    }, [patchStatus, patchErrors]);

    // Handle save
    useImperativeHandle(
        ref,
        () => ({
            onSave: () => {
                if (confirmPassword === newPassword) {
                    patchUser({
                        userID: viewingUserID,
                        password: newPassword,
                        ...(currentPassword && { passwordConfirmation: currentPassword }),
                    });
                }
            },
        }),
        [confirmPassword, newPassword, patchUser, viewingUserID, currentPassword],
    );

    return (
        <>
            {patchErrors && (
                <Message className={classes.topLevelErrors} type={"error"} stringContents={patchErrors.message} />
            )}
            {!skipCurrentPassword && (
                <InputBlock
                    label={t("Current Password")}
                    errors={patchErrors?.errors?.passwordConfirmation}
                    extendErrorMessage
                >
                    <PasswordInput
                        id={"current-password"}
                        showUnmask
                        onChange={(event) => setCurrentPassword(event.target.value)}
                        value={currentPassword}
                        aria-label={t("Current Password")}
                        required
                    />
                </InputBlock>
            )}
            <InputBlock
                label={t("New Password")}
                noteAfterInput={
                    !patchErrors?.errors?.password && (
                        <Translate source="Your new password must be at least <0/> characters" c0={minPasswordLength} />
                    )
                }
                errors={patchErrors?.errors?.password}
                extendErrorMessage
            >
                <PasswordInput
                    id={"new-password"}
                    showUnmask
                    onChange={(event) => setNewPassword(event.target.value)}
                    value={newPassword}
                    hasError={newPassword.length > 0 && newPassword.length < minPasswordLength}
                    errorTooltip={
                        <Translate source="Your new password must be at least <0/> characters" c0={minPasswordLength} />
                    }
                    aria-label={t("New Password")}
                    required
                />
            </InputBlock>
            <InputBlock
                label={t("Confirm New Password")}
                noteAfterInput={confirmNote}
                errors={confirmError}
                extendErrorMessage
            >
                <PasswordInput
                    id={"confirm-new-password"}
                    showUnmask
                    onChange={(event) => setConfirmPassword(event.target.value)}
                    value={confirmPassword}
                    hasError={confirmPassword !== "" && confirmPassword !== newPassword}
                    aria-label={t("Confirm New Password")}
                    required
                />
            </InputBlock>
        </>
    );
});

export default EditPassword;
