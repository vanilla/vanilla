/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import LayoutErrorBoundary, { LayoutError } from "@library/features/Layout/LayoutErrorBoundary";
import { Devices, useDevice } from "@library/layout/DeviceContext";
import { useSection } from "@library/layout/LayoutContext";
import { getComponent, IRegisteredComponent } from "@library/utility/componentRegistry";
import { logDebug, RecordID } from "@vanilla/utils";
import React from "react";

export interface IDynamicComponent {
    $middleware?: {
        visibility?: {
            device?: LayoutDevice;
        };
    };
    /** The look up name of the component (lowercase) */
    $reactComponent: string;
    /** Props to be passed to the component */
    $reactProps: Record<string, IDynamicComponent | IDynamicComponent[] | any>;
}

export interface ILayout {
    /** An array describing components of a layout */
    layout: IDynamicComponent[];
}

/**
 * This component will render all registered components from the schema passed in the layout prop
 */
export function Layout(props: ILayout): React.ReactElement {
    return (
        <LayoutErrorBoundary>
            <LayoutImpl {...props} />
        </LayoutErrorBoundary>
    );
}

function LayoutImpl(props: ILayout) {
    const { layout } = props;
    const device = useDevice();
    const layoutDevice = [Devices.XS, Devices.MOBILE].includes(device) ? LayoutDevice.MOBILE : LayoutDevice.DESKTOP;
    return (
        <>
            {layout.map((componentConfig, index) => {
                return resolveDynamicComponent(componentConfig, { device: layoutDevice }, index);
            })}
        </>
    );
}

export enum LayoutDevice {
    MOBILE = "mobile",
    DESKTOP = "desktop",
    ALL = "all",
}
interface ILayoutRenderContext {
    device: LayoutDevice;
}

/**
 * This function will turn a component configuration into its matching registered react component
 */
function resolveDynamicComponent(
    componentConfig: IDynamicComponent,
    context: ILayoutRenderContext,
    key?: RecordID,
): React.ReactNode {
    // Get the component from the registry
    const registeredComponent: IRegisteredComponent | null =
        getComponent(componentConfig?.$reactComponent ?? "") ?? null;

    // If the component is not found, warn the developer
    !registeredComponent &&
        logDebug(
            `"${
                (componentConfig && componentConfig?.$reactComponent) ?? componentConfig
            }" cannot be found in the component registry`,
        );

    // Backend middleware allows specifying this device property on all nodes.
    const componentDevice = componentConfig?.$middleware?.visibility?.device;
    if (componentDevice && componentDevice !== LayoutDevice.ALL && componentDevice !== context.device) {
        // A specific device was specified for the component.
        // It was not "all".
        // That device is different than the current device we determined during rendering.
        // Don't render the component.
        return <React.Fragment key={key} />;
    }

    // Return an error boundary wrapped component
    if (registeredComponent) {
        return React.createElement(LayoutErrorBoundary, { key: (componentConfig.$reactProps?.key || key) ?? null }, [
            React.createElement(registeredComponent.Component, {
                ...resolveNestedComponents(componentConfig.$reactProps, context),
                key: key,
            }),
        ]);
    }

    return React.createElement(LayoutError, { componentName: componentConfig?.$reactComponent, key });
}

/**
 * This function will resolve any nested component configurations into react components if they
 * are listed in object values or array items.
 */
function resolveNestedComponents(
    componentProps: IDynamicComponent["$reactProps"],
    context: ILayoutRenderContext,
    key?: RecordID,
) {
    if (componentProps !== null && typeof componentProps === "object") {
        // If props is not an object and we know its not a config
        if (!Array.isArray(componentProps) && !isComponentConfig(componentProps)) {
            // Loop through each value of the object and resolve it
            return Object.fromEntries(
                Object.keys(componentProps).map((key, index) => {
                    return [key, resolveNestedComponents(componentProps[key], context, index)];
                }),
            );
        }
        // If props is an array, then resolve each array entry
        if (Array.isArray(componentProps)) {
            return componentProps.map((item, index) => resolveNestedComponents(item, context, index));
        }
        // If props is a config, resolve the component
        if (isComponentConfig(componentProps)) {
            // This return could be null, if it is, we should return the original object instead of null
            return (
                resolveDynamicComponent(componentProps, context, key) ??
                React.createElement(LayoutError, { componentName: componentProps.$reactComponent, key })
            );
        }
    }
    // Otherwise return the value as is
    return componentProps;
}

/**
 * Utility function to test if a given object is indeed a DynamicComponent configuration
 * by testing that $reactComponent and $reactProps keys exist
 */
function isComponentConfig(object: { [key: string]: any }): object is IDynamicComponent {
    return object && object.hasOwnProperty("$reactComponent") && object.hasOwnProperty("$reactProps");
}
