/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useContext } from "react";
import { useThrowError } from "@vanilla/react-utils";
import { IFieldError } from "@library/@types/api/core";

export interface IRadioGroupContext {
    onChange?: (value: string) => void;
    value?: string;
    isInline?: boolean;
    isGrid?: boolean;
    errors?: IFieldError[];
}

export const RadioGroupContext = React.createContext<IRadioGroupContext>({});

export function useRadioGroupContext() {
    const context = useContext(RadioGroupContext);

    const throwError = useThrowError();
    if (context === null) {
        throwError(
            new Error(
                "Attempting to use a radio group without specifying a group. Be sure to wrap it in a RadioGroupContext provider",
            ),
        );
    }
    return context!;
}
