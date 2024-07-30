/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import AddEditReportReasonModal from "@dashboard/communityManagementSettings/AddEditReportReasonModal";
import ReorderReportReasonModal from "@dashboard/communityManagementSettings/ReorderReportReasonModal";
import {
    reportReasonListClasses,
    rowActionsClasses,
} from "@dashboard/communityManagementSettings/ReportReasonList.classes";
import { Table } from "@dashboard/components/Table";
import { DashboardFormSubheading } from "@dashboard/forms/DashboardFormSubheading";
import { dashboardClasses } from "@dashboard/forms/dashboardStyles";
import { useReasonsDeleteMutation, useReportReasons } from "@dashboard/moderation/CommunityManagement.hooks";
import { IReason } from "@dashboard/moderation/CommunityManagementTypes";
import { IRoleFragment } from "@dashboard/roles/roleTypes";
import { cx } from "@emotion/css";
import { humanReadableNumber } from "@library/content/NumberFormatted";
import { ErrorBoundary } from "@library/errorPages/ErrorBoundary";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import ErrorMessages from "@library/forms/ErrorMessages";
import { LoadingRectangle } from "@library/loaders/LoadingRectangle";
import { TokenItem } from "@library/metas/TokenItem";
import ModalConfirm from "@library/modal/ModalConfirm";
import { visibility } from "@library/styles/styleHelpersVisibility";
import { ToolTip } from "@library/toolTip/ToolTip";
import { t } from "@vanilla/i18n";
import { Icon } from "@vanilla/icons";
import { useMemo, useState } from "react";
import { TableInstance } from "react-table";

interface IProps {}

