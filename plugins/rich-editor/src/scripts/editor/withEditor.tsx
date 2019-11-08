/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import React from "react";
import { IWithEditorProps, useEditor } from "@rich-editor/editor/context";
import { useEditorContents } from "@rich-editor/editor/contentContext";

/**
 * Map a quill context to props.
 *
 * @param WrappedComponent - The component to map.
 *
 * @returns A component with a quill context injected as props.
 */
export function withEditor<T extends IWithEditorProps = IWithEditorProps>(WrappedComponent: React.ComponentType<T>) {
    type Omitted = Omit<T, keyof IWithEditorProps>;
    function ComponentWithEditor(props: Omitted) {
        const context = useEditor();
        const content = useEditorContents();
        return <WrappedComponent {...context} {...content} {...props as T} />;
    }
    ComponentWithEditor.displayName = WrappedComponent.displayName || WrappedComponent.name || "Component";
    return ComponentWithEditor as React.ComponentType<Omitted>;
}
