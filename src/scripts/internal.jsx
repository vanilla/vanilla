/**
 * Internal functions that should not be used outside of core.
 *
 * @module internal
 */

import React from 'react';
import ReactDOM from 'react-dom';
import {getComponent, componentExists, addComponent} from "@core/application";
import {logError} from "@core/utility";
import App from "@core/Main/App";

/**
 * Mount all declared components on the dom.
 *
 * The page signifies that an element contains a component with the `data-react="<Component>"` attribute.
 *
 * @param {Element} parent The parent element to search. This element is not included in the search.
 */
export function _mountComponents(parent) {
    if (!componentExists('App')) {
        addComponent('App', App);
    }

    const nodes = parent.querySelectorAll('[data-react]');

    Array.prototype.forEach.call(nodes, (node) => {
        const name = node.getAttribute('data-react');
        const Component = getComponent(name);

        if (Component) {
            ReactDOM.render(<Component />, node);
        } else {
            logError("Could not find component %s.", name);
        }
    });
}
