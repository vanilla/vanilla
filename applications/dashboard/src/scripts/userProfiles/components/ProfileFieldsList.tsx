/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { DashboardHeaderBlock } from "@dashboard/components/DashboardHeaderBlock";
import { Table } from "@dashboard/components/Table";
import { dashboardClasses } from "@dashboard/forms/dashboardStyles";
import ProfileFieldsListClasses from "@dashboard/userProfiles/components/ProfileFieldsList.classes";
import { useProfileFields } from "@dashboard/userProfiles/state/UserProfiles.hooks";
import { ProfileField, ProfileFieldRegistrationOptions } from "@dashboard/userProfiles/types/UserProfiles.types";
import { EMPTY_PROFILE_FIELD_CONFIGURATION } from "@dashboard/userProfiles/utils";
import { cx } from "@emotion/css";
import { LoadStatus } from "@library/@types/api/core";
import { ErrorBoundary } from "@library/errorPages/ErrorBoundary";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { FormToggle } from "@library/forms/FormToggle";
import { LoadingRectangle } from "@library/loaders/LoadingRectangle";
import { visibility } from "@library/styles/styleHelpersVisibility";
import { t } from "@vanilla/i18n";
import { Icon } from "@vanilla/icons";
import React, { useMemo } from "react";

interface IProps {
    /** Callback when the edit button is pressed */
    onEdit(field: ProfileField): void;
}

/**
 * This component will display the list of profile fields with optional actions
 */
export function ProfileFieldsList(props: IProps) {
    const { onEdit } = props;
    const profileFields = useProfileFields();

    function labelFromBoolean(boolean: boolean, truthy: string, falsy: string): string {
        return boolean ? truthy : falsy;
    }

    const rows = useMemo(() => {
        if ([LoadStatus.PENDING, LoadStatus.LOADING].includes(profileFields.status)) {
            return [
                {
                    label: <LoadingRectangle width="80" height={16} />,
                    api: <LoadingRectangle width="50" height={16} />,
                    type: <LoadingRectangle width="60" height={16} />,
                    visibility: <LoadingRectangle width="90" height={16} />,
                    editing: <LoadingRectangle width="70" height={16} />,
                    required: <LoadingRectangle width="40" height={16} />,
                    "In Profiles": <LoadingRectangle width="30" height={16} />,
                    registration: <LoadingRectangle width="30" height={16} />,
                    active: <LoadingRectangle width="100" height={16} />,
                },
                {
                    label: <LoadingRectangle width="50" height={16} />,
                    api: <LoadingRectangle width="20" height={16} />,
                    type: <LoadingRectangle width="80" height={16} />,
                    visibility: <LoadingRectangle width="50" height={16} />,
                    editing: <LoadingRectangle width="40" height={16} />,
                    required: <LoadingRectangle width="30" height={16} />,
                    "In Profiles": <LoadingRectangle width="20" height={16} />,
                    registration: <LoadingRectangle width="20" height={16} />,
                    active: <LoadingRectangle width="100" height={16} />,
                },
                {
                    label: <LoadingRectangle width="70" height={16} />,
                    api: <LoadingRectangle width="30" height={16} />,
                    type: <LoadingRectangle width="50" height={16} />,
                    visibility: <LoadingRectangle width="70" height={16} />,
                    editing: <LoadingRectangle width="20" height={16} />,
                    required: <LoadingRectangle width="30" height={16} />,
                    "In Profiles": <LoadingRectangle width="40" height={16} />,
                    registration: <LoadingRectangle width="40" height={16} />,
                    active: <LoadingRectangle width="100" height={16} />,
                },
            ];
        }
        if (profileFields.data && profileFields.data.length > 0) {
            return profileFields.data.map((field: ProfileField) => {
                // To create a subset and customized labels
                return {
                    label: field.label,
                    api: field.apiName,
                    type: field.formType,
                    visibility: field.visibility,
                    editing: field.mutability, // These labels don't match the mock ups
                    required: labelFromBoolean(
                        field.registrationOptions === ProfileFieldRegistrationOptions.REQUIRED,
                        t("Yes"),
                        t("No"),
                    ),
                    "In Profiles": labelFromBoolean(field.displayOptions.profiles, t("Yes"), t("No")),
                    registration: field.registrationOptions,
                    active: (
                        <RowActions
                            fieldName={field.apiName}
                            onChange={() => null}
                            onEdit={() => onEdit && onEdit(field)}
                            // TODO: https://higherlogic.atlassian.net/browse/VNLA-1981
                            onDelete={() => null}
                        />
                    ),
                };
            });
        }
        return null;
    }, [onEdit, profileFields.data]);

    const classes = ProfileFieldsListClasses();

    return (
        <ErrorBoundary>
            <section className={classes.root}>
                <DashboardHeaderBlock
                    title={t("Custom Profile Fields")}
                    actionButtons={
                        <Button
                            onClick={() => {
                                onEdit(EMPTY_PROFILE_FIELD_CONFIGURATION);
                            }}
                        >
                            {t("Add Field")}
                        </Button>
                    }
                />
                <div className={cx(dashboardClasses().extendRow, classes.scrollTable)}>
                    {rows && (
                        <Table
                            headerClassNames={classes.dashboardHeaderStyles}
                            rowClassNames={cx(classes.extendTableRows, classes.highlightLabels)}
                            data={rows}
                        />
                    )}
                </div>
            </section>
        </ErrorBoundary>
    );
}

interface RowActionProps {
    fieldName: string;
    onChange(): void;
    onEdit(): void;
    onDelete(): void;
}
function RowActions(props: RowActionProps) {
    const { fieldName, onChange, onEdit, onDelete } = props;

    const classes = ProfileFieldsListClasses();

    return (
        <div className={classes.actionsLayout}>
            {/* TODO: https://higherlogic.atlassian.net/browse/VNLA-2494 */}
            {/* <FormToggle labelID={fieldName} enabled={false} onChange={() => onChange()} /> */}
            <Button
                className={classes.editIconSize}
                onClick={() => onEdit()}
                ariaLabel={t("Edit")}
                buttonType={ButtonTypes.ICON_COMPACT}
            >
                <Icon icon={"dashboard-edit"} />
                <span className={visibility().visuallyHidden}>{t("Edit")}</span>
            </Button>
            {/* TODO: https://higherlogic.atlassian.net/browse/VNLA-1981 */}
            {/* <Button
                className={classes.deleteIconSize}
                onClick={() => onDelete()}
                ariaLabel={t("Delete")}
                buttonType={ButtonTypes.ICON_COMPACT}
            >
                <Icon icon={"data-trash"} />
                <span className={visibility().visuallyHidden}>{t("Delete")}</span>
            </Button> */}
        </div>
    );
}
