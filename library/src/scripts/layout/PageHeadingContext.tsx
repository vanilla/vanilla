/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useContext } from "react";
import { logWarning } from "@library/utility/utils";
import { style } from "typestyle";

type LineHeightSetter = (lineHeight: number) => void;

interface IContextParams {
    setLineHeight: LineHeightSetter;
    unsetLineHeight: () => void;
    lineHeight: number | null;
}

export const LineHeightCalculatorContext = React.createContext<IContextParams>({
    setLineHeight: () => {
        logWarning("'setLineHeight' called, but a proper provider was not configured.");
    },
    unsetLineHeight: () => {
        logWarning("'unsetLineHeight' called, but a proper provider was not configured.");
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
 * This wraps `LineHeight.Provider` with some good default behaviour.
 *
 * Using this, you can have one component declare an offset value.
 * Other components can then receive this value through context.
 * The context itself handles watching the scroll position and provides a CSS Class styling for the offset.
 *
 * Returns the line height in pixels
 * @see lineHeight
 */
export class LineHeightCalculatorProvider extends React.Component<IProps, IState> {
    public state: IState = {
        lineHeight: null,
    };

    /**
     * @inheritdoc
     */
    public render() {
        // Generate a CSS based on our calculated values.
        const { lineHeight } = this.state;

        // Render out the context with all values and methods.
        return (
            <LineHeightCalculatorContext.Provider
                value={{
                    setLineHeight: this.setLineHeight,
                    unsetLineHeight: this.unsetLineHeight,
                    lineHeight,
                }}
            >
                {this.props.children}
            </LineHeightCalculatorContext.Provider>
        );
    }

    /**
     * Reset the context state.
     */
    private unsetLineHeight = () => {
        this.setState({
            lineHeight: null,
        });
    };

    /**
     * Set the value items will be translated by.
     */
    private setLineHeight: LineHeightSetter = lineHeight => {
        this.setState({
            lineHeight,
        });
    };
}

export function useLineHeightCalculator() {
    return useContext(LineHeightCalculatorContext);
}
