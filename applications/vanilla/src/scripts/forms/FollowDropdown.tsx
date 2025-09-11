/**
 * @copyright 2009-2025 Vanilla Forums Inc.
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
import React, { PropsWithChildren, useState } from "react";
import { INotificationPreferences, NotificationType } from "@library/notificationPreferences";
import { followDropdownClasses } from "@vanilla/addon-vanilla/forms/FollowDropdown.classes";
import { RecordID } from "@vanilla/utils";
import { FollowedContentNotificationPreferences } from "@library/followedContent/FollowedContent.types";

export function FollowDropdown<T extends Record<string, NotificationType>>(
    props: PropsWithChildren<{
        notificationTypes: T;
        updatePreferences: (preferences: FollowedContentNotificationPreferences<T>) => Promise<void>;
        unfollowAndResetPreferences: () => Promise<void>;
        isFollowed: boolean;
        name: string;
        recordID: RecordID;
        emailDigestEnabled: boolean;

        className?: string;
        defaultUserPreferences?: INotificationPreferences;
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
        nameAsLabel?: boolean;

        viewRecordUrl?: string;
        viewRecordText?: string;

        recordDetails?: {
            recordKey: string;
            recordUnfollowText: string;
            recordFollowedContentText: string;
        };
    }>,
) {
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
        children,
        isFollowed,
        defaultUserPreferences,
        notificationTypes,
        updatePreferences,
        unfollowAndResetPreferences,
        size = "default",
        nameAsLabel,
        viewRecordUrl,
        viewRecordText,
    } = props;

    type PreferencesType = Parameters<typeof updatePreferences>[0];

    const classes = followDropdownClasses.useAsHook({
        isOpen,
        isFollowed,
        borderRadius,
        buttonColor,
        textColor,
        alignment,
        size,
    });

    const buttonContents = () => {
        const icon = isFollowed ? <Icon size={size} icon="follow-filled" /> : <Icon size={size} icon="follow-empty" />;
        let followedText = isFollowed ? t("Following") : t("Follow");
        if (nameAsLabel) {
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
                        let newPreferences = {};
                        // apply the default user notification preferences
                        Object.entries(notificationTypes).forEach(([_key, type]) => {
                            newPreferences = {
                                ...newPreferences,
                                ...type.getDefaultPreferences(defaultUserPreferences ?? {}),
                            };
                        });

                        await updatePreferences({
                            "preferences.followed": true,
                            ...newPreferences,
                            ...(emailDigestEnabled && { "preferences.email.digest": true }),
                        } as PreferencesType);

                        props.onPreferencesChange?.({
                            preferences: {
                                "preferences.followed": true,
                            },
                            ...(recordDetails && { [recordDetails.recordKey]: recordID }),
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
                                    label={recordDetails?.recordFollowedContentText ?? t("View all followed content")}
                                >
                                    <span>
                                        <LinkAsButton
                                            to="/profile/followed-content"
                                            buttonType={ButtonTypes.ICON}
                                            className={classes.preferencesButton}
                                            ariaLabel={
                                                recordDetails?.recordFollowedContentText ??
                                                t("View all followed content")
                                            }
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
                                {children}
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
                                    {viewRecordText ?? t("View Record")}
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
                                            ...(recordDetails && { [recordDetails.recordKey]: recordID }),
                                        });
                                        setIsOpen(false);
                                    }}
                                >
                                    {recordDetails?.recordUnfollowText ?? t("Unfollow")}
                                </Button>
                            )}
                        </FrameFooter>
                    }
                />
            </DropDown>
        </div>
    );
}
