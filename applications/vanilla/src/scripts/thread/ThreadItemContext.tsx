/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { RecordID } from "@vanilla/utils";
import React, { useContext } from "react";

export interface IThreadItemContext {
    recordType: "discussion" | "comment";
    recordID: RecordID;
}

const ThreadItemContext = React.createContext<IThreadItemContext>({
    recordType: "discussion",
    recordID: 0,
});

export function ThreadItemContextProvider(props: { children: React.ReactNode } & IThreadItemContext) {
    const { children, ...context } = props;
    return <ThreadItemContext.Provider value={context}>{props.children}</ThreadItemContext.Provider>;
}

export function useThreadItemContext() {
    return useContext(ThreadItemContext);
}
