/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { fragmentEditorClasses } from "@dashboard/appearance/fragmentEditor/FragmentEditor.classes";
import {
    FragmentEditorEditorTabID,
    useFragmentEditor,
    useFragmentTabFormField,
} from "@dashboard/appearance/fragmentEditor/FragmentEditor.context";
import MonacoEditor from "@library/textEditor/MonacoEditor";

export function FragmentEditorCss() {
    const editor = useFragmentEditor();
    const field = useFragmentTabFormField(FragmentEditorEditorTabID.IndexCss);
    const classes = fragmentEditorClasses();
    return (
        <MonacoEditor
            className={classes.textEditor}
            value={field.value}
            onValidate={field.onMonacoValidate}
            onChange={(newVal) => {
                field.setValue(newVal ?? "");
            }}
            language={"css"}
            theme={editor.editorTheme}
            editorOptions={editor.editorOptions}
        />
    );
}
