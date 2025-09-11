/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IAccountModalForm, IAccountSettingFormHandle } from "@library/accountSettings/AccountSettingsModal";
import { MutableRefObject, ReactNode, forwardRef, useEffect, useImperativeHandle, useMemo, useState } from "react";

import { ApproveIcon } from "@library/icons/common";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { IError } from "@library/errorPages/CoreErrorMessages";
import { Icon } from "@vanilla/icons";
import InputBlock from "@library/forms/InputBlock";
import InputTextBlock from "@library/forms/InputTextBlock";
import Message from "@library/messages/Message";
import PasswordInput from "@library/forms/PasswordInput";
import { StatusIndicator } from "@library/accountSettings/StatusIndicator";
import { ToolTip } from "@library/toolTip/ToolTip";
import { accountSettingsClasses } from "@library/accountSettings/AccountSettings.classes";
import { editUsernameClasses } from "@library/accountSettings/forms/EditUsername.classes";
import { t } from "@vanilla/i18n";
import { useAccountSettings } from "@library/accountSettings/AccountSettingsContext";
import { useToast } from "@library/features/toaster/ToastContext";
import { useUserMutation } from "@library/features/users/userHooks";
import { useUsernameAvailability } from "@library/accountSettings/forms/EditUsername.hooks";

interface IProps extends IAccountModalForm {}

/**
 * The edit user name form used on the accounts and privacy page
 */
export const EditUsername = forwardRef(function UsernameEditImpl(
    props: IProps,
    ref: MutableRefObject<IAccountSettingFormHandle>,
) {
    const classes = editUsernameClasses();
    const { setIsSaving, setIsFormDirty, setIsSuccess } = props;
    const { viewingUserID, isViewingSelf, canEditUsernames, viewingUser } = useAccountSettings();

    // Fetch some data
    const skipPasswordConfirmation = useMemo(() => {
        const hasPassword = viewingUser?.hashMethod !== "Random" && viewingUser?.hashMethod !== "Reset";
        // If you are editing your own information, you need to provide a confirmation password if we have one
        if (isViewingSelf) {
            return !hasPassword;
        }
        // If you are editing another members information, it depends on your permissions
        return canEditUsernames;
    }, [canEditUsernames, isViewingSelf]);
    const userMutation = useUserMutation();
    const { addToast } = useToast();

    // Cache the initial username
    const initialUsername = useMemo(() => {
        if (viewingUser) {
            return viewingUser.name;
        }
        return "";
    }, [viewingUser]);

    const [username, setUsername] = useState<string>(initialUsername);
    const isAvailable = useUsernameAvailability(username);
    const [isUsernameTouched, setUsernameTouched] = useState<boolean>(false);
    const [password, setPassword] = useState<string>("");

    // Set the forms values when editing user resolves
    useEffect(() => {
        if (initialUsername?.length > 0) {
            setUsername(initialUsername);
        }
    }, [initialUsername]);

    useEffect(() => {
        if (userMutation.isSuccess) {
            setIsSaving(false);
            setIsSuccess(true);
            addToast({
                autoDismiss: true,
                body: <>{t("Username changed successfully.")}</>,
            });
        }
        if (userMutation.isError) {
            setIsSaving(false);
            setIsSuccess(false);
        }
        if (userMutation.isLoading) {
            setIsSaving(true);
        }
    }, [userMutation.isSuccess, userMutation.isError, userMutation.isLoading]);

    // Determine if the user has touched the username form
    const isFieldDirty = useMemo(() => {
        return isUsernameTouched && username.length > 0 && username !== initialUsername;
    }, [initialUsername, isUsernameTouched, username]);

    // Get an icon and tooltip pair to indicate availability
    const availabilityIcon = useMemo(() => {
        // Returns an icon wrapped in a tooltip
        const wrappedIcon = (tooltip: ReactNode, icon: ReactNode) => {
            return (
                <span className={classes.statusLayout}>
                    <ToolTip label={tooltip}>
                        <span>
                            <StatusIndicator icon={icon} />
                        </span>
                    </ToolTip>
                </span>
            );
        };

        // Check if the username is available after a user makes a change
        if (isFieldDirty) {
            switch (isAvailable) {
                case true: {
                    return wrappedIcon(
                        t("This username is available"),
                        <ApproveIcon className={accountSettingsClasses().verified} />,
                    );
                }
                case false: {
                    return wrappedIcon(
                        t("This username is unavailable"),
                        <Icon icon={"status-warning"} className={accountSettingsClasses().unverified} />,
                    );
                }
                case undefined:
                default: {
                    return wrappedIcon(
                        t("Checking username availability"),
                        <ButtonLoader className={classes.loadingSpinner} />,
                    );
                }
            }
        }
        return null;
    }, [isFieldDirty, isAvailable]);

    // Build an error object that can be passed to the input
    const usernameErrors = useMemo<IError[]>(() => {
        let errors: IError[] = [];
        if (isFieldDirty && isAvailable === false) {
            errors.push({
                message: t("The name you entered is already in use by another member."),
            });
        }
        return errors;
    }, [isFieldDirty, isAvailable]);

    // Handle save
    useImperativeHandle(
        ref,
        () => ({
            onSave: () => {
                userMutation.mutate({
                    userID: viewingUserID,
                    name: username,
                    ...(password && { passwordConfirmation: password }),
                });
            },
        }),
        [username, password, initialUsername],
    );

    const fieldErrors = userMutation.error?.errors;

    return (
        <>
            {userMutation.isError && (
                <Message
                    className={accountSettingsClasses().topLevelErrors}
                    type={"error"}
                    stringContents={userMutation.error.message}
                />
            )}
            <InputBlock label={t("Current Username")}>
                <span>{initialUsername}</span>
            </InputBlock>
            <InputTextBlock
                label={
                    <span className={classes.labelAndStatusLayout}>
                        {t("New Username")}
                        {availabilityIcon}
                    </span>
                }
                noteAfterInput={t("Your new username must be unique")}
                errors={fieldErrors?.name || usernameErrors}
                inputProps={{
                    required: true,
                    onChange: (event) => {
                        setUsername(event.target.value);
                        setUsernameTouched(true);
                        setIsFormDirty(true);
                    },
                    defaultValue: username,
                    valid: false,
                }}
                extendErrorMessage
            />
            {!skipPasswordConfirmation && (
                <InputBlock label={t("Password")} errors={fieldErrors?.currentPassword} extendErrorMessage>
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
