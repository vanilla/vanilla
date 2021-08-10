/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import DropDown, { FlyoutType } from "@library/flyouts/DropDown";
import { ButtonTypes } from "@library/forms/buttonTypes";
import RadioButton from "@library/forms/RadioButton";
import RadioButtonGroup from "@library/forms/RadioButtonGroup";
import Heading from "@library/layout/Heading";
import {
    CategoryFollowDropDownClasses,
    RadioLabelClasses,
} from "@vanilla/addon-vanilla/categories/categoryFollowDropDown.styles";
import { CategoryPreference, useCategoryNotifications } from "@vanilla/addon-vanilla/categories/categoryFollowHooks";
import { t } from "@vanilla/i18n";
import { Icon } from "@vanilla/icons";
import React, { useEffect, useState } from "react";

const RadioLabel = ({ title, description }: { title: string; description: string }) => {
    const classes = RadioLabelClasses();
    return (
        <span className={classes.layout}>
            <span className={classes.title}>{title}</span>
            <span className={classes.description}>{description}</span>
        </span>
    );
};

export interface ICategoryFollowFlyoutProps {
    userID: number;
    categoryID: number;
    isFollowed: boolean;
    notificationPreference: CategoryPreference;
}

const radioOptions = [
    {
        value: "follow",
        title: "Follow",
        description: "Follow on my homepage.",
    },
    {
        value: "discussions",
        title: "Discussions",
        description: "Notify of all new discussions.",
    },
    {
        value: "all",
        title: "Discussions and Comments",
        description: "Notify of all new posts.",
    },
    {
        value: "null",
        title: "Unfollow",
        description: "Only receive default notifications.",
    },
];

export const CategoryFollowDropDown = (props: ICategoryFollowFlyoutProps) => {
    const [isOpen, setOpen] = useState<boolean>(false);
    /**
     * We need to maintain this state because the props are fed in
     * through the initial render and will be updated via an API
     */
    const [isFollowed, setFollowed] = useState<boolean>(props.isFollowed);
    const [stringifiedNotificationPreference, setStringifiedValue] = useState<CategoryPreference | "null">(
        props.notificationPreference,
    );

    const { notificationPreference, getNotificationPreference, setNotificationPreference } = useCategoryNotifications(
        props.userID,
        props.categoryID,
    );
    const classes = CategoryFollowDropDownClasses({ isOpen, isFollowed });

    useEffect(() => {
        // notificationPreference could be null but radio buttons do not accept null as a value
        setStringifiedValue(() => {
            return notificationPreference === null ? "null" : notificationPreference;
        });
        setFollowed(!!notificationPreference);
    }, [notificationPreference]);

    // Props are the source of truth only when the component is loaded
    useEffect(() => {
        setFollowed(props.isFollowed);
        setStringifiedValue(() => {
            return notificationPreference === null ? "null" : notificationPreference;
        });
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const getNotificationInfo = (visibility: boolean) => {
        setOpen(visibility);
        visibility && getNotificationPreference();
    };

    const handlePreferenceSelection = (selection: CategoryPreference | "null") => {
        // Selection could be the string "null" because radio buttons do not accept null as a value
        const requestBody = selection === "null" ? null : selection;
        setNotificationPreference(requestBody);
    };

    return (
        <div className={classes.layout}>
            <DropDown
                name={isFollowed ? t("Unfollow") : t("Follow")}
                buttonType={ButtonTypes.TEXT}
                toggleButtonClassName={classes.followButton}
                buttonContents={isFollowed ? <Icon icon="me-notifications-solid" /> : <Icon icon="me-notifications" />}
                flyoutType={FlyoutType.FRAME}
                onVisibilityChange={(b) => getNotificationInfo(b)}
            >
                <>
                    <Heading className={classes.heading} renderAsDepth={3} title={t("Notification Preferences")}>
                        {t("Notification Preferences")}
                    </Heading>
                    <RadioButtonGroup wrapClassName={classes.groupLayout}>
                        {radioOptions.map((option) => {
                            return (
                                <RadioButton
                                    key={option.title}
                                    className={classes.radioItem}
                                    onChange={(e) => handlePreferenceSelection(e.target.value)}
                                    checked={option.value === stringifiedNotificationPreference}
                                    name={`category-${props.categoryID}-notifications`}
                                    value={option.value}
                                    label={<RadioLabel title={t(option.title)} description={t(option.description)} />}
                                />
                            );
                        })}
                    </RadioButtonGroup>
                </>
            </DropDown>
        </div>
    );
};
