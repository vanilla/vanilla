/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { InputSize } from "../../types";
import { IAutoCompleteOptionProps } from "./AutoCompleteOption";

export type ComboboxStatus = "IDLE" | "SUGGESTING" | "NAVIGATING" | "INTERACTING"; // from reach combobox
export interface IAutoCompleteInputState {
    status: ComboboxStatus;
    value?: string;
}

export interface IAutoCompleteContext {
    size?: InputSize;
    inputState: IAutoCompleteInputState;
    value?: string | number | string[] | number[];
    setOptions: React.Dispatch<React.SetStateAction<IAutoCompleteOptionProps[]>>;
    multiple?: boolean;
}

/** @internal */
export const AutoCompleteContext = React.createContext<IAutoCompleteContext>({
    inputState: { status: "IDLE", value: "" },
    setOptions: () => {},
});

export const useAutoCompleteContext = () => React.useContext(AutoCompleteContext);
