/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import Quill, { IFormats } from "quill/core";
import React from "react";
import { connect } from "react-redux";
import { IStoreState, IEditorInstance } from "@rich-editor/@types/store";

interface IContextProps {
    quill: Quill;
    editorID: string;
}

interface IGeneratedContextProps {
    instanceState: IEditorInstance;
    activeFormats: IFormats;
}

type Omit<T, K extends keyof T> = Pick<T, Exclude<keyof T, K>>;

export type IWithEditorProps = IContextProps & IGeneratedContextProps;

const { Consumer, Provider } = React.createContext<IContextProps>({} as any);

export { Consumer as EditorConsumer, Provider as EditorProvider };

/**
 * Map in the instance state of the current editor.
 */
function mapStateToProps(state: IStoreState, ownProps: IContextProps) {
    const { editorID, quill } = ownProps;
    const instanceState = state.editor.instances[editorID];
    const { lastGoodSelection } = instanceState;
    const activeFormats = lastGoodSelection ? quill.getFormat(lastGoodSelection) : {};
    return {
        instanceState,
        activeFormats,
    };
}
const withRedux = connect(mapStateToProps);

/**
 * Map a quill context to props.
 *
 * @param WrappedComponent - The component to map.
 *
 * @returns A component with a quill context injected as props.
 */
export function withEditor<T extends IWithEditorProps = IWithEditorProps>(
    WrappedComponent: React.ComponentClass<IWithEditorProps>,
) {
    // the func used to compute this HOC's displayName from the wrapped component's displayName.
    const ReduxComponent = withRedux(WrappedComponent);
    const displayName = WrappedComponent.displayName || WrappedComponent.name || "Component";
    class ComponentWithEditor extends React.Component<Omit<T, keyof IWithEditorProps>> {
        public static displayName = `withEditor(${displayName})`;
        public render() {
            return (
                <Consumer>
                    {context => {
                        return <ReduxComponent {...context} {...this.props} />;
                    }}
                </Consumer>
            );
        }
    }

    return ComponentWithEditor;
}
