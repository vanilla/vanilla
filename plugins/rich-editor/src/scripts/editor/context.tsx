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
    setQuillInstance: (quill: Quill | null) => void;
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
