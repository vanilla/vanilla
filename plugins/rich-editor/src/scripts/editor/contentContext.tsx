/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { getMentionRange } from "@rich-editor/quill/utility";
import Quill, { RangeStatic, StringMap } from "quill/core";
import React, { useCallback, useReducer, useContext } from "react";

interface IState {
    currentSelection: RangeStatic | null;
    lastGoodSelection: RangeStatic;
    mentionSelection: RangeStatic | null;
    activeFormats: StringMap;
}

const defaultSelection = {
    index: 0,
    length: 0,
};

const initialState: IState = {
    currentSelection: defaultSelection,
    lastGoodSelection: defaultSelection,
    mentionSelection: null,
    activeFormats: {},
};

/**
 * Reducer for the content of the rich editor.
 *
 * Maintains the active selections and formats.
 * @param quill
 */
function useEditorContentReducer(quill: Quill | null) {
    const reducer = useCallback(
        (prevState: IState, action: { selection: RangeStatic | null }): IState => {
            if (!quill) {
                return prevState;
            }
            const newSelection = action.selection;
            const lastGoodSelection = newSelection !== null ? newSelection : prevState.lastGoodSelection;
            return {
                currentSelection: newSelection,
                lastGoodSelection,
                mentionSelection: getMentionRange(quill, newSelection),
                activeFormats: lastGoodSelection ? quill.getFormat(lastGoodSelection) : {},
            };
        },
        [quill],
    );

    const [state, dispatch] = useReducer(reducer, initialState);

    const updateSelection = useCallback(
        (selection: RangeStatic | null) => {
            dispatch({ selection });
        },
        [dispatch],
    );

    return {
        ...state,
        updateSelection,
    };
}

type IContextValue = ReturnType<typeof useEditorContentReducer>;

const EditorContentContext = React.createContext<IContextValue>(null as any);

export function useEditorContents() {
    return useContext(EditorContentContext);
}
export function EditorContentContextProvider(props: { children: React.ReactNode; quill: Quill | null }) {
    const value = useEditorContentReducer(props.quill);
    return <EditorContentContext.Provider value={value}>{props.children}</EditorContentContext.Provider>;
}
