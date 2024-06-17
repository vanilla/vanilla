/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { InputSize } from "../../types";
import { IAutoCompleteOptionProps } from "./AutoCompleteOption";

type AutoCompleteStatus = "initial" | "selected" | "suggesting";

export interface IAutoCompleteInputState {
    status: AutoCompleteStatus;
    value?: string | string[];
}

export interface IAutoCompleteContext {
    size?: InputSize;
    onClear?(): void;
    inputState: IAutoCompleteInputState;
    setInputState: React.Dispatch<React.SetStateAction<IAutoCompleteInputState>>;
    value?: string | number | string[] | number[];
    setOptions?(options: IAutoCompleteOptionProps[]);
    multiple?: boolean;
}

/** @internal */
export const AutoCompleteContext = React.createContext<IAutoCompleteContext>({
    inputState: { status: "initial", value: "" },
    setInputState: () => {},
});
