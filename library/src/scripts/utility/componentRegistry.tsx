/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { resetThemeCache } from "@library/styles/themeCache";
import { IComponentMountOptions, IMountable, mountReact, mountReactMultiple } from "@vanilla/react-utils";
import { logDebug, logWarning } from "@vanilla/utils";
import React from "react";

let useTheme = true;

/**
 * Disable theming for the site. This is useful for sections like the dashboard.
 */
export function disableComponentTheming() {
    useTheme = false;
    resetThemeCache();
}

/**
 * Enable theming for the site. This is useful for sections like the dashboard.
 */
export function enableComponentTheming() {
    useTheme = true;
    resetThemeCache();
}

export function isComponentThemingEnabled() {
    return useTheme;
}

export interface IRegisteredComponent {
    Component: React.ComponentType<any>;
    mountOptions?: IComponentMountOptions;
}

/**
 * The currently registered Components.
 * @private
 */
const _components: {
    [key: string]: IRegisteredComponent;
} = {};

let _pageComponent: React.ComponentType<any> | null = null;
let _mountedPage = false;

/**
 * Register a component in the Components registry.
 *
 * @param name The name of the component.
 * @param component The component to register.
 */
export function addComponent(name: string, Component: React.ComponentType<any>, mountOptions?: IComponentMountOptions) {
    _components[name.toLowerCase()] = {
        Component,
        mountOptions,
    };
}

/**
 * Register a component in the Components registry.
 *
 * @param name The name of the component.
 * @param component The component to register.
 */
export function addPageComponent(Component: React.ComponentType<any>) {
    _pageComponent = Component;
}

/**
 * Test to see if a component has been registered.
 *
 * @param name The name of the component to test.
 * @returns Returns **true** if the component has been registered or **false** otherwise.
 */
export function componentExists(name: string): boolean {
    return _components[name.toLowerCase()] !== undefined;
}

/**
 * Get a component from the component registry.
 *
 * @param name The name of the component.
 * @returns Returns the component or **undefined** if there is no registered component.
 */
export function getComponent(name: string): IRegisteredComponent | undefined {
    return _components[name.toLowerCase()];
}

/**
 * Mount all declared Components on the dom.
 *
 * The page signifies that an element contains a component with the `data-react="<Component>"` attribute.
 *
 * @param parent - The parent element to search. This element is not included in the search.
 */
export async function _mountComponents(parent: Element) {
    const awaiting: Array<Promise<any>> = [];
    const parentPage = parent.querySelector("#app");
    let mountables: IMountable[] = [];

    if (parentPage instanceof HTMLElement && _pageComponent !== null && !_mountedPage) {
        _mountedPage = true;
        let PageComponentToMount = _pageComponent;
        mountables.push({
            target: parentPage,
            component: <PageComponentToMount />,
        });
    }

    let elementsToUnhide: Element[] = [];

    parent.querySelectorAll("[data-react]").forEach((node) => {
        if (!(node instanceof HTMLElement)) {
            logWarning("Attempting to mount a data-react component on an invalid element", node);
            return;
        }

        const name = node.getAttribute("data-react") || "";
        let props = node.getAttribute("data-props") || {};
        if (typeof props === "string") {
            props = JSON.parse(props);
        }
        const registeredComponent = getComponent(name);

        if (!registeredComponent) {
            return;
        }
        const children = node.innerHTML;
        node.innerHTML = "";

        node.removeAttribute("data-react");
        node.removeAttribute("data-props");

        if (node.getAttribute("data-unhide") === "true") {
            elementsToUnhide.push(node);
        }

        const reactNode = <registeredComponent.Component {...props} contents={children} />;

        if (registeredComponent.mountOptions?.bypassPortalManager) {
            awaiting.push(
                new Promise<void>((resolve) => {
                    mountReact(reactNode, node, () => resolve(), registeredComponent.mountOptions);
                }),
            );
        } else {
            mountables.push({
                component: reactNode,
                target: node,
                overwrite: registeredComponent.mountOptions?.overwrite ?? false,
            });
        }
    });

    awaiting.push(
        new Promise<void>((resolve) => {
            mountReactMultiple(mountables, () => {
                elementsToUnhide.forEach((element) => {
                    element.removeAttribute("style");
                });
                resolve();
            });
        }),
    );

    await Promise.all(awaiting);
}
