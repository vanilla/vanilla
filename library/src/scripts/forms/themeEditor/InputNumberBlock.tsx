/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useMemo } from "react";
import ThemeBuilderBlock, { IThemeBuilderBlock } from "@library/forms/themeEditor/ThemeBuilderBlock";
import { uniqueIDFromPrefix } from "@library/utility/idUtils";
import { IInputNumber } from "@library/forms/themeEditor/InputNumber";
import InputNumber from "@library/forms/themeEditor/InputNumber";
import { inputNumberClasses } from "@library/forms/themeEditor/inputNumberStyles";

export interface IPresetInputNumber extends Omit<IInputNumber, "inputID" | "labelID"> {
    defaultValue?: number;
}

export interface IPresetThemeEditorInputBlock extends Omit<IThemeBuilderBlock, "children" | "labelID" | "inputID"> {}

export interface INumberInputBlock {
    inputNumber: IPresetInputNumber;
    inputBlock: IPresetThemeEditorInputBlock;
}

export default function InputNumberBlock(props: INumberInputBlock) {
    const inputID = useMemo(() => {
        return uniqueIDFromPrefix("numberPickerInput");
    }, []);
    const labelID = useMemo(() => {
        return uniqueIDFromPrefix("numberPickerLabel");
    }, []);

    return (
        <ThemeBuilderBlock
            undo={true}
            {...{
                ...props.inputBlock,
                inputWrapClass: inputNumberClasses().inputWrap,
                inputID,
                labelID,
            }}
        >
            <InputNumber
                {...{
                    ...props.inputNumber,
                    inputID,
                    labelID,
                }}
            />
        </ThemeBuilderBlock>
    );
}
