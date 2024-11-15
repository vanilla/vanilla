/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { cx } from "@emotion/css";
import Translate from "@library/content/Translate";
import Checkbox from "@library/forms/Checkbox";
import CheckboxGroup from "@library/forms/CheckboxGroup";
import ErrorMessages from "@library/forms/ErrorMessages";
import { ColumnType, NotificationType, TableType } from "@library/notificationPreferences";
import { makeRowDescriptionId } from "@library/notificationPreferences/utils";
import { categoryPreferencesTableClasses } from "@library/preferencesTable/CategoryPreferencesTable.styles";
import { PreferencesTable } from "@library/preferencesTable/PreferencesTable";
import { notificationPreferencesFormClasses } from "@library/preferencesTable/PreferencesTable.styles";
import { ToolTip } from "@library/toolTip/ToolTip";
import { getMeta } from "@library/utility/appUtils";
import {
    getDefaultCategoryNotificationPreferences,
    ICategoryPreferences,
} from "@vanilla/addon-vanilla/categories/CategoryNotificationPreferences.hooks";
import { t } from "@vanilla/i18n";
import { Icon } from "@vanilla/icons";
import omit from "lodash-es/omit";
import React, { useEffect, useMemo, useState } from "react";
import { Column, Row, useTable } from "react-table";

interface IProps {
    canIncludeInDigest?: boolean;
    preferences: Partial<ICategoryPreferences>;
    notificationTypes: Record<string, NotificationType>;
    onPreferenceChange(delta: Partial<ICategoryPreferences>): Promise<void>;
    defaultNotificationPreferences?: ICategoryPreferences;
    className?: string;
    /** Does the labels reference me, or other users? */
    admin?: boolean;
    /** Disable network calls for widget preview */
    preview?: boolean;
}

/**
 * This component is used to display and emit changes for category (or group) notification preferences
 * Email and Digest visibility is handled internally
 */
