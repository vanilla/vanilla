/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IEditorInstance } from "@rich-editor/@types/store";
import Quill, { DeltaOperation, IFormats } from "quill/core";
import React, { useContext } from "react";

export interface IEditorProps {
    isPrimaryEditor: boolean;
    isLoading: boolean;
    onFocus?: (isFocused: boolean) => void;
    onChange?: (newContent: DeltaOperation[]) => void;
    allowUpload: boolean;
    initialValue?: DeltaOperation[];
    reinitialize?: boolean;
    operationsQueue?: EditorQueueItem[];
    clearOperationsQueue?: () => void;
    legacyMode: boolean;
}
export type EditorQueueItem = DeltaOperation[] | string;

interface IContextProps extends IEditorProps {
    editor: Quill | null;
    setEditorInstance: (quill: Quill | null) => void;
    isMobile: boolean;
    onFocus?: (isFocused: boolean) => void;
}

interface IEditorReduxValue extends IEditorInstance {
    activeFormats: IFormats;
}

export interface IWithEditorProps extends IEditorReduxValue, IContextProps {}

export const EditorContext = React.createContext<IContextProps>({} as any);

/**
 * Hook for using the editor context.
 */
export function useEditor() {
    const editorContext = useContext(EditorContext);
    return editorContext;
}
