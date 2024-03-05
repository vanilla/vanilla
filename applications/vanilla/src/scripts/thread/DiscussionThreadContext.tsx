/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IDiscussion } from "@dashboard/@types/api/discussion";
import React, { useContext } from "react";

const DiscussionThreadContext = React.createContext<{
    discussion?: IDiscussion;
}>({
    discussion: undefined,
});

export function DiscussionThreadContextProvider(props: React.PropsWithChildren<{ discussion: IDiscussion }>) {
    const { discussion, children } = props;

    return (
        <DiscussionThreadContext.Provider
            value={{
                discussion,
            }}
        >
            {children}
        </DiscussionThreadContext.Provider>
    );
}

export function useDiscussionThreadContext() {
    return useContext(DiscussionThreadContext);
}
