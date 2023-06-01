/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useCurrentUser } from "@library/features/users/userHooks";
import DropDown, { FlyoutType } from "@library/flyouts/DropDown";
import { ButtonTypes } from "@library/forms/buttonTypes";
import Button from "@library/forms/Button";
import CheckboxGroup from "@library/forms/CheckboxGroup";
import Checkbox from "@library/forms/Checkbox";
import { SettingsIcon } from "@library/icons/titleBar";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameHeaderWithAction from "@library/layout/frame/FrameHeaderWithAction";
import LinkAsButton from "@library/routing/LinkAsButton";
import {
    DEFAULT_NOTIFICATION_PREFERENCES,
    ICategoryPreferences,
    CategoryPostNotificationType,
} from "@vanilla/addon-vanilla/categories/categoriesTypes";
import { categoryFollowDropDownClasses } from "@vanilla/addon-vanilla/categories/categoryFollowDropDown.styles";
import { useCategoryNotifications } from "@vanilla/addon-vanilla/categories/categoryFollowHooks";
import { t } from "@vanilla/i18n";
import Translate from "@library/content/Translate";
import { Icon } from "@vanilla/icons";
import React, { useEffect, useState } from "react";
import FrameFooter from "@library/layout/frame/FrameFooter";

enum EmailMode {
    DEFAULT_ON = "defaultOn",
    DEFAULT_OFF = "defaultOff",
    DISABLED = "disabled",
}

interface IProps {
    userID: number;
    categoryID: number;
    categoryName: string;
    notificationPreferences?: ICategoryPreferences | null;
    emailNotificationsMode?: EmailMode;
}

export const CategoryFollowDropDown = (props: IProps) => {
    const [isOpen, setOpen] = useState<boolean>(false);
    const currentUser = useCurrentUser();

    /**
     * We need to maintain this state because the props are fed in
     * through the initial render and will be updated via an API
     */
    const { setNotificationPreferences, notificationPreferences } = useCategoryNotifications(
        props.userID,
        props.categoryID,
        props.notificationPreferences ?? DEFAULT_NOTIFICATION_PREFERENCES,
    );

    useEffect(() => {
        if (
            props.notificationPreferences?.useEmailNotifications &&
            props.emailNotificationsMode === EmailMode.DISABLED
        ) {
            setNotificationPreferences({
                useEmailNotifications: false,
            });
        }
    }, [props.emailNotificationsMode, props.notificationPreferences, setNotificationPreferences]);

    const currentPreferences = notificationPreferences;
    const isFollowed = currentPreferences.postNotifications !== null;
    const classes = categoryFollowDropDownClasses({ isOpen, isFollowed });

    return (
        <div className={classes.layout}>
            <DropDown
                name={isFollowed ? t("Unfollow") : t("Follow")}
                buttonType={ButtonTypes.TEXT}
                buttonClassName={classes.followButton}
                buttonContents={isFollowed ? <Icon icon="me-notifications-solid" /> : <Icon icon="me-notifications" />}
                flyoutType={FlyoutType.FRAME}
                onVisibilityChange={(b) => {
                    setOpen(b);
                }}
            >
                <Frame
                    header={
                        <FrameHeaderWithAction title={t("Notification Preferences")}>
                            <LinkAsButton
                                to={`/profile/preferences/${encodeURIComponent(
                                    currentUser?.name ?? "",
                                )}#followed-categories`}
                                buttonType={ButtonTypes.ICON}
                                className={classes.preferencesButton}
                            >
                                <SettingsIcon />
                            </LinkAsButton>
                        </FrameHeaderWithAction>
                    }
                    body={
                        !isFollowed ? (
                            <FrameBody hasVerticalPadding={true}>
                                <Button
                                    buttonType={ButtonTypes.PRIMARY}
                                    className={classes.fullWidth}
                                    onClick={() => {
                                        setNotificationPreferences({
                                            postNotifications: CategoryPostNotificationType.FOLLOW,
                                        });
                                    }}
                                >
                                    {t("Follow Category")}
                                </Button>
                            </FrameBody>
                        ) : (
                            <>
                                <FrameBody hasVerticalPadding={true}>
                                    <p className={classes.heading}>
                                        <Translate source="Preferences for <0/>" c0={props.categoryName} />
                                    </p>
                                    <CheckboxGroup>
                                        <Checkbox
                                            label={t("Notify of new posts")}
                                            labelBold={false}
                                            onChange={(event: React.ChangeEvent<HTMLInputElement>) => {
                                                event.target.checked
                                                    ? setNotificationPreferences({
                                                          postNotifications: CategoryPostNotificationType.DISCUSSIONS,
                                                      })
                                                    : setNotificationPreferences({
                                                          postNotifications: CategoryPostNotificationType.FOLLOW,
                                                          useEmailNotifications: false,
                                                      });
                                            }}
                                            checked={
                                                currentPreferences.postNotifications ===
                                                    CategoryPostNotificationType.DISCUSSIONS ||
                                                currentPreferences.postNotifications ===
                                                    CategoryPostNotificationType.ALL
                                            }
                                            hugLeft={true}
                                            className={classes.checkBox}
                                        />
                                        <CheckboxGroup noMargin={true} className={classes.marginLeft}>
                                            <Checkbox
                                                label={t("Notify of new comments")}
                                                labelBold={false}
                                                onChange={(event: React.ChangeEvent<HTMLInputElement>) => {
                                                    setNotificationPreferences({
                                                        postNotifications: event.target.checked
                                                            ? CategoryPostNotificationType.ALL
                                                            : CategoryPostNotificationType.DISCUSSIONS,
                                                    });
                                                }}
                                                checked={
                                                    currentPreferences.postNotifications ===
                                                    CategoryPostNotificationType.ALL
                                                }
                                                disabled={
                                                    currentPreferences.postNotifications ===
                                                    CategoryPostNotificationType.FOLLOW
                                                }
                                                className={classes.checkBox}
                                            />
                                            <Checkbox
                                                label={t("Email notifications")}
                                                labelBold={false}
                                                onChange={(event: React.ChangeEvent<HTMLInputElement>) => {
                                                    setNotificationPreferences({
                                                        useEmailNotifications: event.target.checked ? true : false,
                                                    });
                                                }}
                                                checked={currentPreferences.useEmailNotifications}
                                                disabled={
                                                    currentPreferences.postNotifications ===
                                                    CategoryPostNotificationType.FOLLOW
                                                }
                                                className={classes.checkBox}
                                            />
                                        </CheckboxGroup>
                                    </CheckboxGroup>
                                </FrameBody>
                                <FrameFooter forDashboard={true}>
                                    <Button
                                        className={classes.fullWidth}
                                        onClick={() => {
                                            setNotificationPreferences({
                                                postNotifications: null,
                                                useEmailNotifications: false,
                                            });
                                        }}
                                    >
                                        {t("Unfollow Category")}
                                    </Button>
                                </FrameFooter>
                            </>
                        )
                    }
                />
            </DropDown>
        </div>
    );
};
