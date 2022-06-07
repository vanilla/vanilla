/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useCurrentUser } from "@library/features/users/userHooks";
import DropDown, { FlyoutType } from "@library/flyouts/DropDown";
import { ButtonTypes } from "@library/forms/buttonTypes";
import RadioButton from "@library/forms/RadioButton";
import RadioButtonGroup from "@library/forms/RadioButtonGroup";
import { SettingsIcon } from "@library/icons/titleBar";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameHeaderWithAction from "@library/layout/frame/FrameHeaderWithAction";
import LinkAsButton from "@library/routing/LinkAsButton";
import {
    DEFAULT_NOTIFICATION_PREFERENCES,
    ICategoryPreferences,
} from "@vanilla/addon-vanilla/categories/categoriesTypes";
import {
    categoryFollowDropDownClasses,
    radioLabelClasses,
} from "@vanilla/addon-vanilla/categories/categoryFollowDropDown.styles";
import { useCategoryNotifications } from "@vanilla/addon-vanilla/categories/categoryFollowHooks";
import { t } from "@vanilla/i18n";
import { Icon } from "@vanilla/icons";
import React, { useEffect, useMemo, useState } from "react";

enum EmailMode {
    DEFAULT_ON = "defaultOn",
    DEFAULT_OFF = "defaultOff",
    DISABLED = "disabled",
}

interface IProps {
    userID: number;
    categoryID: number;
    notificationPreferences?: ICategoryPreferences | null;
    emailNotificationsMode?: EmailMode;
}

export const CategoryFollowDropDown = (props: IProps) => {
    const [isOpen, setOpen] = useState<boolean>(false);
    /**
     * We need to maintain this state because the props are fed in
     * through the initial render and will be updated via an API
     */
    const { setNotificationPreferences, setNotificationPreferencesState, notificationPreferences } =
        useCategoryNotifications(
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
    }, []);

    const currentPreferences = notificationPreferences;

    const originalEmailPref = useMemo(() => {
        switch (props.emailNotificationsMode) {
            case EmailMode.DISABLED:
                return false;
            case EmailMode.DEFAULT_ON:
                return !props.notificationPreferences ||
                    props.notificationPreferences?.useEmailNotifications ||
                    props.notificationPreferences?.postNotifications === "follow"
                    ? true
                    : false;
            case EmailMode.DEFAULT_OFF:
            default:
                return props.notificationPreferences?.useEmailNotifications;
        }
    }, []);
    const isFollowed = currentPreferences.postNotifications !== null;
    const classes = categoryFollowDropDownClasses({ isOpen, isFollowed });

    const currentUser = useCurrentUser();

    return (
        <div className={classes.layout}>
            <DropDown
                name={isFollowed ? t("Unfollow") : t("Follow")}
                buttonType={ButtonTypes.TEXT}
                buttonClassName={classes.followButton}
                buttonContents={isFollowed ? <Icon icon="me-notifications-solid" /> : <Icon icon="me-notifications" />}
                flyoutType={FlyoutType.FRAME}
                onVisibilityChange={(b) => setOpen(b)}
            >
                <Frame
                    header={
                        <FrameHeaderWithAction title={t("Notification Preferences")}>
                            <LinkAsButton
                                to={`/profile/preferences/${encodeURIComponent(
                                    currentUser?.name ?? "",
                                )}#followed-categories`}
                                buttonType={ButtonTypes.ICON}
                            >
                                <SettingsIcon />
                            </LinkAsButton>
                        </FrameHeaderWithAction>
                    }
                    body={
                        <FrameBody selfPadded>
                            <RadioButtonGroup wrapClassName={classes.groupLayout}>
                                <RadioButton
                                    className={classes.radioItem}
                                    onChecked={() => {
                                        setNotificationPreferences({
                                            useEmailNotifications: false,
                                            postNotifications: "follow",
                                        });
                                    }}
                                    checked={currentPreferences.postNotifications === "follow"}
                                    value={"follow"}
                                    label={<RadioLabel title={t("Follow")} description={t("Follow on my homepage.")} />}
                                />
                                <RadioButton
                                    className={classes.radioItem}
                                    onChecked={() => {
                                        setNotificationPreferences({
                                            postNotifications: "discussions",
                                            useEmailNotifications: originalEmailPref,
                                        });
                                    }}
                                    checked={currentPreferences.postNotifications === "discussions"}
                                    value={"discussions"}
                                    label={
                                        <RadioLabel
                                            title={t("Discussions")}
                                            description={
                                                originalEmailPref
                                                    ? t("Notify of all new discussions by email.")
                                                    : t("Notify of all new discussions.")
                                            }
                                        />
                                    }
                                />
                                <RadioButton
                                    className={classes.radioItem}
                                    onChecked={() => {
                                        setNotificationPreferences({
                                            postNotifications: "all",
                                            useEmailNotifications: originalEmailPref,
                                        });
                                    }}
                                    checked={currentPreferences.postNotifications === "all"}
                                    value={"all"}
                                    label={
                                        <RadioLabel
                                            title={t("Discussions and Comments")}
                                            description={
                                                originalEmailPref
                                                    ? t("Notify of all new posts by email.")
                                                    : t("Notify of all new posts.")
                                            }
                                        />
                                    }
                                />
                                <RadioButton
                                    className={classes.radioItem}
                                    onChecked={() => {
                                        setNotificationPreferences({
                                            useEmailNotifications: false,
                                            postNotifications: null,
                                        });
                                    }}
                                    checked={currentPreferences.postNotifications === null}
                                    value={"null"}
                                    label={
                                        <RadioLabel
                                            title={t("Unfollow")}
                                            description={t("Only receive default notifications.")}
                                        />
                                    }
                                />
                            </RadioButtonGroup>
                        </FrameBody>
                    }
                />
            </DropDown>
        </div>
    );
};

interface ILabelProps {
    title: string;
    description: string;
}

function RadioLabel(props: ILabelProps) {
    const { title, description } = props;
    const classes = radioLabelClasses();
    return (
        <span className={classes.layout}>
            <span className={classes.title}>{title}</span>
            <span className={classes.description}>{description}</span>
        </span>
    );
}
