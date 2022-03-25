/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import { layoutEditorContextProvider } from "@dashboard/appearance/components/LayoutEditorContextProvider";
import LayoutErrorBoundary, { LayoutError } from "@library/features/Layout/LayoutErrorBoundary";
import { Devices, useDevice } from "@library/layout/DeviceContext";
import { SectionBehaviourContext } from "@library/layout/SectionBehaviourContext";
import { WidgetLayout } from "@library/layout/WidgetLayout";
import { getComponent, IRegisteredComponent } from "@library/utility/componentRegistry";
import { logDebug } from "@vanilla/utils";
import React, { useContext } from "react";

export type IComponentFetcher = (name: string) => IRegisteredComponent | null;
export type FallbackLayoutWidget = React.ComponentType<IDynamicComponent>;

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

interface IProps extends ILayout {
    applyContexts?: boolean;
    layoutRef?: React.Ref<HTMLDivElement>;
    onKeyDown?: (e) => void;
    fallbackWidget?: FallbackLayoutWidget;
    componentFetcher?: IComponentFetcher;
    editorDecorator?: (i) => JSX.Element;
}

/**
 * This component will render all registered components from the schema passed in the layout prop
 */
export function Layout(props: IProps): React.ReactElement {
    let content = (
        <div ref={props.layoutRef}>
            <LayoutImpl {...props} />
        </div>
    );
    if (props.applyContexts ?? true) {
        content = (
            <SectionBehaviourContext.Provider value={{ autoWrap: true, useMinHeight: false }}>
                <WidgetLayout>{content}</WidgetLayout>
            </SectionBehaviourContext.Provider>
        );
    }
    return <LayoutErrorBoundary>{content}</LayoutErrorBoundary>;
}

function LayoutImpl(props: IProps) {
    const { layout, editorDecorator } = props;
    const device = useDevice();
    const layoutDevice = [Devices.XS, Devices.MOBILE].includes(device) ? LayoutDevice.MOBILE : LayoutDevice.DESKTOP;
    const layoutRenderContext: ILayoutRenderContext = {
        device: layoutDevice,
        componentFetcher: props.componentFetcher,
        fallbackWidget: props.fallbackWidget,
    };
    const { isEditMode } = useContext(layoutEditorContextProvider);

    if (isEditMode && editorDecorator && layout.length === 0) {
        return editorDecorator(0);
    }

    return (
        <>
            {layout.map((componentConfig, index) => {
                return (
                    <React.Fragment key={index}>
                        {isEditMode && editorDecorator && editorDecorator(index)}
                        {resolveDynamicComponent(componentConfig, layoutRenderContext, index)}
                        {isEditMode && index === layout.length - 1 && editorDecorator && editorDecorator(layout.length)}
                    </React.Fragment>
                );
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
    fallbackWidget?: React.ComponentType<any>;
    componentFetcher?: (name: string) => IRegisteredComponent | null;
}

/**
 * This function will turn a component configuration into its matching registered react component
 */
function resolveDynamicComponent(
    componentConfig: IDynamicComponent,
    context: ILayoutRenderContext,
    reactKey?: React.Key,
): React.ReactNode {
    const componentFetcher = context.componentFetcher ?? getComponent;
    // Get the component from the registry
    const registeredComponent: IRegisteredComponent | null =
        componentFetcher(componentConfig?.$reactComponent ?? "") ?? null;

    // If the component is not found, warn the developer
    !registeredComponent &&
        logDebug(
            `"${
                (componentConfig && componentConfig?.$reactComponent) ?? componentConfig
            }" cannot be found in the component registry`,
        );

    const key = (componentConfig?.$reactProps?.key || reactKey) ?? null;

    // Backend middleware allows specifying this device property on all nodes.
    const componentDevice = componentConfig?.$middleware?.visibility?.device;
    if (componentDevice && componentDevice !== LayoutDevice.ALL && componentDevice !== context.device) {
        // A specific device was specified for the component.
        // It was not "all".
        // That device is different than the current device we determined during rendering.
        // Don't render the component.
        return <React.Fragment />;
    }

    // Return an error boundary wrapped component
    if (registeredComponent) {
        return React.createElement(LayoutErrorBoundary, { key }, [
            React.createElement(registeredComponent.Component, {
                ...resolveNestedComponents(componentConfig.$reactProps, context),
                key: key,
            }),
        ]);
    }

    if (context.fallbackWidget) {
        return React.createElement(
            context.fallbackWidget,
            { ...(componentConfig ?? {}), ...(componentConfig.$reactProps ?? {}), key },
            [],
        );
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
    key?: React.Key,
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
