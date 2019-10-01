/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { createRef, useContext, useEffect, useState } from "react";
import { logWarning } from "@vanilla/utils";

type FontSizeSetter = (fontSize: number | null) => void;

interface IContextParams {
    setFontSize: FontSizeSetter;
    fontSize: number | null;
}

export const FontSizeCalculatorContext = React.createContext<IContextParams>({
    setFontSize: () => {
        logWarning("'setFontSize' called, but a proper provider was not configured.");
    },
    fontSize: null,
});

export interface IWithFontSize {
    setFontSize: FontSizeSetter;
}

interface IProps {
    children: React.ReactNode;
}

/**
 * Provider for getting a calculated font size from an element.
 * This context is to center elements based on the font size of text,
 * so it stays centered on the first line.
 *
 * Returns the line height in pixels
 *
 * @see fontSize
 */
export function FontSizeCalculatorProvider(props: IProps) {
    const { children } = props;
    const [fontSize, setFontSize] = useState<number | null>(null);

    return (
        <FontSizeCalculatorContext.Provider
            value={{
                setFontSize,
                fontSize,
            }}
        >
            {children}
        </FontSizeCalculatorContext.Provider>
    );
}

export function useFontSizeCalculator() {
    return useContext(FontSizeCalculatorContext);
}
