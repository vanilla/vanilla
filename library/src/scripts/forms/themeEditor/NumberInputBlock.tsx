/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useMemo } from "react";
import ThemeBuilderBlock, { IThemeBuilderBlock } from "@library/forms/themeEditor/ThemeBuilderBlock";
import { uniqueIDFromPrefix } from "@library/utility/idUtils";
import { ColorHelper } from "csx";
import { IInputNumber } from "@library/forms/themeEditor/NumberInput";

export interface IPresetNumberInput extends Omit<Omit<IInputNumber, "inputID">, "labelID"> {
    defaultValue?: ColorHelper;
}

export interface IPresetThemeEditorInputBlock extends Omit<Omit<IThemeBuilderBlock, "children">, "labelID"> {}

export interface INumberInputBlock {
    inputNumber: IInputNumber;
    inputBlock: IPresetThemeEditorInputBlock;
}

export default function NumberInputBlock(props: INumberInputBlock) {
    const inputID = useMemo(() => {
        return uniqueIDFromPrefix("themeBuilderNumberPickerInput");
    }, []);
    const labelID = useMemo(() => {
        return uniqueIDFromPrefix("themeBuilderNumberPickerLabel");
    }, []);

    return (
        <ThemeBuilderBlock
            {...{
                ...props.inputBlock,
                inputID,
                labelID,
            }}
        >
            <NumberInputBlock
                {...{
                    ...props.inputNumber,
                    inputID,
                    labelID,
                }}
            />
        </ThemeBuilderBlock>
    );
}
