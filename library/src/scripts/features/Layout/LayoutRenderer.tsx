/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IHydratedLayoutWidget, LayoutDevice } from "@library/features/Layout/LayoutRenderer.types";
import LayoutErrorBoundary, { LayoutError } from "@library/features/Layout/LayoutErrorBoundary";
import { Devices, useDevice } from "@library/layout/DeviceContext";
import { SectionBehaviourContext } from "@library/layout/SectionBehaviourContext";
import { WidgetLayout } from "@library/layout/WidgetLayout";
import { getComponent, IRegisteredComponent } from "@library/utility/componentRegistry";
import { logDebug, mergeAndReplaceArrays } from "@vanilla/utils";
import React, { useContext, useEffect } from "react";
import { isHydratedLayoutWidget } from "@library/features/Layout/LayoutRenderer.utils";
import { LayoutOverviewSkeleton } from "@dashboard/layout/overview/LayoutOverviewSkeleton";
import { FragmentImplContextProvider } from "@library/utility/FragmentImplContext";

export type IComponentFetcher = (name: string) => IRegisteredComponent | null;
export type FallbackLayoutWidget = React.ComponentType<IHydratedLayoutWidget>;
export type IComponentWrapper<T> = React.ComponentType<IHydratedLayoutWidget<T>["$reactProps"]>;

interface IProps<T> {
    applyContexts?: boolean;
    layout: Array<IHydratedLayoutWidget<T>>;
    contexts?: Array<IHydratedLayoutWidget<T>>;
    layoutRef?: React.Ref<HTMLDivElement>;
    allowInternalProps?: boolean;
    fallback?: React.ReactNode;
    noSuspense?: boolean; // If true, will not use suspense for loading components
}

interface ILayoutLookupContext<T> {
    fallbackWidget?: FallbackLayoutWidget;
    componentFetcher: IComponentFetcher;
    componentWrapper?: IComponentWrapper<T>;
    propEnhancer?: (hydrateKey: string) => any;
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
        <div ref={props.layoutRef} style={{ display: "contents" }}>
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

    const contexts = props.contexts ?? [];
    const componentFetcher = lookupContext.componentFetcher ?? getComponent;

    for (const context of contexts) {
        const ContextComponent = componentFetcher(context.$reactComponent);
        if (!ContextComponent) {
            continue;
        }

        content = (
            <ContextComponent.Component key={context.$reactComponent} {...context.$reactProps}>
                {content}
            </ContextComponent.Component>
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
        propEnhancer: props.propEnhancer,
    };

    return (
        <>
            {layout.map((componentConfig, index) => {
                let component = resolveDynamicComponent(componentConfig, layoutRenderContext, index);

                if (!props.noSuspense) {
                    component = (
                        <React.Suspense key={index} fallback={props.fallback ?? <LayoutOverviewSkeleton />}>
                            {component}
                        </React.Suspense>
                    );
                }

                return <React.Fragment key={index}>{component}</React.Fragment>;
            })}
        </>
    );
}

interface ILayoutRenderContext {
    device: LayoutDevice;
    fallbackWidget?: React.ComponentType<any>;
    componentFetcher?: (name: string) => IRegisteredComponent | null;
    propEnhancer?: (hydrateKey: string) => any;
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
    const hydrateKey = componentConfig.$reactProps?.$hydrate ?? "unknown";

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

    // Backend middleware allows specifying this device property on all nodes.
    const componentDevice = componentConfig?.$middleware?.visibility?.device;
    if (componentDevice && componentDevice !== LayoutDevice.ALL && componentDevice !== context.device) {
        // A specific device was specified for the component.
        // It was not "all".
        // That device is different than the current device we determined during rendering.
        // Don't render the component.
        return <React.Fragment key={reactKey} />;
    }

    let result: React.ReactNode = null;

    let componentProps = componentConfig.$reactProps ?? {};
    if (context.propEnhancer) {
        componentProps = mergeAndReplaceArrays(context.propEnhancer(hydrateKey), componentProps);
    }

    // Return an error boundary wrapped component
    if (registeredComponent) {
        result = (
            <LayoutErrorBoundary componentName={componentConfig?.$reactComponent}>
                <registeredComponent.Component {...resolveNestedComponents(componentProps, context)} />
            </LayoutErrorBoundary>
        );
    } else if (context.fallbackWidget) {
        result = <context.fallbackWidget {...(componentConfig ?? {})} {...componentProps} />;
    } else {
        result = <LayoutError componentName={componentConfig?.$reactComponent} />;
    }

    result = (
        <FragmentImplContextProvider $fragmentImpls={componentConfig.$fragmentImpls ?? {}}>
            {result}
        </FragmentImplContextProvider>
    );

    if (context.componentWrapper) {
        result = (
            <context.componentWrapper {...componentProps} {...componentProps}>
                {result}
            </context.componentWrapper>
        );
    }

    return <React.Fragment key={reactKey}>{result}</React.Fragment>;
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
                Object.keys(componentProps).map((property) => {
                    return [property, resolveNestedComponents(componentProps[property], context)];
                }),
            );
        }
        // If props is an array, then resolve each array entry
        if (Array.isArray(componentProps)) {
            return componentProps.map((item, index) => resolveNestedComponents(item, context, index));
        }
        // If props is a config, resolve the component
        if (isHydratedLayoutWidget(componentProps)) {
            // This return could be null, if it is, we should return the original object instead of null
            return (
                resolveDynamicComponent(componentProps, context, key) ?? (
                    <LayoutError componentName={componentProps.$reactComponent} key={key} />
                )
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
