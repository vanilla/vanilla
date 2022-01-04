/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import Translate from "@library/content/Translate";
import LayoutErrorBoundary from "@library/features/Layout/LayoutErrorBoundary";
import { ErrorIcon } from "@library/icons/common";
import Message from "@library/messages/Message";
import { t } from "@library/utility/appUtils";
import { getComponent, IRegisteredComponent } from "@library/utility/componentRegistry";
import { logDebug, RecordID } from "@vanilla/utils";
import React from "react";

export interface IDynamicComponent {
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
    return (
        <>
            {layout.map((componentConfig, index) => {
                return resolveDynamicComponent(componentConfig, index);
            })}
        </>
    );
}

/**
 * This function will turn a component configuration into its matching registered react component
 */
function resolveDynamicComponent(componentConfig: IDynamicComponent, key?: RecordID): React.ReactNode {
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

    // Return an error boundary wrapped component
    if (registeredComponent) {
        return React.createElement(LayoutErrorBoundary, { key: (componentConfig.$reactProps?.key || key) ?? null }, [
            React.createElement(registeredComponent.Component, {
                ...resolveNestedComponents(componentConfig.$reactProps),
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
function resolveNestedComponents(componentProps: IDynamicComponent["$reactProps"], key?: RecordID) {
    if (componentProps !== null && typeof componentProps === "object") {
        // If props is not an object and we know its not a config
        if (!Array.isArray(componentProps) && !isComponentConfig(componentProps)) {
            // Loop through each value of the object and resolve it
            return Object.fromEntries(
                Object.keys(componentProps).map((key, index) => {
                    return [key, resolveNestedComponents(componentProps[key], index)];
                }),
            );
        }
        // If props is an array, then resolve each array entry
        if (Array.isArray(componentProps)) {
            return componentProps.map((item, index) => resolveNestedComponents(item, index));
        }
        // If props is a config, resolve the component
        if (isComponentConfig(componentProps)) {
            // This return could be null, if it is, we should return the original object instead of null
            return (
                resolveDynamicComponent(componentProps, key) ??
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

interface ILayoutErrorProps {
    componentName: string;
}

function LayoutError(props: ILayoutErrorProps) {
    return (
        <>
            <div style={{ width: "100%", height: "100%", padding: "16px" }}>
                <Message
                    type={"error"}
                    icon={<ErrorIcon />}
                    stringContents={`There was a problem loading "${
                        props.componentName ?? t("Invalid component name")
                    }"."`}
                />
            </div>
        </>
    );
}
