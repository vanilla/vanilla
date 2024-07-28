/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
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
import { categoryFollowDropDownClasses } from "@vanilla/addon-vanilla/categories/categoryFollowDropDown.styles";
import { t } from "@vanilla/i18n";
import { Icon } from "@vanilla/icons";
import FrameFooter from "@library/layout/frame/FrameFooter";
import { ToolTip } from "@library/toolTip/ToolTip";
import { css, cx } from "@emotion/css";
import { IUser } from "@library/@types/api/users";
import { CategoryPreferencesTable } from "@library/preferencesTable/CategoryPreferencesTable";
import React, { useCallback, useState } from "react";
import { isINotificationPreference } from "@library/notificationPreferences/utils";
import {
    INotificationPreferencesApi,
    NotificationPreferencesContextProvider,
    useNotificationPreferencesContext,
} from "@library/notificationPreferences";
import NotificationPreferencesApi from "@library/notificationPreferences/NotificationPreferences.api";
import debounce from "lodash-es/debounce";
import { useFormik } from "formik";
import {
    useCategoryNotificationPreferencesContext,
    CATEGORY_NOTIFICATION_TYPES,
    getDefaultCategoryNotificationPreferences,
    ICategoryPreferences,
} from "@vanilla/addon-vanilla/categories/CategoryNotificationPreferences.hooks";
import { CategoryNotificationPreferencesContextProvider } from "@vanilla/addon-vanilla/categories/CategoryNotificationPreferences.context";

interface IProps {
    categoryID: number;
    categoryName: string;
    notificationPreferences?: ICategoryPreferences;
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
    isCompact?: boolean;
}

export function CategoryFollowDropDownImpl(props: IProps) {
    const [isOpen, setOpen] = useState<boolean>(!!props.isOpen);

    const { categoryID, emailDigestEnabled, preview, borderRadius, buttonColor, textColor, alignment, isCompact } =
        props;

    const { preferences: categoryNotificationPreferences, setPreferences } =
        useCategoryNotificationPreferencesContext();

    const debouncedSetPreferences = useCallback(
        debounce(setPreferences, 1250, {
            leading: true,
        }),
        [setPreferences],
    );

    const { preferences: globalNotificationPreferences } = useNotificationPreferencesContext();
    const defaultUserPreferences = globalNotificationPreferences?.data ?? undefined;

    const canIncludeInDigest =
        emailDigestEnabled &&
        isINotificationPreference(defaultUserPreferences?.DigestEnabled) &&
        defaultUserPreferences?.DigestEnabled?.email;

    const isFollowed = categoryNotificationPreferences?.["preferences.followed"] ?? false;

    const classes = categoryFollowDropDownClasses({
        isOpen,
        isFollowed,
        borderRadius,
        buttonColor,
        textColor,
        alignment,
    });

    // My special child needs bigger pants
    const widthOverride = css({
        minWidth: 345,
    });

    const { values, setValues, submitForm } = useFormik<ICategoryPreferences>({
        enableReinitialize: true,
        initialValues: categoryNotificationPreferences,
        onSubmit: async (values) => {
            await debouncedSetPreferences(values);
        },
    });

    async function unfollowAndResetPreferences() {
        setValues((values) => ({
            // set everything to false
            ...Object.entries(values).reduce((acc, [key, type]) => {
                acc[key] = false;
                return acc;
            }, {} as ICategoryPreferences),
            ...(props.emailDigestEnabled && { "preferences.email.digest": false }),
        }));
        await submitForm();
    }

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
                onVisibilityChange={async (b) => {
                    if (!preview && !isFollowed && b) {
                        let newPreferences: Partial<ICategoryPreferences> = {};
                        // apply the default user notification preferences
                        Object.entries(CATEGORY_NOTIFICATION_TYPES).forEach(([_key, type]) => {
                            newPreferences = {
                                ...newPreferences,
                                ...type.getDefaultPreferences?.(defaultUserPreferences ?? {}),
                            };
                        });

                        await setPreferences({
                            "preferences.followed": true,
                            ...newPreferences,
                            ...(emailDigestEnabled && { "preferences.email.digest": true }),
                        });

                        props.onPreferencesChange?.({
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
                                <form
                                    role="form"
                                    onSubmit={async (e) => {
                                        e.preventDefault();
                                        await submitForm();
                                    }}
                                >
                                    <CategoryPreferencesTable
                                        canIncludeInDigest={canIncludeInDigest}
                                        preferences={values}
                                        onPreferenceChange={async function (delta) {
                                            setValues((values) => ({ ...values, ...delta }));
                                            await submitForm();
                                        }}
                                        preview={preview}
                                    />
                                </form>
                            </>
                        </FrameBody>
                    }
                    footer={
                        isFollowed && (
                            <FrameFooter forDashboard={true}>
                                <Button
                                    className={classes.fullWidth}
                                    onClick={async () => {
                                        await unfollowAndResetPreferences();
                                        props.onPreferencesChange?.({
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
}

export function CategoryFollowDropDownWithCategoryNotificationsContext(
    props: React.ComponentProps<typeof CategoryFollowDropDownImpl> & {
        userID: IUser["userID"];
    },
) {
    const { userID, ...rest } = props;
    const { preferences } = useNotificationPreferencesContext();

    return (
        <CategoryNotificationPreferencesContextProvider
            userID={props.userID}
            categoryID={props.categoryID}
            initialPreferences={
                props.notificationPreferences ?? getDefaultCategoryNotificationPreferences(preferences?.data)
            }
        >
            <CategoryFollowDropDownImpl {...rest} />
        </CategoryNotificationPreferencesContextProvider>
    );
}

export default function CategoryFollowDropdownWithNotificationPreferencesContext(
    props: React.ComponentProps<typeof CategoryFollowDropDownImpl> & {
        userID: IUser["userID"];
        api?: INotificationPreferencesApi;
    },
) {
    const { api = NotificationPreferencesApi, ...rest } = props;
    return (
        <NotificationPreferencesContextProvider userID={rest.userID} api={NotificationPreferencesApi}>
            <CategoryFollowDropDownWithCategoryNotificationsContext {...rest} />
        </NotificationPreferencesContextProvider>
    );
}
