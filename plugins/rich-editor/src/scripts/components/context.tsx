/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import uniqueId from "lodash/uniqueId";
import * as PropTypes from "prop-types";
import Quill from "quill/core";
import React from "react";

export const editorContextTypes = {
    quill: PropTypes.object,
    editorID: PropTypes.string,
};

interface IProps {
    quill: Quill;
}

export interface IEditorContextProps {
    quill?: Quill;
    editorID?: string;
}

export class Provider extends React.PureComponent<IProps> {
    public static childContextTypes = editorContextTypes;

    public getChildContext() {
        return {
            quill: this.props.quill,
            editorID: "richEditor" + uniqueId(),
        };
    }

    public render() {
        return <div className="editorContextProvider">{this.props.children}</div>;
    }
}

/**
 * Map a quill context to props.
 *
 * @param WrappedComponent - The component to map.
 *
 * @returns A component with a quill context injected as props.
 */
export function withEditor<T extends IEditorContextProps = IEditorContextProps>(
    WrappedComponent: React.ComponentClass<T>,
): React.ComponentClass<T> {
    function ComponentWithEditor(props, context) {
        return <WrappedComponent {...context} {...props} />;
    }
    (ComponentWithEditor as any).contextTypes = editorContextTypes;
    const displayName = WrappedComponent.displayName || WrappedComponent.name || "Component";

    // the func used to compute this HOC's displayName from the wrapped component's displayName.
    (ComponentWithEditor as any).displayName = `withEditor(${displayName})`;

    return ComponentWithEditor as any;
}
