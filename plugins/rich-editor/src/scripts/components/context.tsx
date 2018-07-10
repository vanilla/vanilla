/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import Quill from "quill/core";
import React from "react";

export interface IEditorContextProps {
    quill?: Quill;
    editorID?: string;
}

const { Consumer, Provider } = React.createContext<IEditorContextProps>({});

export { Consumer as EditorConsumer, Provider as EditorProvider };

/**
 * Map a quill context to props.
 *
 * @param WrappedComponent - The component to map.
 *
 * @returns A component with a quill context injected as props.
 */
export function withEditor<T extends IEditorContextProps = IEditorContextProps>(
    WrappedComponent: React.ComponentClass<T>,
) {
    function ComponentWithEditor(props: T) {
        return (
            <Consumer>
                {context => {
                    return <WrappedComponent {...context} {...props} />;
                }}
            </Consumer>
        );
    }
    const displayName = WrappedComponent.displayName || WrappedComponent.name || "Component";

    // the func used to compute this HOC's displayName from the wrapped component's displayName.
    (ComponentWithEditor as any).displayName = `withEditor(${displayName})`;
    return ComponentWithEditor;
}
