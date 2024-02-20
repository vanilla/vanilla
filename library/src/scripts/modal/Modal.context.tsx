/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { createContext, useContext } from "react";

export interface IModalContextValue {
    isInModal: boolean;
}

export const ModalContext = createContext<IModalContextValue>({
    isInModal: false,
});

export function useIsInModal() {
    return useContext(ModalContext).isInModal;
}
