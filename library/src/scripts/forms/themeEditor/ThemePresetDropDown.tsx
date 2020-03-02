/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useState } from "react";
import classNames from "classnames";
import SelectOne from "@library/forms/select/SelectOne";
import { IComboBoxOption } from "@library/features/search/SearchBar";
import { useField } from "formik";
import InputHidden from "@library/forms/themeEditor/InputHidden";
import { t } from "@vanilla/i18n/src";
import { globalVariables } from "@library/styles/globalStyleVars";
import { color } from "csx";
import { useUniqueID } from "@library/utility/idUtils";
import ThemeBuilderBlock from "@library/forms/themeEditor/ThemeBuilderBlock";
import { inputDropDownClasses } from "@library/forms/themeEditor/inputDropDownStyles";

export const ThemePresetDropDown = () => {
    const options: IComboBoxOption[] = [
        {
            label: t("Light"),
            value: "light",
            data: {
                fg: color("#555a62"),
                bg: color("#fff"),
            },
        },
        {
            label: t("Dark"),
            value: "dark",
            data: {
                fg: color("#fff"),
                bg: color("#555a62"),
            },
        },
    ];

    let defaultValue = options[0];

    const [currentOption, setCurrentOption] = useState(defaultValue);

    const fgID = "global.mainColors.fg";
    const bgID = "global.mainColors.bg";

    const [fgValue, fgValueMeta, fgValueHelpers] = useField(fgID);
    const [bgValue, bgValueMeta, bgValueHelpers] = useField(bgID);

    const onChange = (option: IComboBoxOption | undefined) => {
        if (option) {
            fgValueHelpers.setTouched(true);
            bgValueHelpers.setTouched(true);
            fgValueHelpers.setValue(option.data.fg.toHexString());
            bgValueHelpers.setValue(option.data.bg.toHexString());
            setCurrentOption(option as any);
        }
    };

    const inputID = useUniqueID("themePreset");
    const labelID = useUniqueID("themePresetLabel");

    return (
        <ThemeBuilderBlock label={t("Preset")} labelID={labelID} inputWrapClass={inputDropDownClasses().root}>
            <div className={classNames("input-wrap-right")}>
                <SelectOne
                    label={null}
                    labelID={labelID}
                    inputID={inputID}
                    options={options}
                    value={currentOption as any}
                    onChange={onChange}
                    isClearable={false}
                />
                <InputHidden variableID={fgID} value={fgValue.value} />
                <InputHidden variableID={bgID} value={bgValue.value} />
            </div>
        </ThemeBuilderBlock>
    );
};
