/*
 * @author Carla França <cfranca@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useContext, useEffect, useMemo, useState } from "react";
import { RecordID } from "@vanilla/utils";
import { useSessionStorage } from "@vanilla/react-utils";
import { useToast } from "@library/features/toaster/ToastContext";
import { BulkActionsToast } from "@library/features/discussions/BulkActionsToast";
import { BulkActionTypes } from "@library/features/discussions/BulkActionsModal";
import { BulkActionsManager } from "@library/features/discussions/BulkActionsManager";
import { IDiscussion } from "@dashboard/@types/api/discussion";
import { discussionListClasses } from "@library/features/discussions/DiscussionList.classes";

/**
 * Context provider to manage the state of checkboxes when
 * selecting discussions for bulk actions.
 */
interface IDiscussionCheckboxContext {
    /** Record of all checked discussions */
    checkedDiscussionIDs: Array<IDiscussion["discussionID"]>;
    /** Record of all discussions that have a pending action */
    pendingActionIDs: Array<IDiscussion["discussionID"]>;
    /** Function to add discussion to checked list */
    addCheckedDiscussionsByIDs(discussionID: IDiscussion["discussionID"] | Array<IDiscussion["discussionID"]>): void;
    /** Function to remove discussion to checked list */
    removeCheckedDiscussionsByIDs(discussionID: IDiscussion["discussionID"] | Array<IDiscussion["discussionID"]>): void;
    /** Function to add discussion to checked list */
    addPendingDiscussionByIDs(discussionID: IDiscussion["discussionID"] | Array<IDiscussion["discussionID"]>): void;
    /** Function to remove discussion to checked list */
    removePendingDiscussionByIDs(discussionID: IDiscussion["discussionID"] | Array<IDiscussion["discussionID"]>): void;
}

const DiscussionCheckboxContext = React.createContext<IDiscussionCheckboxContext>({
    checkedDiscussionIDs: [],
    pendingActionIDs: [],
    addCheckedDiscussionsByIDs: () => {},
    removeCheckedDiscussionsByIDs: () => {},
    addPendingDiscussionByIDs: () => {},
    removePendingDiscussionByIDs: () => {},
});

export function useDiscussionCheckBoxContext() {
    return useContext(DiscussionCheckboxContext);
}

interface IProps {
    children: React.ReactNode;
}
/**
 * This component is responsible for managing discussion selection and bulk action toast
 */
export function DiscussionCheckboxProvider(props: IProps) {
    const { children } = props;
    const classes = discussionListClasses();

    const { addToast, updateToast, removeToast } = useToast();

    // Using hook to store ids in sessionStorage
    const [checkedDiscussionIDs, setCheckedDiscussionIDs] = useSessionStorage<RecordID[]>("checkedDiscussionsIDs", []);
    const [pendingActionIDs, setPendingActionIDs] = useSessionStorage<RecordID[]>("pendingDiscussionIDs", []);

    /**
     * This state manages toasts
     * The addToast function returns an ID used to identify that toast for future updates
     * Bulk actions could potentially generate multiple job IDs asynchronously
     * This state should be used to map a toastID to the a jobID, so that toasts can be
     * updated to reflect the job status accurately.
     */
    const [toastIDsByKey, setToastIDsByKey] = useState<Record<string, any>>({});

    const [bulkAction, setBulkAction] = useState<BulkActionTypes | null>(null);

    /**
     * Utility function to normalize singular values to array
     */
    const normalizeToArray = (arg: any): any[] => {
        return Array.isArray(arg) ? arg : [arg];
    };

    const addCheckedDiscussionsByIDs = (
        discussionIDs: IDiscussion["discussionID"] | Array<IDiscussion["discussionID"]>,
    ) => {
        setCheckedDiscussionIDs([...new Set([...checkedDiscussionIDs, ...normalizeToArray(discussionIDs)])]);
    };

    const removeCheckedDiscussionsByIDs = (
        discussionIDs: IDiscussion["discussionID"] | Array<IDiscussion["discussionID"]>,
    ) => {
        setCheckedDiscussionIDs((prevState) => prevState.filter((id) => !normalizeToArray(discussionIDs).includes(id)));
    };

    const addPendingDiscussionByIDs = (
        discussionIDs: IDiscussion["discussionID"] | Array<IDiscussion["discussionID"]>,
    ) => {
        setPendingActionIDs([...new Set([...pendingActionIDs, ...normalizeToArray(discussionIDs)])]);
    };

    const removePendingDiscussionByIDs = (
        discussionIDs: IDiscussion["discussionID"] | Array<IDiscussion["discussionID"]>,
    ) => {
        removeCheckedDiscussionsByIDs(discussionIDs);
        setPendingActionIDs((prevState) => prevState.filter((id) => !normalizeToArray(discussionIDs).includes(id)));
    };

    const removeAllDiscussionIDs = () => {
        setCheckedDiscussionIDs([]);
    };

    // The bulk actions toast body
    const toastBody = useMemo(() => {
        if (checkedDiscussionIDs.length > 0) {
            return (
                <BulkActionsToast
                    selectedIDs={checkedDiscussionIDs}
                    handleSelectionClear={removeAllDiscussionIDs}
                    handleBulkDelete={() => setBulkAction(BulkActionTypes.DELETE)}
                    handleBulkMove={() => setBulkAction(BulkActionTypes.MOVE)}
                    handleBulkMerge={() => setBulkAction(BulkActionTypes.MERGE)}
                    handleBulkClose={() => setBulkAction(BulkActionTypes.CLOSE)}
                />
            );
        } else {
            return null;
        }
    }, [checkedDiscussionIDs]);

    // Manage the bulk actions toast
    useEffect(() => {
        const bulkActionsToastID = toastIDsByKey["action"];
        if (toastBody && !bulkActionsToastID) {
            setToastIDsByKey({
                action: addToast({ persistent: true, body: toastBody, className: classes.bulkActionsToast }),
            });
        } else {
            updateToast(bulkActionsToastID, { body: toastBody });
        }

        if (!toastBody && bulkActionsToastID) {
            removeToast(bulkActionsToastID);
            setToastIDsByKey((prevState) => ({ ...prevState, action: null }));
        }
    }, [toastBody]);

    return (
        <DiscussionCheckboxContext.Provider
            value={{
                checkedDiscussionIDs,
                pendingActionIDs,
                addCheckedDiscussionsByIDs,
                removeCheckedDiscussionsByIDs,
                addPendingDiscussionByIDs,
                removePendingDiscussionByIDs,
            }}
        >
            <BulkActionsManager action={bulkAction} setAction={setBulkAction} />
            {children}
        </DiscussionCheckboxContext.Provider>
    );
}
