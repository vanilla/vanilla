/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { createRef, useContext, useEffect, useState } from "react";
import { logWarning } from "@library/utility/utils";
import { style } from "typestyle";
import DeviceContext, { IDeviceProps } from "@library/layout/DeviceContext";
import { Optionalize } from "@library/@types/utils";

type LineHeightSetter = (lineHeight: number) => void;

interface IContextParams {
    component: React.RefObject<HTMLHeadingElement>;
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
export function LineHeightCalculatorProvider(props: IProps) {
    const { children } = props;
    const [lineHeight, setLineHeight] = useState(null);

    return (
        <LineHeightCalculatorContext.Provider
            value={{
                setLineHeight,
                unsetLineHeight,
                lineHeight,
            }}
        >
            {children}
        </LineHeightCalculatorContext.Provider>
    );

    // /**
    //  * Reset the context state.
    //  */
    // private unsetLineHeight = () => {
    //     this.setState({
    //         lineHeight: null,
    //     });
    // };
    //
    // /**
    //  * Set the value items will be translated by.
    //  */
    // private setLineHeight: LineHeightSetter = lineHeight => {
    //     this.setState({
    //         lineHeight,
    //     });
    // };
}

export function useLineHeightCalculator() {
    return useContext(LineHeightCalculatorContext);
}

// /**
//  * HOC to inject DeviceContext as props.
//  *
//  * @param WrappedComponent - The component to wrap
//  */
// export function withLineHeightCalculator(WrappedComponent: React.ComponentType<T>) {
//     const displayName = WrappedComponent.displayName || WrappedComponent.name || "Component";
//     const ComponentWithDevice = (props: Optionalize<T, IDeviceProps>) => {
//         return (
//             <DeviceContext.Consumer>
//                 {context => {
//                     // https://github.com/Microsoft/TypeScript/issues/28938
//                     return <WrappedComponent device={context} {...props as T} />;
//                 }}
//             </DeviceContext.Consumer>
//         );
//     };
//     ComponentWithDevice.displayName = `withDevice(${displayName})`;
//     return ComponentWithDevice;
// }
