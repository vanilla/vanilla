/**
 * Internal functions that should not be used outside of core.
 *
 * @module internal
 */

import { addComponent, componentExists, getComponent } from "@core/application";
import * as app from "./application";
import App from "@core/Main/App";
import { logError } from "@core/utility";
import React from 'react';
import ReactDOM from 'react-dom';

/**
 * Mount all declared components on the dom.
 *
 * The page signifies that an element contains a component with the `data-react="<Component>"` attribute.
 *
 * @param parent - The parent element to search. This element is not included in the search.
 */
export function _mountComponents(parent: Element) {
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

    _mountEmbeds(parent);
}

function _mountEmbeds(parent: Element) {
    const nodes = parent.querySelectorAll('[data-embed]');

    Array.prototype.forEach.call(nodes, (node) => {
        const data = JSON.parse(node.getAttribute('data-embed'));
        const type = data.renderType || data.type;

        // Extract all other data attributes to get the data for the embed.
        const embed = getEmbed(type);

        if (embed) {
            embed(node, data);
        } else {
            logError("Could not find embed of type %s.", type);
        }
    });

}
