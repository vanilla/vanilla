/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import {
    IHydratedLayoutSpec,
    IHydratedLayoutWidget,
    LayoutDevice,
} from "@library/features/Layout/LayoutRenderer.types";
import LayoutErrorBoundary, { LayoutError } from "@library/features/Layout/LayoutErrorBoundary";
import { Devices, useDevice } from "@library/layout/DeviceContext";
import { SectionBehaviourContext } from "@library/layout/SectionBehaviourContext";
import { WidgetLayout } from "@library/layout/WidgetLayout";
import { getComponent, IRegisteredComponent } from "@library/utility/componentRegistry";
import { logDebug } from "@vanilla/utils";
import React, { useContext } from "react";
import { isHydratedLayoutWidget } from "@library/features/Layout/LayoutRenderer.utils";

export type IComponentFetcher = (name: string) => IRegisteredComponent | null;
export type FallbackLayoutWidget = React.ComponentType<IHydratedLayoutWidget>;
export type IComponentWrapper<T> = React.ComponentType<IHydratedLayoutWidget<T>["$reactProps"]>;

interface IProps<T> {
    applyContexts?: boolean;
    layout: Array<IHydratedLayoutWidget<T>>;
    layoutRef?: React.Ref<HTMLDivElement>;
    allowInternalProps?: boolean;
}

interface ILayoutLookupContext<T> {
    fallbackWidget?: FallbackLayoutWidget;
    componentFetcher: IComponentFetcher;
    componentWrapper?: IComponentWrapper<T>;
}

export const LayoutLookupContext = React.createContext<ILayoutLookupContext<any>>({
    componentFetcher: getComponent,
});

/**
 * This component will render all registered components from the schema passed in the layout prop
 */
export function LayoutRenderer<T>(props: IProps<T>): React.ReactElement {
    const parentSectionContext = useContext(SectionBehaviourContext);
    const lookupContext = useContext(LayoutLookupContext);
    let content = (
        <div ref={props.layoutRef}>
            <LayoutRendererImpl {...lookupContext} {...props} />
        </div>
    );
    if (props.applyContexts ?? true) {
        content = (
            <SectionBehaviourContext.Provider value={{ ...parentSectionContext, autoWrap: true, useMinHeight: false }}>
                <WidgetLayout>{content}</WidgetLayout>
            </SectionBehaviourContext.Provider>
        );
    }
    return <LayoutErrorBoundary componentName="Layout">{content}</LayoutErrorBoundary>;
}

function LayoutRendererImpl<T>(props: IProps<T> & ILayoutLookupContext<T>) {
    const { layout } = props;
    const device = useDevice();
    const layoutDevice = [Devices.XS, Devices.MOBILE].includes(device) ? LayoutDevice.MOBILE : LayoutDevice.DESKTOP;
    const layoutRenderContext: ILayoutRenderContext = {
        device: layoutDevice,
        componentFetcher: props.componentFetcher,
        fallbackWidget: props.fallbackWidget,
        componentWrapper: props.componentWrapper,
        allowInternalProps: props.allowInternalProps ?? false,
    };

    return (
        <>
            {layout.map((componentConfig, index) => {
                const key = `${index}-${layout.length}`;
                return (
                    <React.Fragment key={key}>
                        {resolveDynamicComponent(componentConfig, layoutRenderContext, key)}
                    </React.Fragment>
                );
            })}
        </>
    );
}

interface ILayoutRenderContext {
    device: LayoutDevice;
    fallbackWidget?: React.ComponentType<any>;
    componentFetcher?: (name: string) => IRegisteredComponent | null;
    componentWrapper?: IComponentWrapper<any>;
    allowInternalProps: boolean;
}

/**
 * This function will turn a component configuration into its matching registered react component
 */
function resolveDynamicComponent(
    componentConfig: IHydratedLayoutWidget<any> | null,
    context: ILayoutRenderContext,
    reactKey?: React.Key,
): React.ReactNode {
    if (componentConfig === null) {
        return null;
    }
    if (!context.allowInternalProps) {
        componentConfig = {
            ...componentConfig,
            $reactProps: filterInternalProps(componentConfig.$reactProps),
        };
    }

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

    const key = reactKey ?? "unknownkey";

    // Backend middleware allows specifying this device property on all nodes.
    const componentDevice = componentConfig?.$middleware?.visibility?.device;
    if (componentDevice && componentDevice !== LayoutDevice.ALL && componentDevice !== context.device) {
        // A specific device was specified for the component.
        // It was not "all".
        // That device is different than the current device we determined during rendering.
        // Don't render the component.
        return <React.Fragment />;
    }

    let result: React.ReactNode = null;
    // Return an error boundary wrapped component
    if (registeredComponent) {
        result = React.createElement(LayoutErrorBoundary, { key, componentName: componentConfig?.$reactComponent }, [
            React.createElement(registeredComponent.Component, {
                ...resolveNestedComponents(componentConfig.$reactProps, context),
                key: key,
            }),
        ]);
    } else if (context.fallbackWidget) {
        result = React.createElement(
            context.fallbackWidget,
            { ...(componentConfig ?? {}), ...(componentConfig.$reactProps ?? {}), key },
            [],
        );
    } else {
        result = React.createElement(LayoutError, { componentName: componentConfig?.$reactComponent, key });
    }

    if (context.componentWrapper) {
        result = React.createElement(
            context.componentWrapper,
            { ...(componentConfig ?? {}), ...(componentConfig.$reactProps ?? {}), key },
            [result],
        );
    }

    return result;
}

/**
 * This function will resolve any nested component configurations into react components if they
 * are listed in object values or array items.
 */
function resolveNestedComponents(
    componentProps: IHydratedLayoutWidget["$reactProps"],
    context: ILayoutRenderContext,
    key?: React.Key,
) {
    if (componentProps !== null && typeof componentProps === "object") {
        // If props is not an object and we know its not a config
        if (!Array.isArray(componentProps) && !isHydratedLayoutWidget(componentProps)) {
            // Loop through each value of the object and resolve it
            return Object.fromEntries(
                Object.keys(componentProps).map((key, index) => {
                    return [key, resolveNestedComponents(componentProps[key], context, index)];
                }),
            );
        }
        // If props is an array, then resolve each array entry
        if (Array.isArray(componentProps)) {
            return componentProps.map((item, index) =>
                resolveNestedComponents(item, context, `${index}-${componentProps.length}`),
            );
        }
        // If props is a config, resolve the component
        if (isHydratedLayoutWidget(componentProps)) {
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

function filterInternalProps(props: Record<string, any>) {
    const finalProps: Record<string, any> = {};
    for (const [key, value] of Object.entries(props)) {
        if (key.startsWith("$")) {
            continue;
        } else {
            finalProps[key] = value;
        }
    }
    return finalProps;
}
