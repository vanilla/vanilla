/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { DashboardHeaderBlock } from "@dashboard/components/DashboardHeaderBlock";
import { Table } from "@dashboard/components/Table";
import { dashboardClasses } from "@dashboard/forms/dashboardStyles";
import ProfileFieldsListClasses from "@dashboard/userProfiles/components/ProfileFieldsList.classes";
import { ProfileFieldVisibilityIcon } from "@dashboard/userProfiles/components/ProfileFieldVisibilityIcon";
import ReorderProfileFields from "@dashboard/userProfiles/components/ReorderProfileFields";
import {
    usePatchProfileField,
    useProfileFields,
    usePutProfileFieldsSorts,
} from "@dashboard/userProfiles/state/UserProfiles.hooks";
import { ProfileField } from "@dashboard/userProfiles/types/UserProfiles.types";
import { EMPTY_PROFILE_FIELD_CONFIGURATION } from "@dashboard/userProfiles/utils";
import { cx } from "@emotion/css";
import { LoadStatus } from "@library/@types/api/core";
import Translate from "@library/content/Translate";
import { ErrorBoundary } from "@library/errorPages/ErrorBoundary";
import { useToast } from "@library/features/toaster/ToastContext";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { DashboardToggle } from "@dashboard/forms/DashboardToggle";
import { LoadingRectangle } from "@library/loaders/LoadingRectangle";
import { visibility } from "@library/styles/styleHelpersVisibility";
import { t } from "@vanilla/i18n";
import { Icon } from "@vanilla/icons";
import sortBy from "lodash/sortBy";
import React, { useMemo, useState } from "react";
import ConditionalWrap from "@library/layout/ConditionalWrap";
import { ToolTip } from "@library/toolTip/ToolTip";

interface IProps {
    /** Callback when the edit button is pressed */
    onEdit(field: ProfileField): void;
    /** Callback when the delete button is pressed */
    onDelete(field: ProfileField): void;
}

/**
 * This component will display the list of profile fields with optional actions
 */
