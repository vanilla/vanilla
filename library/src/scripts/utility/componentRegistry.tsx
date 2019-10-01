/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { ComponentClass } from "react";
import { logWarning, logError } from "@vanilla/utils";
import { mountReact } from "@vanilla/react-utils";
import { AppContext } from "@library/AppContext";

let useTheme = true;

/**
 * Disable theming for the site. This is useful for sections like the dashboard.
 */
export function disableComponentTheming() {
    useTheme = false;
}

/**
 * The currently registered Components.
 * @private
 */
const _components = {};

/**
 * Register a component in the Components registry.
 *
 * @param name The name of the component.
 * @param component The component to register.
 */
export function addComponent(name: string, component: React.ComponentType<any>) {
    _components[name.toLowerCase()] = component;
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
export function getComponent(name: string): ComponentClass | undefined {
    return _components[name.toLowerCase()];
}

/**
 * Mount all declared Components on the dom.
 *
 * The page signifies that an element contains a component with the `data-react="<Component>"` attribute.
 *
 * @param parent - The parent element to search. This element is not included in the search.
 */
export function _mountComponents(parent: Element) {
    parent.querySelectorAll("[data-react]").forEach(node => {
        if (!(node instanceof HTMLElement)) {
            logWarning("Attempting to mount a data-react component on an invalid element", node);
            return;
        }

        const name = node.getAttribute("data-react") || "";
        let props = node.getAttribute("data-props") || {};
        if (typeof props === "string") {
            props = JSON.parse(props);
        }

        const Component = getComponent(name);

        if (Component) {
            mountReact(
                <AppContext variablesOnly noTheme={!useTheme}>
                    <Component {...props} />
                </AppContext>,
                node,
                undefined,
                { overwrite: true },
            );
        } else {
            logError("Could not find component %s.", name);
        }
    });
}
