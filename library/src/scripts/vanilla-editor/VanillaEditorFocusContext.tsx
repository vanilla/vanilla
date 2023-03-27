import {
    getSelectionText,
    useEventEditorSelectors,
    useEventPlateId,
    usePlateEditorState,
} from "@udecode/plate-headless";
import { useFocusWatcher } from "@vanilla/react-utils";
import React, { useContext, useEffect, useRef, useState } from "react";

const FocusContext = React.createContext({
    isFocusWithinEditor: false,
    editorRef: React.createRef<HTMLDivElement | null>(),
});

export function VanillaEditorFocusContext(props: { children: React.ReactNode }) {
    const [isFocusWithinEditor, setIsFocusWithinEditor] = useState(false);
    const editor = usePlateEditorState(useEventPlateId());

    const ownRef = useRef<HTMLDivElement | null>(null);

    useFocusWatcher(ownRef, (isFocused) => {
        if (!isFocusWithinEditor && isFocused) {
            setIsFocusWithinEditor(true);
        } else if (isFocusWithinEditor && !isFocused) {
            setIsFocusWithinEditor(false);
        }
    });

    useEffect(() => {
        const handler = (e) => {
            if (getSelectionText(editor).length > 0) {
                e.stopPropagation();
                e.stopImmediatePropagation();
            }
        };
        ownRef.current?.addEventListener("onKeyDown", handler);

        const current = ownRef.current;

        return () => {
            current?.removeEventListener("onKeyDown", handler);
        };
    }, [editor]);

    return (
        <FocusContext.Provider
            value={{
                isFocusWithinEditor,
                editorRef: ownRef,
            }}
        >
            <div style={{ position: "relative" }} id="vanilla-editor-focus-context" ref={ownRef}>
                {props.children}
            </div>
        </FocusContext.Provider>
    );
}

export function useVanillaEditorFocus() {
    return useContext(FocusContext);
}
