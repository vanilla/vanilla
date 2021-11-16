/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React, { useContext } from "react";
import { FormGroup, FormGroupLabel, TextBox, AutoComplete } from "@vanilla/ui";

type CommonOmit = "value" | "onChange" | "placeholder" | "children" | "disabled";

type InputTypeProps = {
    textBox?: Omit<React.ComponentProps<typeof TextBox>, CommonOmit>;
    dropDown?: Omit<React.ComponentProps<typeof AutoComplete>, CommonOmit>;
};

interface IVanillaUIFormControlContext {
    inputTypeProps: InputTypeProps;
    commonInputProps: React.HTMLAttributes<HTMLElement>;
}

export const VanillaUIFormControlContext = React.createContext<IVanillaUIFormControlContext>({
    inputTypeProps: {},
    commonInputProps: {},
});

export function useVanillaUIFormControlContext() {
    return useContext(VanillaUIFormControlContext);
}
