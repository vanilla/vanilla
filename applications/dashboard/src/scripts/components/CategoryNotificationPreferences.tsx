/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IFollowedCategory, useCategoryNotificationPreferences } from "@dashboard/components/CategoryNotificationHooks";
import { categoryNotificationPreferencesClasses } from "@dashboard/components/CategoryNotificationPreferences.styles";
import { cx } from "@emotion/css";
import CheckBox from "@library/forms/Checkbox";
import ErrorMessages from "@library/forms/ErrorMessages";
import { LoadingRectangle } from "@library/loaders/LoadingRectangle";
import SmartLink from "@library/routing/links/SmartLink";
import { ToolTip } from "@library/toolTip/ToolTip";
import { useCategoryNotifications } from "@vanilla/addon-vanilla/categories/categoryFollowHooks";
import { t } from "@vanilla/i18n";
import React, { useEffect } from "react";

interface IProps {
    userID: number;
    isEmailDisabled: boolean;
}

const EMAIL_LABEL_ID = "followed-categories-email-label";
const POPUP_LABEL_ID = "followed-categories-popup-label";

export function CategoryNotificationPreferences(props: IProps) {
    const { isEmailDisabled } = props;

    const classes = categoryNotificationPreferencesClasses();

    return (
        // There is some legacy JS that removes the table if its after a h* element
        // Turn this <section> into a fragment if you are refactoring the page
        <section>
            <h2 id="followed-categories">{t("Followed Categories")}</h2>
            <table className={cx(classes.table)}>
                <thead>
                    <tr className={cx(classes.row)}>
                        <th>{t("Category")}</th>
                        {!isEmailDisabled && (
                            <th id={EMAIL_LABEL_ID} data-type="checkbox">
                                {t("Email")}
                            </th>
                        )}
                        <th id={POPUP_LABEL_ID} data-type="checkbox">
                            {t("Popup")}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <TableContents {...props} />
                </tbody>
            </table>
        </section>
    );
}

function TableContents(props: IProps) {
    const preferencesLoadable = useCategoryNotificationPreferences(props.userID);

    if (["loading", "pending"].includes(preferencesLoadable.status)) {
        return (
            <>
                <LoadingRow />
                <LoadingRow />
                <LoadingRow />
            </>
        );
    }

    if (preferencesLoadable.error || !preferencesLoadable.data) {
        return (
            <tr>
                <td>
                    <ErrorMessages errors={[preferencesLoadable.error ?? { message: t("There was an error") }]} />
                </td>
            </tr>
        );
    }

    // we actually have data now.
    return (
        <>
            {preferencesLoadable.data.map((category) => (
                <TableRow key={category.categoryID} {...props} {...category} />
            ))}
        </>
    );
}

interface IRowProps extends IFollowedCategory {
    isEmailDisabled: boolean;
    userID: number;
}

function TableRow(props: IRowProps) {
    const { name, categoryID, url, isEmailDisabled } = props;
    const classes = categoryNotificationPreferencesClasses();
    const { notificationPreferences, setNotificationPreferences } = useCategoryNotifications(
        props.userID,
        props.categoryID,
        props.preferences,
    );

    useEffect(() => {
        if (props.preferences.useEmailNotifications && isEmailDisabled) {
            setNotificationPreferences({
                useEmailNotifications: false,
            });
        }
    }, []);

    const isPopupEnabled =
        notificationPreferences.postNotifications === "discussions" ||
        notificationPreferences.postNotifications === "all";

    return (
        <tr className={cx(classes.row)} data-category-id={categoryID}>
            <td>
                <SmartLink to={url}>{name}</SmartLink>
            </td>
            {!isEmailDisabled && (
                <td data-type="checkbox">
                    <CheckBox
                        aria-labelledby={EMAIL_LABEL_ID}
                        checked={notificationPreferences.useEmailNotifications}
                        onChange={(e) => {
                            if (e.target.checked) {
                                setNotificationPreferences({
                                    useEmailNotifications: true,
                                    postNotifications:
                                        // If we enable email, enable popups and receive notifications.
                                        [null, "follow"].includes(notificationPreferences.postNotifications)
                                            ? "all"
                                            : notificationPreferences.postNotifications,
                                });
                            } else {
                                setNotificationPreferences({
                                    useEmailNotifications: false,
                                });
                            }
                        }}
                    />
                </td>
            )}
            <td data-type="checkbox">
                {notificationPreferences.useEmailNotifications && !isEmailDisabled ? (
                    <ToolTip label={t("Pop up notifications are enabled by default when email is enabled.")}>
                        <span>
                            <CheckBox aria-labelledby={POPUP_LABEL_ID} checked={isPopupEnabled} disabled={true} />
                        </span>
                    </ToolTip>
                ) : (
                    <CheckBox
                        aria-labelledby={POPUP_LABEL_ID}
                        checked={isPopupEnabled}
                        onChange={(e) => {
                            setNotificationPreferences({
                                useEmailNotifications: false,
                                postNotifications: e.target.checked ? "all" : "follow",
                            });
                        }}
                    />
                )}
            </td>
        </tr>
    );
}

function LoadingRow() {
    const classes = categoryNotificationPreferencesClasses();

    return (
        <tr className={cx(classes.row)}>
            <td>
                <LoadingRectangle width={320} height={18} className={classes.loadingRect} />
            </td>
            <td data-type="checkbox">
                <LoadingRectangle width={18} height={18} className={classes.loadingRect} />
            </td>
            <td data-type="checkbox">
                <LoadingRectangle width={18} height={18} className={classes.loadingRect} />
            </td>
        </tr>
    );
}
