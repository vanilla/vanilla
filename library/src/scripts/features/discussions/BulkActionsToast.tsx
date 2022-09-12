/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IDiscussion } from "@dashboard/@types/api/discussion";
import Translate from "@library/content/Translate";
import { IGetDiscussionByID } from "@library/features/discussions/DiscussionActions";
import { useDiscussionByIDs } from "@library/features/discussions/discussionHooks";
import { discussionListClasses } from "@library/features/discussions/DiscussionList.classes";
import { hasPermission, PermissionMode } from "@library/features/users/Permission";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import ConditionalWrap from "@library/layout/ConditionalWrap";
import { ToolTip } from "@library/toolTip/ToolTip";
import { t } from "@library/utility/appUtils";
import { RecordID } from "@vanilla/utils";
import React, { useEffect, useMemo } from "react";

interface IProps {
    /** The number of selected discussions */
    selectedIDs: number[] | RecordID[];
    /** Function to clear all selected discussions */
    handleSelectionClear(): void;
    /** Function to delete all selected discussions */
    handleBulkDelete(): void;
    /** Function to move all selected discussions */
    handleBulkMove(): void;
    /** Function to merge all selected discussions */
    handleBulkMerge(): void;
    /** Function to close all selected discussions */
    handleBulkClose(): void;
}

/**
 * This is the toast notification which is displayed when multiple discussions are selected
 *
 * It will also render a modal for synchronous bulk actions
 */
export function BulkActionsToast(props: IProps) {
    const { selectedIDs, handleSelectionClear, handleBulkDelete, handleBulkMove, handleBulkMerge, handleBulkClose } =
        props;
    const classes = discussionListClasses();

    const sanitizedIDs = useMemo(() => {
        return selectedIDs.map((id: RecordID) => Number(id));
    }, [selectedIDs]);

    const countSelectedDiscussions = sanitizedIDs.length;

    const discussions = useDiscussionByIDs(sanitizedIDs ?? []);

    /**
     * Check one permission against a list of discussions
     */
    const checkPermissions = (permission: string, discussionList: Record<RecordID, IDiscussion> | null) => {
        if (discussionList) {
            return Object.values(discussionList)
                .map((discussion) => {
                    if (
                        !hasPermission(permission, {
                            resourceType: "category",
                            mode: PermissionMode.RESOURCE_IF_JUNCTION,
                            resourceID: discussion.categoryID,
                        })
                    ) {
                        return discussion.name;
                    }
                    return null;
                })
                .filter((entry) => entry);
        }
        return [];
    };

    // If all of the selected discussions are already closed, disable the close button
    const isAllClosed = useMemo<boolean>(() => {
        if (discussions) {
            const notClosed = Object.values(discussions).filter(({ closed }) => !closed);
            return notClosed.length === 0;
        }
        return false;
    }, [discussions]);

    // Create a list of all the discussions which we do not have permission to operate on.
    // If the list is empty, we have the required permissions.
    const uneditableDiscussions = useMemo(() => {
        return checkPermissions("discussions.manage", discussions);
    }, [discussions]);

    return (
        <>
            <span className={classes.bulkActionsText}>
                <Translate source={"You have selected <0/> discussions."} c0={selectedIDs.length} />
            </span>
            <div className={classes.bulkActionsButtons}>
                <Button onClick={handleSelectionClear} buttonType={ButtonTypes.TEXT}>
                    {t("Cancel")}
                </Button>
                <ConditionalWrap
                    condition={uneditableDiscussions.length > 0}
                    component={ToolTip}
                    componentProps={{
                        label: `${t(
                            "You don’t have the edit permission on the following discussions:",
                        )} ${uneditableDiscussions.join(", ")}`,
                    }}
                >
                    {/* This span is required for the conditional tooltip */}
                    <span>
                        <Button
                            onClick={handleBulkMove}
                            buttonType={ButtonTypes.TEXT}
                            disabled={uneditableDiscussions.length > 0}
                        >
                            {t("Move")}
                        </Button>
                    </span>
                </ConditionalWrap>
                <ConditionalWrap
                    condition={uneditableDiscussions.length > 0 || countSelectedDiscussions < 2}
                    component={ToolTip}
                    componentProps={{
                        label:
                            countSelectedDiscussions < 2
                                ? t("You must select at least 2 discussions to merge.")
                                : `${t(
                                      "You don’t have the edit permission on the following discussions:",
                                  )} ${uneditableDiscussions.join(", ")}`,
                    }}
                >
                    {/* This span is required for the conditional tooltip */}
                    <span>
                        <Button
                            onClick={handleBulkMerge}
                            buttonType={ButtonTypes.TEXT}
                            disabled={uneditableDiscussions.length > 0 || countSelectedDiscussions < 2}
                        >
                            {t("Merge")}
                        </Button>
                    </span>
                </ConditionalWrap>
                <ConditionalWrap
                    condition={uneditableDiscussions.length > 0}
                    component={ToolTip}
                    componentProps={{
                        label: `${t(
                            "You don't have the close permission on the following discussions:",
                        )} ${uneditableDiscussions.join(", ")}`,
                    }}
                >
                    {/* This span is required for the conditional tooltip */}
                    <span>
                        <Button
                            onClick={handleBulkClose}
                            buttonType={ButtonTypes.TEXT}
                            disabled={uneditableDiscussions.length > 0 || isAllClosed}
                        >
                            {t("Close")}
                        </Button>
                    </span>
                </ConditionalWrap>
                <ConditionalWrap
                    condition={uneditableDiscussions.length > 0}
                    component={ToolTip}
                    componentProps={{
                        label: `${t(
                            "You don’t have the delete permission on the following discussions:",
                        )} ${uneditableDiscussions.join(", ")}`,
                    }}
                >
                    {/* This span is required for the conditional tooltip */}
                    <span>
                        <Button
                            onClick={handleBulkDelete}
                            buttonType={ButtonTypes.TEXT}
                            disabled={uneditableDiscussions.length > 0}
                        >
                            {t("Delete")}
                        </Button>
                    </span>
                </ConditionalWrap>
            </div>
        </>
    );
}