export function ProfileFieldsList(props: IProps) {
    const { onEdit, onDelete } = props;
    const profileFields = useProfileFields();
    const profileFieldsLoaded = ![LoadStatus.PENDING, LoadStatus.LOADING].includes(profileFields.status);
    const profileFieldsAvailable = profileFieldsLoaded && profileFields.data && profileFields.data.length > 0;

    const sortedProfileFields = profileFieldsAvailable ? sortBy(profileFields.data, (field) => field.sort) : undefined;

    const patchProfileField = usePatchProfileField();

    const putProfileFieldsSorts = usePutProfileFieldsSorts();

    const toast = useToast();

    const [reorderModalIsVisible, setReorderModalIsVisible] = useState(false);

    const errorMessage = "There was an error saving your changes. Please try again.";

    const toggleEnabled = async (field) => {
        try {
            await patchProfileField({
                ...field,
                enabled: !field.enabled,
            });
            toast.addToast({
                autoDismiss: true,
                body: (
                    <Translate
                        source={"<0/> has been <1/>"}
                        c0={field.label}
                        c1={field.enabled ? "disabled" : "enabled"}
                    />
                ),
            });
        } catch {
            toast.addToast({
                dismissible: true,
                body: <>{t(errorMessage)}</>,
            });
        }
    };

    function handleReorderProfileFieldsFormSuccess() {
        toast.addToast({
            autoDismiss: true,
            body: <Translate source={"Your changes have been saved."} />,
        });
        setReorderModalIsVisible(false);
    }
    function handleReorderProfileFieldsFormError(error: any) {
        toast.addToast({
            dismissible: true,
            body: <>{t(errorMessage)}</>,
        });
    }

    const rows = useMemo(() => {
        if (!profileFieldsLoaded) {
            return [
                {
                    label: <LoadingRectangle width="80" height={16} />,
                    "api label": <LoadingRectangle width="50" height={16} />,
                    type: <LoadingRectangle width="60" height={16} />,
                    visibility: <LoadingRectangle width="90" height={16} />,
                    active: <LoadingRectangle width="90" height={16} />,
                    actions: <LoadingRectangle width="100" height={16} />,
                },
                {
                    label: <LoadingRectangle width="50" height={16} />,
                    "api label": <LoadingRectangle width="20" height={16} />,
                    type: <LoadingRectangle width="80" height={16} />,
                    visibility: <LoadingRectangle width="50" height={16} />,
                    active: <LoadingRectangle width="50" height={16} />,
                    actions: <LoadingRectangle width="100" height={16} />,
                },
                {
                    label: <LoadingRectangle width="70" height={16} />,
                    "api label": <LoadingRectangle width="30" height={16} />,
                    type: <LoadingRectangle width="50" height={16} />,
                    visibility: <LoadingRectangle width="70" height={16} />,
                    active: <LoadingRectangle width="70" height={16} />,
                    actions: <LoadingRectangle width="100" height={16} />,
                },
            ];
        }
        if (profileFieldsAvailable) {
            return sortedProfileFields!.map((field: ProfileField) => {
                const canDelete = !field.isCoreField;
                const deleteDisabledTooltip = field.isCoreField
                    ? t("To remove this field, disable the User Tags addon.")
                    : undefined;
                // To create a subset and customized labels
                return {
                    label: field.label,
                    "api label": field.apiName,
                    type: field.formType,
                    visibility: (
                        <>
                            {field.visibility}
                            <ProfileFieldVisibilityIcon visibility={field.visibility} />
                        </>
                    ),
                    active: <DashboardToggle checked={field.enabled ?? false} onChange={() => toggleEnabled(field)} />,
                    actions: (
                        <RowActions
                            fieldName={field.apiName}
                            onEdit={() => onEdit && onEdit(field)}
                            onDelete={() => onDelete && onDelete(field)}
                            canDelete={canDelete}
                            deleteDisabledTooltip={deleteDisabledTooltip}
                        />
                    ),
                };
            });
        }
        return null;
    }, [onEdit, onDelete, profileFields.data]);

    const classes = ProfileFieldsListClasses();

    return (
        <ErrorBoundary>
            <section className={classes.root}>
                <DashboardHeaderBlock
                    title={t("Custom Profile Fields")}
                    actionButtons={
                        <div className={classes.actionButtonsContainer}>
                            <Button
                                disabled={!profileFieldsAvailable}
                                onClick={() => {
                                    setReorderModalIsVisible(true);
                                }}
                            >
                                {t("Reorder")}
                            </Button>

                            <Button
                                onClick={() => {
                                    onEdit(EMPTY_PROFILE_FIELD_CONFIGURATION);
                                }}
                            >
                                {t("Add Field")}
                            </Button>
                        </div>
                    }
                />
                <div className={cx(dashboardClasses().extendRow, classes.scrollTable)}>
                    {rows && (
                        <Table
                            headerClassNames={classes.dashboardHeaderStyles}
                            rowClassNames={cx(classes.extendTableRows, classes.highlightLabels)}
                            data={rows}
                            hiddenHeaders={["actions"]}
                        />
                    )}
                </div>
            </section>

            {profileFieldsAvailable && (
                <ReorderProfileFields
                    isVisible={reorderModalIsVisible}
                    sortedProfileFields={sortedProfileFields!}
                    onSubmit={async function (values) {
                        await putProfileFieldsSorts(values);
                    }}
                    onCancel={() => setReorderModalIsVisible(false)}
                    onSubmitSuccess={handleReorderProfileFieldsFormSuccess}
                    onSubmitError={handleReorderProfileFieldsFormError}
                />
            )}
        </ErrorBoundary>
    );
}

interface RowActionProps {
    fieldName: string;
    onEdit(): void;
    onDelete(): void;
    canDelete: boolean;
    deleteDisabledTooltip?: string;
}

function RowActions(props: RowActionProps) {
    const { onEdit, onDelete, canDelete, deleteDisabledTooltip } = props;

    const classes = ProfileFieldsListClasses();

    return (
        <div className={classes.actionsLayout}>
            <Button
                className={classes.editIconSize}
                onClick={onEdit}
                ariaLabel={t("Edit")}
                buttonType={ButtonTypes.ICON_COMPACT}
            >
                <Icon icon={"dashboard-edit"} />
                <span className={visibility().visuallyHidden}>{t("Edit")}</span>
            </Button>
            <ConditionalWrap
                component={ToolTip}
                condition={!canDelete}
                componentProps={{ label: deleteDisabledTooltip }}
            >
                <span>
                    <Button
                        className={classes.deleteIconSize}
                        onClick={onDelete}
                        ariaLabel={t("Delete")}
                        buttonType={ButtonTypes.ICON_COMPACT}
                        disabled={!canDelete}
                    >
                        <Icon icon={"data-trash"} />
                        <span className={visibility().visuallyHidden}>{t("Delete")}</span>
                    </Button>
                </span>
            </ConditionalWrap>
        </div>
    );
}
