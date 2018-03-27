/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import * as PropTypes from "prop-types";
import uniqueId from "lodash/uniqueId";

export const editorContextTypes = {
    quill: PropTypes.object,
    editorID: PropTypes.string,
};

export default class EditorProvider extends React.PureComponent {

    static propTypes = {
        quill: PropTypes.object.isRequired,
        children: PropTypes.element,
    };

    static childContextTypes = editorContextTypes;

    getChildContext() {
        return {
            quill: this.props.quill,
            editorID: uniqueId(),
        };
    }

    render() {
        return <div>{this.props.children}</div>;
    }
}

/**
 * Map a quill context to props.
 *
 * @param {React.Component} Component - The component to map.
 *
 * @returns {ComponentWithEditor} - A component with a quill context injected as props.
 */
export function withEditor(Component) {
    class ComponentWithEditor extends React.PureComponent {

        static contextTypes = editorContextTypes;

        render() {
            return <Component { ...this.context } { ...this.props } />;
        }
    }

    return ComponentWithEditor;
}
