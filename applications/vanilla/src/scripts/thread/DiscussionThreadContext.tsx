/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React, { useContext, useState } from "react";

const DiscussionThreadContext = React.createContext<{
    page: number;
    setPage: (newPage: number | ((currentPage: number) => number)) => void;
}>({
    page: 1,
    setPage: () => {},
});

export function DiscussionThreadContextProvider(props: { initialPage: number; children: React.ReactNode }) {
    const [page, setPage] = useState(props.initialPage);

    return (
        <DiscussionThreadContext.Provider
            value={{
                page,
                setPage,
            }}
        >
            {props.children}
        </DiscussionThreadContext.Provider>
    );
}

export function useDiscussionThreadPageContext() {
    return useContext(DiscussionThreadContext);
}
