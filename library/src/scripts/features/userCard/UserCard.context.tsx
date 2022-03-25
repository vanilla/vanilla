/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React, { useContext } from "react";

interface IUserCardContext {
    isOpen: boolean;
    setIsOpen: (val: boolean) => void;
    triggerRef: React.RefObject<HTMLElement>;
    contentRef: React.RefObject<HTMLElement>;
    contents: React.ReactNode;
    contentID: string;
    triggerID: string;
}

export const UserCardContext = React.createContext<IUserCardContext>({
    isOpen: false,
    setIsOpen: () => {},
    triggerRef: {
        current: null,
    },
    contentRef: {
        current: null,
    },
    contents: null,
    contentID: "",
    triggerID: "",
});

export function useUserCardContext() {
    return useContext(UserCardContext);
}
