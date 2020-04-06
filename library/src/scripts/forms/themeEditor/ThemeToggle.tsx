/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { FormToggle } from "@library/forms/FormToggle";
import { useThemeVariableField } from "@library/forms/themeEditor/ThemeBuilderContext";
import { useThemeBlock } from "@library/forms/themeEditor/ThemeBuilderBlock";

interface IProps {
    variableKey: string;
}

export function ThemeToggle(props: IProps) {
    const { variableKey } = props;
    const { generatedValue, setValue } = useThemeVariableField(variableKey);
    const { inputID, labelID } = useThemeBlock();

    return <FormToggle id={inputID} labelID={labelID} slim enabled={!!generatedValue} onChange={setValue} />;
}