export function ReportReasonList(props: IProps) {
    const classes = reportReasonListClasses();
    const { reasons, isLoading, error } = useReportReasons();
    const deleteReason = useReasonsDeleteMutation();
    const [confirmDeleteModalVisible, setConfirmDeleteModalVisible] = useState(false);
    const [addEditVisibility, setAddEditVisibility] = useState(false);
    const [reorderVisibility, setReorderVisibility] = useState(false);
    const [reportReasonToEdit, setReportReasonToEdit] = useState<IReason | null>(null);
    const [reportReasonToDelete, setReportReasonToDelete] = useState<IReason["reportReasonID"] | null>(null);

    const reasonTableData = useMemo(() => {
        if (isLoading) {
            return [1, 2, 3].map((x) => {
                return {
                    name: <LoadingRectangle width={"20%"} inline />,
                    description: <LoadingRectangle width={"80%"} inline />,
                    roles: [
                        {
                            roleID: "1",
                            name: <LoadingRectangle width={50} inline />,
                        },
                        {
                            roleID: "2",
                            name: <LoadingRectangle width={50} inline />,
                        },
                        {
                            roleID: "3",
                            name: <LoadingRectangle width={50} inline />,
                        },
                    ],
                    actions: (
                        <>
                            <LoadingRectangle width={16} inline />
                            <LoadingRectangle width={16} inline />
                        </>
                    ),
                };
            });
        }
        return (reasons?.data ?? [])
            .sort((a, b) => (a.sort > b.sort ? 1 : -1))
            .map((reason, index) => {
                return {
                    id: reason.reportReasonID,
                    name: reason.name,
                    description: reason.description,
                    roles: reason.roles,
                    usage: humanReadableNumber(reason.countReports, 0),
                    actions: (
                        <RowActions
                            reportReasonID={reason.reportReasonID}
                            onEdit={() => {
                                setReportReasonToEdit(reason);
                                setAddEditVisibility(true);
                            }}
                            onDelete={() => {
                                setReportReasonToDelete(reason.reportReasonID);
                                setConfirmDeleteModalVisible(true);
                            }}
                        />
                    ),
                };
            });
    }, [isLoading, reasons.data]);

    const handleDelete = async () => {
        await deleteReason.mutateAsync(reportReasonToDelete);
        setReportReasonToDelete(null);
        setConfirmDeleteModalVisible(false);
    };

    const hasError = !isLoading && error;

    return (
        <ErrorBoundary>
            <section>
                <DashboardFormSubheading
                    hasBackground
                    actions={
                        <>
                            <Button disabled={!reasons.data?.length} onClick={() => setReorderVisibility(true)}>
                                {t("Reorder")}
                            </Button>

                            <Button
                                onClick={() => {
                                    setAddEditVisibility(true);
                                }}
                            >
                                {t("Add Reason")}
                            </Button>
                        </>
                    }
                >
                    {t("Report Reasons")}
                </DashboardFormSubheading>
                <div className={cx(dashboardClasses().extendRow)}>
                    {reasonTableData.length > 0 && (
                        <Table
                            headerClassNames={classes.dashboardHeaderStyles}
                            rowClassNames={cx(classes.extendTableRows)}
                            cellClassNames={classes.cellOverride}
                            data={reasonTableData}
                            columnSizes={[50, 40, 10]}
                            hiddenColumns={["id", "description"]}
                            hiddenHeaders={["actions"]}
                            customCellRenderer={[
                                {
                                    columnName: ["name"],
                                    component: function NameDescriptionCell(props: TableInstance) {
                                        return (
                                            <div className={classes.nameDescriptionCellRoot}>
                                                <span className={classes.nameDescriptionCellName}>{props.value}</span>
                                                <span className={classes.nameDescriptionCellDescription}>
                                                    {props.row.original.description}
                                                </span>
                                            </div>
                                        );
                                    },
                                },
                                {
                                    columnName: ["roles"],
                                    component: function RoleTokenCell(props: TableInstance) {
                                        return (
                                            <div className={classes.roleCellRoot}>
                                                {props.value && props.value.length > 0 ? (
                                                    props.value.map((roleFragment: IRoleFragment) => (
                                                        <TokenItem key={roleFragment.roleID}>
                                                            {roleFragment.name}
                                                        </TokenItem>
                                                    ))
                                                ) : (
                                                    <span className={classes.allRoles}>
                                                        This reason can be seen by all roles with the flag.add
                                                        permission
                                                    </span>
                                                )}
                                            </div>
                                        );
                                    },
                                },
                            ]}
                        />
                    )}

                    {!isLoading && !reasons.data && !hasError && (
                        <div className={classes.emptyState}>
                            <h3>{t("Report reasons will appear here.")}</h3>
                            <p>{t(`Use the "Add Reason" button above to get started.`)}</p>
                        </div>
                    )}
                    {hasError && (
                        <div className={classes.errorContainer}>
                            <ErrorMessages errors={[error]} />
                        </div>
                    )}
                </div>
            </section>
            <AddEditReportReasonModal
                reportReason={reportReasonToEdit}
                isVisible={addEditVisibility}
                onVisibilityChange={() => {
                    setReportReasonToEdit(null);
                    setAddEditVisibility(false);
                }}
            />
            <ReorderReportReasonModal
                reportReasons={reasons.data!}
                isVisible={reorderVisibility}
                onVisibilityChange={setReorderVisibility}
            />
            <ModalConfirm
                isVisible={confirmDeleteModalVisible}
                title={t("Delete?")}
                onCancel={() => setConfirmDeleteModalVisible(false)}
                onConfirm={() => handleDelete()}
                confirmTitle={t("Delete")}
            >
                {t("Are you sure you want to delete this reason?")}
            </ModalConfirm>
        </ErrorBoundary>
    );
}

interface IRowActionsProps {
    reportReasonID: IReason["reportReasonID"];
    onEdit: (reportReasonID: IReason["reportReasonID"]) => void;
    onDelete: (reportReasonID: IReason["reportReasonID"]) => void;
}

function RowActions(props: IRowActionsProps) {
    const { reportReasonID, onEdit, onDelete } = props;
    const classes = rowActionsClasses();
    return (
        <div className={classes.actions}>
            <ToolTip label={t("Edit Report Reason")}>
                <Button
                    className={classes.editIconSize}
                    onClick={() => onEdit(reportReasonID)}
                    ariaLabel={t("Edit")}
                    buttonType={ButtonTypes.ICON_COMPACT}
                >
                    <Icon icon={"dashboard-edit"} />
                    <span className={visibility().visuallyHidden}>{t("Edit")}</span>
                </Button>
            </ToolTip>
            <ToolTip label={t("Delete Report Reason")}>
                <Button
                    className={classes.deleteIconSize}
                    onClick={() => onDelete(reportReasonID)}
                    ariaLabel={t("Delete")}
                    buttonType={ButtonTypes.ICON_COMPACT}
                >
                    <Icon icon={"data-trash"} />
                    <span className={visibility().visuallyHidden}>{t("Delete")}</span>
                </Button>
            </ToolTip>
        </div>
    );
}
