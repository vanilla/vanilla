/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { themeBuilderClasses } from "@library/forms/themeEditor/ThemeBuilder.styles";
import { useUniqueID } from "@library/utility/idUtils";
import { useThrowError } from "@vanilla/react-utils";
import classNames from "classnames";
import React, { useContext, useState } from "react";
import { ThemeInfoTooltip } from "@library/forms/themeEditor/ThemeInfoTooltip";
import { GoogleFontDropdown } from "@themingapi/theme/GoogleFontDropdown";
import { t } from "@vanilla/i18n/src";
import { CustomFontURL } from "@themingapi/theme/CustomFontFamilyName";
import { FontFamilyName } from "@themingapi/theme/FontFamilyName";

interface IProps {
    label: string;
    undo?: boolean;
    children: React.ReactNode;
    inputWrapClass?: string;
    info?: React.ReactNode;
}

interface IThemeBlockContext {
    inputID: string;
    labelID: string;
}

const ThemeBlockContext = React.createContext<IThemeBlockContext | null>(null);

export function useThemeBlock(): IThemeBlockContext {
    const context = useContext(ThemeBlockContext);

    const throwError = useThrowError();
    if (context === null) {
        throwError(
            new Error(
                "Attempting to create a ThemeBuilder form component without a block. Be sure to place it in a <ThemeBuilderBlock />",
            ),
        );
    }
    return context!;
}

export function ThemeBuilderBlock(props: IProps) {
    const inputID = useUniqueID("themBlockInput");
    const labelID = useUniqueID("themeBlockLabel");
    const classes = themeBuilderClasses();
    return (
        <div className={classes.block}>
            <label htmlFor={labelID} className={classes.label}>
                {props.label}
                {props.info && <ThemeInfoTooltip label={props.info} />}
            </label>
            <span className={classNames(classes.inputWrap, props.inputWrapClass)}>
                <ThemeBlockContext.Provider value={{ inputID, labelID }}>{props.children}</ThemeBlockContext.Provider>
            </span>
        </div>
    );
}

export function ThemeBuilderFontBlock() {
    const [customFont, setCustomFont] = useState(null);

    console.log("state of ThemeFontBlock: ", customFont);

    return (
        <>
            <ThemeBuilderBlock label={t("Font")}>
                <GoogleFontDropdown setCustomFont={setCustomFont as any} setInitialValue={!!customFont} />
            </ThemeBuilderBlock>
            {customFont && (
                <>
                    <ThemeBuilderBlock label={t("Font URL")} info={undefined}>
                        <CustomFontURL />
                    </ThemeBuilderBlock>
                    <ThemeBuilderBlock label={t("Font Name")} info={undefined}>
                        <FontFamilyName />
                    </ThemeBuilderBlock>
                </>
            )}
        </>
    );
}
