/*
 * @author Carla França <cfranca@higherlogic.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useContext } from "react";
import DiscussionCheckToast from "@library/features/discussions/DiscussionCheckToast";
import { RecordID } from "@vanilla/utils";
import { useSessionStorage } from "@vanilla/react-utils";

/**
 * Context provider to manage the state of checkboxes when
 * selecting discussions for bulk actions.
 */

interface IProps {
    children: React.ReactNode;
}
interface IDiscussionCheckboxContext {
    checkedDiscussionIDs: RecordID[];
    addCheckedDiscussionID(discussionID: RecordID): void;
    removeCheckedDisscussionID(discussionID: RecordID): void;
}

const noop = () => {};
const DiscussionCheckboxContext = React.createContext<IDiscussionCheckboxContext>({
    checkedDiscussionIDs: [],
    addCheckedDiscussionID: noop,
    removeCheckedDisscussionID: noop,
});

export function useDiscussionCheckBoxContext() {
    return useContext(DiscussionCheckboxContext);
}

export function DiscussionCheckboxProvider(props: IProps) {
    const { children } = props;

    // using hook to store ids in sessionStorage
    const [checkedDiscussionIDs, setCheckedDiscussionIDs] = useSessionStorage<RecordID[]>("checkedDiscussionsIDs", []);

    const addCheckedDiscussionID = (discussionID: RecordID) => {
        setCheckedDiscussionIDs([...checkedDiscussionIDs, discussionID]);
    };
    const removeCheckedDisscussionID = (discussionID: RecordID) => {
        setCheckedDiscussionIDs(checkedDiscussionIDs.filter((id) => id !== discussionID));
    };

    return (
        <DiscussionCheckboxContext.Provider
            value={{ checkedDiscussionIDs, addCheckedDiscussionID, removeCheckedDisscussionID }}
        >
            {checkedDiscussionIDs.length > 0 && (
                <DiscussionCheckToast discussionIDs={checkedDiscussionIDs}></DiscussionCheckToast>
            )}
            {children}
        </DiscussionCheckboxContext.Provider>
    );
}
