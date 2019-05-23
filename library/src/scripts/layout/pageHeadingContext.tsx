/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { createRef, useContext, useEffect, useState } from "react";
import { logWarning } from "@library/utility/utils";
import { style } from "typestyle";
import DeviceContext, { IDeviceProps } from "@library/layout/DeviceContext";
import { Optionalize } from "@library/@types/utils";

type LineHeightSetter = (lineHeight: number | null) => void;

interface IContextParams {
    setLineHeight: LineHeightSetter;
    lineHeight: number | null;
}

export const LineHeightCalculatorContext = React.createContext<IContextParams>({
    setLineHeight: () => {
        logWarning("'setLineHeight' called, but a proper provider was not configured.");
    },
    lineHeight: null,
});

export interface IWithLineHeight {
    setLineHeight: LineHeightSetter;
}

interface IProps {
    children: React.ReactNode;
}

interface IState {
    lineHeight: number | null;
}

/**
 * Provider for getting a calculated line height from an element.
 * This context is to center elements based on the line height of text, so it stays centered on the first line
 *
 * Returns the line height in pixels
 * @see lineHeight
 */
export function LineHeightCalculatorProvider(props: IProps) {
    const { children } = props;
    const [lineHeight, setLineHeight] = useState<number | null>(null);

    return (
        <LineHeightCalculatorContext.Provider
            value={{
                setLineHeight,
                lineHeight,
            }}
        >
            {children}
        </LineHeightCalculatorContext.Provider>
    );
}

export function useLineHeightCalculator() {
    return useContext(LineHeightCalculatorContext);
}
