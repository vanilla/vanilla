/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { LoadStatus } from "@library/@types/api/core";
import { IAccountModalForm, IAccountSettingFormHandle } from "@library/accountSettings/AccountSettingsModal";
import { StatusIndicator } from "@library/accountSettings/StatusIndicator";
import { useUsernameAvailability } from "@library/accountSettings/forms/EditUsername.hooks";
import { useCurrentUser, usePatchUser } from "@library/features/users/userHooks";
import InputTextBlock from "@library/forms/InputTextBlock";
import { ApproveIcon } from "@library/icons/common";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { ToolTip } from "@library/toolTip/ToolTip";
import { t } from "@vanilla/i18n";
import React, {
    forwardRef,
    MutableRefObject,
    ReactNode,
    useEffect,
    useImperativeHandle,
    useMemo,
    useState,
} from "react";
import { editUsernameClasses } from "@library/accountSettings/forms/EditUsername.classes";
import { Icon } from "@vanilla/icons";
import { IError } from "@library/errorPages/CoreErrorMessages";
import { useToast } from "@library/features/toaster/ToastContext";
import Message from "@library/messages/Message";
import { accountSettingsClasses } from "@library/accountSettings/AccountSettings.classes";
import InputBlock from "@library/forms/InputBlock";
import { useAccountSettings } from "@library/accountSettings/AccountSettingsContext";
import PasswordInput from "@library/forms/PasswordInput";

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
    const sessionUser = useCurrentUser();
    const skipPasswordConfirmation = useMemo(() => {
        // If you are editing your own information, you need to provide a confirmation password
        if (isViewingSelf) {
            return false;
        }
        // If you are editing another members information, it depends on your permissions
        return canEditUsernames;
    }, [canEditUsernames, isViewingSelf]);
    const { patchUser, patchErrors, patchStatus } = usePatchUser(viewingUserID);
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

    // Sync save status with parent modal
    useEffect(() => {
        switch (patchStatus) {
            case LoadStatus.LOADING:
                setIsSaving(true);
                break;
            case LoadStatus.SUCCESS:
                setIsSaving(false);
                setIsSuccess(true);
                addToast({
                    autoDismiss: true,
                    body: <>{t("Username changed successfully.")}</>,
                });
                break;

            default:
                setIsSaving(false);
                setIsSuccess(false);
                break;
        }
    }, [patchStatus, patchErrors]);

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
                patchUser({
                    userID: viewingUserID,
                    name: username,
                    ...(password && { passwordConfirmation: password }),
                });
            },
        }),
        [username, password, initialUsername],
    );

    return (
        <>
            {patchErrors && (
                <Message
                    className={accountSettingsClasses().topLevelErrors}
                    type={"error"}
                    stringContents={patchErrors.message}
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
                errors={patchErrors?.errors?.username || usernameErrors}
                inputProps={{
                    required: true,
                    onChange: (event) => {
                        setUsername(event.target.value);
                        setUsernameTouched(true);
                        setIsFormDirty(true);
                    },
                    value: username,
                    valid: false,
                }}
                extendErrorMessage
            />
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
