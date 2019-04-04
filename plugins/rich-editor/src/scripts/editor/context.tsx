/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useState, useContext } from "react";
import Quill, { IFormats, DeltaOperation } from "quill/core";
import { IEditorInstance, IStoreState } from "@rich-editor/@types/store";
import { Omit } from "@library/@types/utils";
import { connect } from "react-redux";
import { Devices, useDevice, IDeviceProps } from "@library/layout/DeviceContext";
import uniqueId from "lodash/uniqueId";
import { getIDForQuill } from "@rich-editor/quill/utility";

interface IEditorProps {
    isPrimaryEditor: boolean;
    isLoading?: boolean;
    onChange?: (newContent: DeltaOperation[]) => void;
    allowUpload: boolean;
    initialValue?: DeltaOperation[];
    reinitialize?: boolean;
    operationsQueue?: EditorQueueItem[];
    clearOperationsQueue?: () => void;
    legacyMode: boolean;
    children: React.ReactNode;
}

export type EditorQueueItem = DeltaOperation[] | string;

interface IContextProps extends IEditorProps {
    quill: Quill | null;
    isMobile: boolean;
    setQuillInstance: (quill: Quill) => void;
    editorID: string;
    descriptionID: string;
    quillID: string;
}

interface IEditorReduxValue extends IEditorInstance {
    activeFormats: IFormats;
}

export interface IWithEditorProps extends IEditorReduxValue, IContextProps {}

export const EditorContext = React.createContext<IContextProps>({} as any);
const { Consumer, Provider } = EditorContext;

export { Consumer as EditorConsumer };

export function useEditor() {
    const editorContext = useContext(EditorContext);
    return editorContext;
}

export const Editor = (props: IEditorProps) => {
    const [quill, setQuillInstance] = useState<Quill | null>(null);
    const quillID = quill ? getIDForQuill(quill) : null;
    const device = useDevice();
    const isMobile = device === Devices.MOBILE;
    const ID = uniqueId("editor");
    const descriptionID = ID + "-description";

    return (
        <Provider
            value={{
                ...props,
                quill,
                setQuillInstance,
                isMobile,
                editorID: ID,
                descriptionID,
                quillID,
            }}
        >
            {props.children}
        </Provider>
    );
};

/**
 * Map in the instance state of the current editor.
 */
function mapStateToProps(state: IStoreState, ownProps: IContextProps): IEditorReduxValue {
    const { quillID, quill } = ownProps;
    if (quill) {
        const instanceState = state.editor.instances[quillID];
        const { lastGoodSelection } = instanceState;
        const activeFormats = lastGoodSelection && quill ? quill.getFormat(lastGoodSelection) : {};
        return {
            ...instanceState,
            activeFormats,
        };
    } else {
        return {
            activeFormats: {},
            currentSelection: null,
            lastGoodSelection: { index: 0, length: 0 },
            mentionSelection: null,
        };
    }
}
const withRedux = connect(mapStateToProps);

/**
 * Map a quill context to props.
 *
 * @param WrappedComponent - The component to map.
 *
 * @returns A component with a quill context injected as props.
 */
export function withEditor<T extends IWithEditorProps = IWithEditorProps>(WrappedComponent: React.ComponentType<T>) {
    const ReduxedComponent = withRedux(WrappedComponent as any);
    type Omitted = Omit<T, keyof IWithEditorProps>;
    function ComponentWithEditor(props: Omitted) {
        const context = useEditor();
        return <ReduxedComponent {...context} {...props as T} />;
    }

    ComponentWithEditor.displayName = WrappedComponent.displayName || WrappedComponent.name || "Component";
    return ComponentWithEditor as React.ComponentType<Omitted>;
}
