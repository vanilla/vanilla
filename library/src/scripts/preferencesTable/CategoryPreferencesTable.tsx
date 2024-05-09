/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { cx } from "@emotion/css";
import Checkbox from "@library/forms/Checkbox";
import CheckboxGroup from "@library/forms/CheckboxGroup";
import ErrorMessages from "@library/forms/ErrorMessages";
import { ColumnType, TableType } from "@library/notificationPreferences";
import { makeRowDescriptionId } from "@library/notificationPreferences/utils";
import { categoryPreferencesTableClasses } from "@library/preferencesTable/CategoryPreferencesTable.styles";
import { PreferencesTable } from "@library/preferencesTable/PreferencesTable";
import { notificationPreferencesFormClasses } from "@library/preferencesTable/PreferencesTable.styles";
import { ToolTip } from "@library/toolTip/ToolTip";
import { getMeta } from "@library/utility/appUtils";
import { ICategoryPreferences } from "@vanilla/addon-vanilla/categories/categoriesTypes";
import { t } from "@vanilla/i18n";
import { Icon } from "@vanilla/icons";
import omit from "lodash-es/omit";
import React, { useEffect, useMemo, useState } from "react";
import { Column, Row, useTable } from "react-table";

interface IProps {
    canIncludeInDigest?: boolean;
    preferences: ICategoryPreferences;
    onPreferenceChange(delta: Partial<ICategoryPreferences>): void;
    className?: string;
    /** Does the labels reference me, or other users? */
    admin?: boolean;
    /** Disable network calls for widget preview */
    preview?: boolean;
}

/**
 * This component is used to display and emit changes for category preferences
 * Email and Digest visibility is handled internally
 */
export function CategoryPreferencesTable(props: IProps) {
    const { onPreferenceChange, admin, canIncludeInDigest, preview } = props;
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
    const handlePreferenceChange = (row: Row<ColumnType>, checked: boolean, type: "popup" | "email") => {
        !preview &&
            onPreferenceChange({
                [`preferences.${type}.${row.original.id}`]: checked,
            });
    };

    useEffect(() => {
        setNotificationPreferenceTableVisibility(!digestEnabled || !props.canIncludeInDigest || hasPreferences);
    }, [hasPreferences]);

    const subject = admin ? "" : "me";

    // Format notification preferences as table data
    const data: ColumnType[] = useMemo(() => {
        return [
            {
                popup: props.preferences["preferences.popup.posts"],
                ...(emailEnabled && { email: props.preferences["preferences.email.posts"] }),
                description: `Notify ${subject} of new posts`,
                id: "posts",
                error: preferenceError,
            },
            {
                popup: props.preferences["preferences.popup.comments"],
                ...(emailEnabled && { email: props.preferences["preferences.email.comments"] }),
                description: `Notify ${subject} of new comments`,
                id: "comments",
                error: preferenceError,
            },
        ];
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
                                <Icon size="default" icon={"me-notifications"} />
                            </span>
                        </ToolTip>
                    );
                },
                Cell: function RenderCell(cellProps) {
                    return (
                        <Checkbox
                            checked={cellProps.cell.value}
                            className={formClasses.checkbox}
                            onChange={(event) => {
                                handlePreferenceChange(cellProps.row, event.target.checked, "popup");
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
                                <Icon size="default" icon={"me-inbox"} />
                            </span>
                        </ToolTip>
                    );
                },
                Cell: function RenderCell(cellProps) {
                    return (
                        <Checkbox
                            checked={cellProps.cell.value}
                            className={formClasses.checkbox}
                            onChange={(event) => {
                                handlePreferenceChange(cellProps.row, event.target.checked, "email");
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
                        onChange={(event: React.ChangeEvent<HTMLInputElement>) => {
                            onPreferenceChange({ "preferences.email.digest": event.target.checked });
                        }}
                        checked={props.preferences["preferences.email.digest"]}
                        className={classes.checkBox}
                    />
                    <Checkbox
                        // TODO: Make two locale strings for this and others like this
                        label={t(`Notify ${subject} of new content`)}
                        labelBold={false}
                        onChange={(event: React.ChangeEvent<HTMLInputElement>) => {
                            setNotificationPreferenceTableVisibility(event.target.checked);
                            if (!event.target.checked) {
                                onPreferenceChange({
                                    "preferences.email.comments": false,
                                    "preferences.email.posts": false,
                                    "preferences.popup.comments": false,
                                    "preferences.popup.posts": false,
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
