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
import { css } from "@emotion/css";
import { IUser } from "@library/@types/api/users";
import { CategoryPreferencesTable } from "@library/preferencesTable/CategoryPreferencesTable";
import React, { useState } from "react";

interface IProps {
    userID: IUser["userID"];
    categoryID: number;
    categoryName: string;
    notificationPreferences?: ICategoryPreferences | null;
    emailDigestEnabled: boolean;
    emailEnabled: boolean;
    /** Used for testing to override open state */
    isOpen?: boolean;
}

export const CategoryFollowDropDown = (props: IProps) => {
    const [isOpen, setOpen] = useState<boolean>(!!props.isOpen);

    const { userID, categoryID, emailDigestEnabled } = props;

    /**
     * We need to maintain this state because the props are fed in
     * through the initial render and will be updated via an API
     */
    const { setNotificationPreferences, notificationPreferences } = useCategoryNotifications({
        userID,
        categoryID,
        initialPreferences: props.notificationPreferences ?? DEFAULT_NOTIFICATION_PREFERENCES,
        emailDigestEnabled,
    });

    const isFollowed = notificationPreferences["preferences.followed"];

    const classes = categoryFollowDropDownClasses({ isOpen, isFollowed });

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
        <div className={classes.layout}>
            <DropDown
                name={isFollowed ? t("Unfollow") : t("Follow")}
                buttonType={ButtonTypes.TEXT}
                buttonClassName={classes.followButton}
                buttonContents={isFollowed ? <Icon icon="me-notifications-solid" /> : <Icon icon="me-notifications" />}
                flyoutType={FlyoutType.FRAME}
                contentsClassName={widthOverride}
                onVisibilityChange={(b) => {
                    setOpen(b);
                }}
                isVisible={isOpen}
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
                            {!isFollowed ? (
                                <Button
                                    buttonType={ButtonTypes.PRIMARY}
                                    className={classes.fullWidth}
                                    onClick={() => {
                                        setNotificationPreferences({
                                            "preferences.followed": true,
                                        });
                                    }}
                                >
                                    {t("Follow Category")}
                                </Button>
                            ) : (
                                <>
                                    <p className={classes.heading}>{props.categoryName}</p>
                                    <CategoryPreferencesTable
                                        preferences={notificationPreferences}
                                        onPreferenceChange={setNotificationPreferences}
                                    />
                                </>
                            )}
                        </FrameBody>
                    }
                    footer={
                        isFollowed && (
                            <FrameFooter forDashboard={true}>
                                <Button
                                    className={classes.fullWidth}
                                    onClick={() => {
                                        unfollowAndResetPreferences();
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
