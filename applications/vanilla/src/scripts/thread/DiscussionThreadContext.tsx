/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import type { IComment } from "@dashboard/@types/api/comment";
import { IDiscussion } from "@dashboard/@types/api/discussion";
import React, { useContext } from "react";

export type ThreadItemActionsComponent = React.ComponentType<{
    comment: IComment;
    discussion: IDiscussion;
    onMutateSuccess?: () => Promise<void>;
}>;

type IContext = {
    discussion: IDiscussion;
    ThreadItemActionsComponent?: ThreadItemActionsComponent;
};

const DiscussionThreadContext = React.createContext<IContext>({
    discussion: {} as any,
    ThreadItemActionsComponent: undefined,
});

export function DiscussionThreadContextProvider(props: React.PropsWithChildren<IContext>) {
    const { discussion, ThreadItemActionsComponent, children } = props;

    return (
        <DiscussionThreadContext.Provider
            value={{
                discussion,
                ThreadItemActionsComponent,
            }}
        >
            {children}
        </DiscussionThreadContext.Provider>
    );
}

export function useDiscussionThreadContext() {
    return useContext(DiscussionThreadContext);
}
