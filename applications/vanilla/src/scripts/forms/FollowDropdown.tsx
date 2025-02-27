/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import DropDown, { FlyoutType } from "@library/flyouts/DropDown";
import { ButtonTypes } from "@library/forms/buttonTypes";
import Button from "@library/forms/Button";
import { SettingsIcon } from "@library/icons/titleBar";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameHeaderWithAction from "@library/layout/frame/FrameHeaderWithAction";
import LinkAsButton from "@library/routing/LinkAsButton";
import { t } from "@vanilla/i18n";
import { Icon } from "@vanilla/icons";
import FrameFooter from "@library/layout/frame/FrameFooter";
import { ToolTip } from "@library/toolTip/ToolTip";
import { css, cx } from "@emotion/css";
import React, { useState } from "react";
import { INotificationPreferences, NotificationType } from "@library/notificationPreferences";
import { ICategoryPreferences } from "@vanilla/addon-vanilla/categories/CategoryNotificationPreferences.hooks";
import { followDropdownClasses } from "@vanilla/addon-vanilla/forms/FollowDropdown.classes";
import { RecordID } from "@vanilla/utils";

export interface IFollowDropdownProps {
    emailDigestEnabled: boolean;
    className?: string;
    onPreferencesChange?: (categoryWithNewPreferences) => void;
    /** Used for testing to override open state */
    isOpen?: boolean;
    /** Disable network calls for widget preview */
    preview?: boolean;
    /** Widget style overrides */
    borderRadius?: number;
    buttonColor?: string;
    textColor?: string;
    alignment?: "start" | "center" | "end";
    /** Only the bell icon instead of the button with text */
    iconOnly?: boolean;
    size?: "compact" | "default";
    categoryAsLabel?: boolean;
}
interface IProps extends IFollowDropdownProps {
    recordID: RecordID;
    name: string;
    preferencesTable: React.ReactNode;
    updatePreferences: (preferences: ICategoryPreferences) => Promise<ICategoryPreferences>;
    submitForm: () => Promise<any>;
    unfollowAndResetPreferences: () => Promise<void>;
    isFollowed: boolean;
    defaultUserPreferences?: INotificationPreferences;
    notificationTypes: Record<string, NotificationType>;
    recordDetails?: {
        recordKey: string;
        recordUnfollowText: string;
        recordFollowedContentText: string;
    };
    viewRecordUrl?: string;
    viewRecordText?: string;
}

export function FollowDropdown(props: IProps) {
    const [isOpen, setIsOpen] = useState<boolean>(!!props.isOpen);

    const {
        recordDetails,
        recordID,
        emailDigestEnabled,
        preview,
        borderRadius,
        buttonColor,
        textColor,
        alignment,
        iconOnly,
        preferencesTable,
        isFollowed,
        defaultUserPreferences,
        notificationTypes,
        updatePreferences,
        submitForm,
        unfollowAndResetPreferences,
        size = "default",
        categoryAsLabel,
        viewRecordUrl,
        viewRecordText,
    } = props;

    const classes = followDropdownClasses({
        isOpen,
        isFollowed,
        borderRadius,
        buttonColor,
        textColor,
        alignment,
        size,
    });

    const buttonContents = () => {
        const icon = isFollowed ? (
            <Icon size={size} icon="me-notifications-solid" />
        ) : (
            <Icon size={size} icon="me-notifications" />
        );
        let followedText = isFollowed ? t("Following") : t("Follow");
        if (categoryAsLabel) {
            followedText = props.name;
        }

        return (
            <>
                {icon} {!iconOnly && followedText}
            </>
        );
    };

    return (
        <div className={cx(classes.layout, props.className)}>
            <DropDown
                name={isFollowed ? t("Following") : t("Follow")}
                buttonType={iconOnly ? ButtonTypes.ICON : ButtonTypes.OUTLINE}
                buttonClassName={cx(classes.followButton, {
                    [classes.unClickable]: preview,
                })}
                buttonContents={buttonContents()}
                flyoutType={FlyoutType.FRAME}
                contentsClassName={css({
                    minWidth: 345,
                })}
                onVisibilityChange={async (b) => {
                    if (!preview && !isFollowed && b) {
                        let newPreferences: Partial<ICategoryPreferences> = {};
                        // apply the default user notification preferences
                        Object.entries(notificationTypes).forEach(([_key, type]) => {
                            newPreferences = {
                                ...newPreferences,
                                ...type.getDefaultPreferences?.(defaultUserPreferences ?? {}),
                            };
                        });

                        await updatePreferences({
                            "preferences.followed": true,
                            ...newPreferences,
                            ...(emailDigestEnabled && { "preferences.email.digest": true }),
                        });

                        props.onPreferencesChange?.({
                            preferences: {
                                "preferences.followed": true,
                            },
                            ...(recordDetails ? { [recordDetails.recordKey]: recordID } : { categoryID: recordID }),
                        });
                    }
                    setIsOpen(b);
                }}
                isVisible={isOpen}
                asReachPopover
            >
                <Frame
                    header={
                        <FrameHeaderWithAction title={t("Notification Preferences")}>
                            {/* If we're on the followed content preference page, this button should be hidden */}
                            {!window.location.pathname.includes("/followed-content") && (
                                <ToolTip
                                    label={t(
                                        recordDetails?.recordFollowedContentText ?? "View all followed categories",
                                    )}
                                >
                                    <span>
                                        <LinkAsButton
                                            to="/profile/followed-content"
                                            buttonType={ButtonTypes.ICON}
                                            className={classes.preferencesButton}
                                            ariaLabel={t(
                                                recordDetails?.recordFollowedContentText ??
                                                    "View all followed categories",
                                            )}
                                        >
                                            <SettingsIcon />
                                        </LinkAsButton>
                                    </span>
                                </ToolTip>
                            )}
                        </FrameHeaderWithAction>
                    }
                    body={
                        <FrameBody hasVerticalPadding={true}>
                            <>
                                <p className={classes.heading}>{props.name}</p>
                                <form
                                    role="form"
                                    onSubmit={async (e) => {
                                        e.preventDefault();
                                        await submitForm();
                                    }}
                                >
                                    {preferencesTable}
                                </form>
                            </>
                        </FrameBody>
                    }
                    footer={
                        <FrameFooter forDashboard={true}>
                            {viewRecordUrl && (
                                <LinkAsButton
                                    to={viewRecordUrl}
                                    target="_blank"
                                    className={cx(classes.fullWidth, classes.viewRecordButton)}
                                >
                                    {t(viewRecordText ?? "View Category")}
                                </LinkAsButton>
                            )}

                            {isFollowed && (
                                <Button
                                    className={cx(classes.fullWidth, {
                                        [classes.extraButtonMargin]: !!viewRecordUrl,
                                    })}
                                    onClick={async () => {
                                        await unfollowAndResetPreferences();
                                        props.onPreferencesChange?.({
                                            preferences: {},
                                            ...(recordDetails
                                                ? { [recordDetails.recordKey]: recordID }
                                                : { categoryID: recordID }),
                                        });
                                        setIsOpen(false);
                                    }}
                                >
                                    {t(recordDetails?.recordUnfollowText ?? "Unfollow Category")}
                                </Button>
                            )}
                        </FrameFooter>
                    }
                />
            </DropDown>
        </div>
    );
}
