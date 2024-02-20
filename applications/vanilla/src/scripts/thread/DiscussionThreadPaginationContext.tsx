/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React, { useContext, useState } from "react";

const DiscussionThreadPaginationContext = React.createContext<{
    page: number;
    setPage: (newPage: number | ((currentPage: number) => number)) => void;
}>({
    page: 1,
    setPage: () => {},
});

export function DiscussionThreadPaginationContextProvider(props: { initialPage: number; children: React.ReactNode }) {
    const [page, setPage] = useState(props.initialPage);

    return (
        <DiscussionThreadPaginationContext.Provider
            value={{
                page,
                setPage,
            }}
        >
            {props.children}
        </DiscussionThreadPaginationContext.Provider>
    );
}

export function useDiscussionThreadPaginationContext() {
    return useContext(DiscussionThreadPaginationContext);
}
