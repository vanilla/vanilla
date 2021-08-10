/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import { CategoryNotificationPreferencesClasses } from "@dashboard/components/CategoryNotificationPreferences.styles";
import {
    ICategoryPreferences,
    IPatchCategoryParams,
    useCategoryNotificationPreferences,
} from "@dashboard/components/CategoryNotificationHooks";
import { cx } from "@emotion/css";
import CheckBox from "@library/forms/Checkbox";
import { ToolTip } from "@library/toolTip/ToolTip";
import { t } from "@vanilla/i18n";
import React, { useEffect, useMemo, useState } from "react";
import { LoadingRectangle } from "@library/loaders/LoadingRectangle";

interface ITableRowProps extends ICategoryPreferences {
    onChange?(config: IPatchCategoryParams): void;
    isEmailDisabled: boolean;
}

const TableRow = (props: ITableRowProps) => {
    const { name, categoryID, onChange, isEmailDisabled } = props;
    const classes = CategoryNotificationPreferencesClasses();
    const [email, setEmail] = useState<boolean>(props.email);
    const [popup, setPopup] = useState<boolean>(props.popup);

    /**
     * If emails are selected, popup notifications are turned on as well
     */
    const handleEmailPref = (value: boolean) => {
        if (value === true && !popup) {
            setPopup(true);
        }
        setEmail(value);
    };

    useEffect(() => {
        if (onChange && (props.email !== email || props.popup !== popup)) {
            onChange({ email, popup, categoryID });
        }
    }, [email, popup]);

    return (
        <tr className={cx(classes.row)} data-category-id={categoryID}>
            <td>{name}</td>
            {!isEmailDisabled && (
                <td data-type="checkbox">
                    <CheckBox label={""} checked={email} onChange={(e) => handleEmailPref(e.target.checked)} />
                </td>
            )}
            <td data-type="checkbox">
                {email ? (
                    <ToolTip label={t("Pop up notifications are enabled by default when email is enabled.")}>
                        <span>
                            <CheckBox
                                label={""}
                                checked={popup}
                                disabled={email}
                                onChange={(e) => setPopup(e.target.checked)}
                            />
                        </span>
                    </ToolTip>
                ) : (
                    <CheckBox
                        label={""}
                        checked={popup}
                        disabled={email}
                        onChange={(e) => setPopup(e.target.checked)}
                    />
                )}
            </td>
        </tr>
    );
};

export interface ICategoryNotificationPreferencesProps {
    userID: number;
    isEmailDisabled: boolean;
}

export const CategoryNotificationPreferences = (props: ICategoryNotificationPreferencesProps) => {
    const { userID, isEmailDisabled } = props;

    const { preferences, isLoading, setCategoryPreference } = useCategoryNotificationPreferences(userID);
    const classes = CategoryNotificationPreferencesClasses();

    const handleCategoryUpdate = (config: IPatchCategoryParams) => {
        setCategoryPreference(config);
    };

    return (
        // There is some legacy JS that removes the table if its after a h* element
        // Turn this <section> into a fragment if you are refactoring the page
        <section>
            <h2>{t("Followed Categories")}</h2>
            <table className={cx(classes.table)}>
                <thead>
                    <tr className={cx(classes.row)}>
                        <th>{t("Category")}</th>
                        {!isEmailDisabled && <th data-type="checkbox">{t("Email")}</th>}
                        <th data-type="checkbox">{t("Popup")}</th>
                    </tr>
                </thead>
                <tbody>
                    {isLoading ? (
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
                    ) : (
                        preferences.map((category) => (
                            <TableRow
                                key={category.categoryID}
                                categoryID={category.categoryID}
                                name={category.name}
                                email={category.email}
                                isEmailDisabled={isEmailDisabled}
                                popup={category.popup}
                                onChange={handleCategoryUpdate}
                            />
                        ))
                    )}
                </tbody>
            </table>
        </section>
    );
};