export function CategoryPreferencesTable(props: IProps) {
    const { onPreferenceChange, notificationTypes, admin, canIncludeInDigest, preview } = props;
    const formClasses = notificationPreferencesFormClasses();

    const classes = categoryPreferencesTableClasses();

    const emailEnabled = getMeta("emails.enabled");
    const digestEnabled = getMeta("emails.digest", false) && canIncludeInDigest;

    // Check if there are any preferences set
    const hasPreferences = Object.values(
        omit(props.preferences, ["preferences.followed", "preferences.email.digest"]),
    ).some((value) => value);
    // We only display an error if there are no preferences set AND digest is enabled
    const preferenceError = digestEnabled ? !hasPreferences : false;

    const [showNotificationPreferenceTable, setNotificationPreferenceTableVisibility] = useState(
        !digestEnabled || !props.canIncludeInDigest || hasPreferences,
    );

    // Update a preference
    const handlePreferenceChange = async (row: Row<ColumnType>, checked: boolean, type: "popup" | "email") => {
        !preview &&
            (await onPreferenceChange({
                [`preferences.${type}.${row.original.id}`]: checked,
            }));
    };

    useEffect(() => {
        setNotificationPreferenceTableVisibility(!digestEnabled || !props.canIncludeInDigest || hasPreferences);
    }, [hasPreferences]);

    // Format notification preferences as table data
    const data: ColumnType[] = useMemo(() => {
        return Object.entries(notificationTypes).map(([id, categoryNotificationType]) => {
            return {
                popup: props.preferences[`preferences.popup.${id}`],
                ...(emailEnabled && { email: props.preferences[`preferences.email.${id}`] }),
                description: (
                    <Translate
                        source={admin ? "Notify of <0/>" : "Notify me of <0/>"}
                        c0={t(categoryNotificationType.getDescription())}
                    />
                ),
                id,
                error: preferenceError,
            };
        });
    }, [preferenceError, emailEnabled, props.preferences]);

    // Memoized columns for the preference table
    const columns = useMemo<Array<Column<ColumnType>>>(() => {
        const columns: Array<Column<ColumnType>> = [
            {
                accessor: "popup",
                Header: function PopupColumnHeader() {
                    return (
                        <ToolTip label={t("Notification popup")}>
                            <span>
                                <Icon size="default" icon={"me-notifications"} className={formClasses.icon} />
                            </span>
                        </ToolTip>
                    );
                },
                Cell: function RenderCell(cellProps) {
                    return (
                        <Checkbox
                            checked={cellProps.cell.value}
                            className={formClasses.checkbox}
                            onChange={async (event) => {
                                await handlePreferenceChange(cellProps.row, event.target.checked, "popup");
                            }}
                            label={t("Notification popup")}
                            hideLabel
                            aria-describedby={makeRowDescriptionId(cellProps.row)}
                        />
                    );
                },
            },
        ];

        if (emailEnabled) {
            columns.push({
                accessor: "email",
                Header: function EmailColumnHeader() {
                    return (
                        <ToolTip label={t("Notification Email")}>
                            <span>
                                <Icon size="default" icon={"me-inbox"} className={formClasses.icon} />
                            </span>
                        </ToolTip>
                    );
                },
                Cell: function RenderCell(cellProps) {
                    return (
                        <Checkbox
                            checked={cellProps.cell.value}
                            className={formClasses.checkbox}
                            onChange={async (event) => {
                                await handlePreferenceChange(cellProps.row, event.target.checked, "email");
                            }}
                            label={t("Notification Email")}
                            hideLabel
                            aria-describedby={makeRowDescriptionId(cellProps.row)}
                            labelBold={false}
                        />
                    );
                },
            });
        }

        columns.push({
            accessor: "description",
            Header: function RenderDescriptionHeader() {
                return <></>;
            },
            Cell: function RenderDescriptionCell(cellProps) {
                return (
                    <span
                        id={makeRowDescriptionId(cellProps.row)}
                        className={formClasses.tableDescriptionWrapper("normal")}
                    >
                        {cellProps.cell.value}
                        {cellProps.row.original.error && <Icon icon={"notification-alert"} />}
                    </span>
                );
            },
        });

        return columns;
    }, []);

    const table: TableType = useTable({
        data,
        columns,
    });

    return (
        <div className={props.className}>
            {digestEnabled && props.canIncludeInDigest && (
                <CheckboxGroup>
                    <Checkbox
                        label={t(`Include in email digest`)}
                        labelBold={false}
                        onChange={async (event: React.ChangeEvent<HTMLInputElement>) => {
                            await onPreferenceChange({ "preferences.email.digest": event.target.checked });
                        }}
                        checked={props.preferences["preferences.email.digest"]}
                        className={classes.checkBox}
                    />
                    <Checkbox
                        label={t(admin ? "Notify of new content" : "Notify me of new content")}
                        labelBold={false}
                        onChange={async (event: React.ChangeEvent<HTMLInputElement>) => {
                            setNotificationPreferenceTableVisibility(event.target.checked);
                            if (!event.target.checked) {
                                const { "preferences.followed": omitted, ...defaultPreferences } =
                                    props.defaultNotificationPreferences ?? getDefaultCategoryNotificationPreferences(); //this should set all preferences to `false`
                                await onPreferenceChange({
                                    ...defaultPreferences,
                                });
                            }
                        }}
                        checked={showNotificationPreferenceTable}
                        className={classes.checkBox}
                    />
                </CheckboxGroup>
            )}
            <div className={cx({ [classes.inset]: canIncludeInDigest })}>
                {showNotificationPreferenceTable && <PreferencesTable table={table} />}
                {showNotificationPreferenceTable && preferenceError && (
                    <ErrorMessages
                        className={classes.errorBlock}
                        errors={[
                            {
                                message: t(
                                    "At least one notification method must be selected to receive notifications",
                                ),
                            },
                        ]}
                        padded
                    />
                )}
            </div>
        </div>
    );
}
