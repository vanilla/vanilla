/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { cx } from "@emotion/css";
import Translate from "@library/content/Translate";
import { FollowedContentNotificationPreferences } from "@library/followedContent/FollowedContent.types";
import Checkbox from "@library/forms/Checkbox";
import CheckboxGroup from "@library/forms/CheckboxGroup";
import ErrorMessages from "@library/forms/ErrorMessages";
import { ColumnType, NotificationType, TableType } from "@library/notificationPreferences";
import { makeRowDescriptionId } from "@library/notificationPreferences/utils";
import { followedNotificationPreferencesTableClasses } from "@library/followedContent/FollowedNotificationPreferencesTable/FollowedNotificationPreferencesTable.styles";
import { PreferencesTable } from "@library/preferencesTable/PreferencesTable";
import { notificationPreferencesFormClasses } from "@library/preferencesTable/PreferencesTable.styles";
import { ToolTip } from "@library/toolTip/ToolTip";
import { getMeta } from "@library/utility/appUtils";
import { t } from "@vanilla/i18n";
import { Icon } from "@vanilla/icons";
import omit from "lodash-es/omit";
import React, { useEffect, useMemo, useState } from "react";
import { Column, Row, useTable } from "react-table";

/**
 * This component is used to display and emit changes for notification preferences about followed resources (categories, groups, etc.)
 * Email and Digest visibility is handled internally
 */
export function FollowedNotificationPreferencesTable<T extends Record<string, NotificationType>>(props: {
    notificationTypes: T;
    preferences: Omit<FollowedContentNotificationPreferences<T>, "preferences.followed">;
    onPreferenceChange(
        delta: Partial<Omit<FollowedContentNotificationPreferences<T>, "preferences.followed">>,
    ): Promise<void>;
    canIncludeInDigest?: boolean;
    className?: string;
    /** Does the labels reference me, or other users? */
    admin?: boolean;
    /** Disable network calls for widget preview */
    preview?: boolean;
}) {
    type PartialPreferences = Parameters<typeof onPreferenceChange>[0];

    const { preferences, onPreferenceChange, notificationTypes, admin, canIncludeInDigest = false, preview } = props;
    const formClasses = notificationPreferencesFormClasses();

    //this should set all preferences to `false`
    function getDefaults(): PartialPreferences {
        const defaults = Object.values(notificationTypes).reduce<PartialPreferences>(
            (acc, type) => {
                return {
                    ...acc,
                    ...type.getDefaultPreferences(),
                };
            },
            {
                "preferences.email.digest": false,
            } as PartialPreferences,
        );

        return defaults;
    }

    const classes = followedNotificationPreferencesTableClasses();

    const emailEnabled = getMeta("emails.enabled");
    const digestEnabled = getMeta("emails.digest", false) && canIncludeInDigest;

    // Check if there are any preferences set
    const hasPreferences = Object.values(omit(preferences, ["preferences.followed", "preferences.email.digest"])).some(
        (value) => value,
    );

    // We only display an error if there are no preferences set AND digest is enabled
    const preferenceError = digestEnabled ? !hasPreferences : false;

    const [showNotificationPreferenceTable, setNotificationPreferenceTableVisibility] = useState(
        !digestEnabled || !props.canIncludeInDigest || hasPreferences,
    );

    // Update a preference
    const handlePreferenceChange = async (row: Row<ColumnType>, checked: boolean, type: "popup" | "email") => {
        if (!preview) {
            const rowKey = row.original.id;
            const key = `preferences.${type}.${rowKey}`;

            await onPreferenceChange({
                [key]: checked,
            } as PartialPreferences);
        }
    };

    useEffect(() => {
        setNotificationPreferenceTableVisibility(!digestEnabled || !props.canIncludeInDigest || hasPreferences);
    }, [hasPreferences]);

    // Format notification preferences as table data
    const data: ColumnType[] = useMemo(() => {
        return Object.entries(notificationTypes).map(([id, notificationType]) => {
            return {
                popup: preferences[`preferences.popup.${id}`],
                ...(emailEnabled && { email: preferences[`preferences.email.${id}`] }),
                description: (
                    <Translate
                        source={admin ? "Notify of <0/>" : "Notify me of <0/>"}
                        c0={t(notificationType.getDescription())}
                    />
                ),
                id,
                error: preferenceError,
            };
        });
    }, [preferenceError, emailEnabled, preferences]);

    // Memoized columns for the preference table
    const columns = useMemo<Array<Column<ColumnType>>>(() => {
        const columns: Array<Column<ColumnType>> = [
            {
                accessor: "popup",
                Header: function PopupColumnHeader() {
                    return (
                        <ToolTip label={t("Notification popup")}>
                            <span>
                                <Icon size="default" icon={"me-notifications-filled"} className={formClasses.icon} />
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
                                <Icon size="default" icon={"notify-email"} className={formClasses.icon} />
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
                        {cellProps.row.original.error && <Icon icon={"status-alert"} />}
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
                            await onPreferenceChange({
                                "preferences.email.digest": !!event.target.checked,
                            } as PartialPreferences);
                        }}
                        checked={preferences["preferences.email.digest"]}
                        className={classes.checkBox}
                    />
                    <Checkbox
                        label={t(admin ? "Notify of new content" : "Notify me of new content")}
                        labelBold={false}
                        onChange={async (event: React.ChangeEvent<HTMLInputElement>) => {
                            setNotificationPreferenceTableVisibility(event.target.checked);
                            if (!event.target.checked) {
                                await onPreferenceChange(getDefaults());
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
