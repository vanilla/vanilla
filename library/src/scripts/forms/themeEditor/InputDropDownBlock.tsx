/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useMemo } from "react";
import { IInputDropDown, InputDropDown } from "@library/forms/themeEditor/InputDropDown";
import { uniqueIDFromPrefix, useUniqueID } from "@library/utility/idUtils";
import ThemeBuilderBlock, { IThemeBuilderBlock } from "@library/forms/themeEditor/ThemeBuilderBlock";
import { inputDropDownClasses } from "@library/forms/themeEditor/inputDropDownStyles";
import { useField } from "formik";

export interface IPresetInputDropDown extends Omit<IInputDropDown, "inputID" | "labelID"> {}

export interface IPresetThemeEditorInputBlock extends Omit<IThemeBuilderBlock, "children" | "labelID" | "inputID"> {}

export interface IDropDownBlock {
    inputDropDown: IPresetInputDropDown;
    inputBlock: IPresetThemeEditorInputBlock;
}

export const InputDropDownBlock = (props: IDropDownBlock) => {
    const inputID = useUniqueID("dropDownInput");
    const labelID = useUniqueID("dropDownLabel");

    return (
        <ThemeBuilderBlock
            inputWrapClass={inputDropDownClasses().root}
            {...{
                ...props.inputBlock,
                // inputWrapClass: InputDropDownClasses().inputWrap,
                inputID,
                labelID,
            }}
        >
            <InputDropDown
                {...{
                    ...props.inputDropDown,
                    inputID,
                    labelID,
                }}
            />
        </ThemeBuilderBlock>
    );
};
