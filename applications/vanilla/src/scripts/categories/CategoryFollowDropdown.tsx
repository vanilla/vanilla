/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
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
import {
    DEFAULT_NOTIFICATION_PREFERENCES,
    ICategoryPreferences,
} from "@vanilla/addon-vanilla/categories/categoriesTypes";
import { categoryFollowDropDownClasses } from "@vanilla/addon-vanilla/categories/categoryFollowDropDown.styles";
import { useCategoryNotifications } from "@vanilla/addon-vanilla/categories/categoryFollowHooks";
import { t } from "@vanilla/i18n";
import { Icon } from "@vanilla/icons";
import FrameFooter from "@library/layout/frame/FrameFooter";
import { ToolTip } from "@library/toolTip/ToolTip";
import { css, cx } from "@emotion/css";
import { IUser } from "@library/@types/api/users";
import { CategoryPreferencesTable } from "@library/preferencesTable/CategoryPreferencesTable";
import React, { useState } from "react";
import { isINotificationPreference } from "@library/notificationPreferences/utils";

interface IProps {
    userID: IUser["userID"];
    categoryID: number;
    categoryName: string;
    notificationPreferences?: ICategoryPreferences | null;
    emailDigestEnabled: boolean;
    emailEnabled: boolean;
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
    isCompact?: boolean;
}

export const CategoryFollowDropDown = (props: IProps) => {
    const [isOpen, setOpen] = useState<boolean>(!!props.isOpen);

    const {
        userID,
        categoryID,
        emailDigestEnabled,
        preview,
        borderRadius,
        buttonColor,
        textColor,
        alignment,
        isCompact,
    } = props;

    /**
     * We need to maintain this state because the props are fed in
     * through the initial render and will be updated via an API
     */
    const { defaultUserPreferences, setNotificationPreferences, notificationPreferences } = useCategoryNotifications({
        userID,
        categoryID,
        initialPreferences: props.notificationPreferences ?? DEFAULT_NOTIFICATION_PREFERENCES,
        emailDigestEnabled,
    });

    const canIncludeInDigest =
        emailDigestEnabled &&
        isINotificationPreference(defaultUserPreferences?.DigestEnabled) &&
        defaultUserPreferences?.DigestEnabled?.email;

    const isFollowed = notificationPreferences["preferences.followed"];

    const classes = categoryFollowDropDownClasses({
        isOpen,
        isFollowed,
        borderRadius,
        buttonColor,
        textColor,
        alignment,
    });

    const unfollowAndResetPreferences = () => {
        setNotificationPreferences({
            "preferences.followed": false,
            "preferences.email.comments": false,
            "preferences.email.posts": false,
            "preferences.popup.comments": false,
            "preferences.popup.posts": false,
            ...(props.emailDigestEnabled && { "preferences.email.digest": false }),
        });
    };

    // My special child needs bigger pants
    const widthOverride = css({
        minWidth: 345,
    });

    return (
        <div className={cx(classes.layout, props.className)}>
            <DropDown
                name={isFollowed ? t("Following") : t("Follow")}
                buttonType={isCompact ? ButtonTypes.ICON : ButtonTypes.OUTLINE}
                buttonClassName={cx(classes.followButton, {
                    [classes.unClickable]: preview,
                })}
                buttonContents={
                    isFollowed ? (
                        <>
                            <Icon icon="me-notifications-solid" /> {!isCompact && t("Following")}
                        </>
                    ) : (
                        <>
                            <Icon icon="me-notifications" /> {!isCompact && t("Follow")}
                        </>
                    )
                }
                flyoutType={FlyoutType.FRAME}
                contentsClassName={widthOverride}
                onVisibilityChange={(b) => {
                    if (!preview && !isFollowed && b) {
                        setNotificationPreferences({
                            "preferences.followed": true,
                        });
                        props.onPreferencesChange &&
                            props.onPreferencesChange({
                                categoryID: categoryID,
                                preferences: {
                                    "preferences.followed": true,
                                },
                            });
                    }
                    setOpen(b);
                }}
                isVisible={isOpen}
                asReachPopover
            >
                <Frame
                    header={
                        <FrameHeaderWithAction title={t("Notification Preferences")}>
                            {/* If we're on the followed content preference page, this button should be hidden */}
                            {!window.location.pathname.includes("/followed-content") && (
                                <ToolTip label={t("View all followed categories")}>
                                    <span>
                                        <LinkAsButton
                                            to="/profile/followed-content"
                                            buttonType={ButtonTypes.ICON}
                                            className={classes.preferencesButton}
                                            ariaLabel={t("View all followed categories")}
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
                                <p className={classes.heading}>{props.categoryName}</p>
                                <CategoryPreferencesTable
                                    canIncludeInDigest={canIncludeInDigest}
                                    preferences={notificationPreferences}
                                    onPreferenceChange={setNotificationPreferences}
                                    preview={preview}
                                />
                            </>
                        </FrameBody>
                    }
                    footer={
                        isFollowed && (
                            <FrameFooter forDashboard={true}>
                                <Button
                                    className={classes.fullWidth}
                                    onClick={() => {
                                        unfollowAndResetPreferences();
                                        props.onPreferencesChange &&
                                            props.onPreferencesChange({
                                                categoryID: categoryID,
                                                preferences: {},
                                            });
                                    }}
                                >
                                    {t("Unfollow Category")}
                                </Button>
                            </FrameFooter>
                        )
                    }
                />
            </DropDown>
        </div>
    );
};

export default CategoryFollowDropDown;
