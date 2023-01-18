/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { LoadStatus } from "@library/@types/api/core";
import { IUser } from "@library/@types/api/users";
import { accountSettingsClasses } from "@library/accountSettings/AccountSettings.classes";
import { useAccountSettings } from "@library/accountSettings/AccountSettingsContext";
import { IAccountModalForm, IAccountSettingFormHandle } from "@library/accountSettings/AccountSettingsModal";
import { useToast } from "@library/features/toaster/ToastContext";
import { usePatchUser } from "@library/features/users/userHooks";
import CheckBox from "@library/forms/Checkbox";
import InputBlock from "@library/forms/InputBlock";
import InputTextBlock from "@library/forms/InputTextBlock";
import PasswordInput from "@library/forms/PasswordInput";
import Paragraph from "@library/layout/Paragraph";
import Message from "@library/messages/Message";
import { t } from "@vanilla/i18n";
import React, { forwardRef, MutableRefObject, useEffect, useImperativeHandle, useMemo, useState } from "react";

interface IProps extends IAccountModalForm {}

export const EditEmail = forwardRef(function EditEmailImpl(
    props: IProps,
    ref: MutableRefObject<IAccountSettingFormHandle>,
) {
    const { setIsSaving, setIsFormDirty, setIsSuccess } = props;
    const classes = accountSettingsClasses();

    const { viewingUserID, viewingUser, isViewingSelf, canEditUsers } = useAccountSettings();
    const { patchUser, patchErrors, patchStatus } = usePatchUser(viewingUserID);
    const { addToast } = useToast();

    // Cache the initial email
    const initialEmail = useMemo(() => {
        if (viewingUser) {
            return viewingUser?.email;
        }
        return "";
    }, [viewingUser]);

    // Set up some local state
    const [email, setEmail] = useState<IUser["email"]>(initialEmail);
    const [password, setPassword] = useState<string>("");
    const [verifiedStatus, setVerifiedStatus] = useState<boolean | undefined>(viewingUser?.emailConfirmed);
    const skipPasswordConfirmation = useMemo(() => {
        // If you are editing your own information, you need to provide a confirmation password
        if (isViewingSelf) {
            return false;
        }
        // If you are editing another members information, it depends on your permissions
        return canEditUsers;
    }, [canEditUsers, isViewingSelf]);

    // Set the forms values when editing user resolves
    useEffect(() => {
        if (viewingUser?.email) {
            setEmail(viewingUser?.email);
        }
        setVerifiedStatus(viewingUser?.emailConfirmed);
    }, [viewingUser]);

    // Sync the save status to the parent modal
    useEffect(() => {
        if (patchStatus === LoadStatus.LOADING) {
            setIsSaving(true);
        } else {
            setIsSaving(false);
        }
    }, [patchStatus]);

    // Sync success state to the parent modal
    useEffect(() => {
        if (patchStatus === LoadStatus.SUCCESS) {
            setIsSuccess(true);
            addToast({
                autoDismiss: true,
                body: <>{t("Your email has been updated")}</>,
            });
        }
    }, [patchStatus, setIsSuccess]);

    // Sync dirty state
    useEffect(() => {
        const isDirty = [email !== viewingUser?.email, verifiedStatus !== viewingUser?.emailConfirmed].some(
            (fieldValues) => fieldValues === true,
        );
        setIsFormDirty(isDirty);
    }, [email, verifiedStatus, viewingUser]);

    // Handle save
    useImperativeHandle(
        ref,
        () => ({
            onSave: () => {
                patchUser({
                    userID: viewingUserID,
                    email,
                    ...(verifiedStatus && { emailConfirmed: verifiedStatus }),
                    ...(password && { passwordConfirmation: password }),
                });
            },
        }),
        [patchUser, viewingUserID, email, verifiedStatus, password],
    );
    return (
        <>
            {patchErrors && (
                <Message className={classes.topLevelErrors} type={"error"} stringContents={patchErrors.message} />
            )}
            <Paragraph className={classes.instructions}>
                {t(
                    "Enter your new email address. After your changes have been saved, you will need to confirm your email.",
                )}
            </Paragraph>
            <InputBlock label={t("Current Email")}>
                <span>{initialEmail}</span>
            </InputBlock>
            <InputTextBlock
                label={<span>{t("New Email")}</span>}
                errors={patchErrors?.errors?.email}
                noteAfterInput={t("Enter your new email address")}
                inputProps={{
                    required: true,
                    onChange: (event) => {
                        setEmail(event.target.value);
                    },
                    value: email,
                }}
                extendErrorMessage
            />
            {canEditUsers && (
                <span style={{ marginBottom: 8, display: "flex" }}>
                    <CheckBox
                        fullWidth
                        hugLeft
                        id="email-verified-status"
                        label={t("Verified. Bypasses spam and pre-moderation filters.")}
                        labelBold={false}
                        checked={verifiedStatus}
                        onChange={(event) => setVerifiedStatus(event.target.checked)}
                    />
                </span>
            )}
            {!skipPasswordConfirmation && (
                <InputBlock label={t("Password")} errors={patchErrors?.errors?.password} extendErrorMessage>
                    <PasswordInput
                        id={"password"}
                        onChange={(event) => setPassword(event.target.value)}
                        value={password}
                        aria-label={t("Password")}
                        showUnmask
                        required
                    />
                </InputBlock>
            )}
        </>
    );
});
