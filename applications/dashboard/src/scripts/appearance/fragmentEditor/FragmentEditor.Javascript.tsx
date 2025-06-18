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
import { stableObjectHash } from "@vanilla/utils";
import { useEffect, useMemo } from "react";

export function FragmentEditorJavascript() {
    const editor = useFragmentEditor();
    const field = useFragmentTabFormField(FragmentEditorEditorTabID.IndexTsx);

    const classes = fragmentEditorClasses();
    const typeDefinitions = useMemo(() => {
        const result = {
            ...editor.typeDefinitions,
        };

        const tsTypes = Object.entries(editor.form.customSchema?.properties ?? {})
            .map(([key, value]) => {
                const isRequired = editor.form.customSchema?.required?.includes(key) ?? false;
                const tsType = (() => {
                    switch (value.type) {
                        case "string":
                            if (value.enum) {
                                return value.enum.map((enumValue: string) => `"${enumValue}"`).join(" | ");
                            }
                            return "string";
                        case "integer":
                            return "number";
                        case "boolean":
                            return "boolean";
                        case "array":
                            if (value.items?.enum) {
                                return `Array<${value.items.enum
                                    .map((enumValue: string) => `"${enumValue}"`)
                                    .join(" | ")}> `;
                            }
                            return `string[]`;
                        default:
                            return "any";
                    }
                })();
                return `    ${key}${isRequired ? "" : "?"}: ${tsType};`;
            })
            .join("\n");

        const definition = `
namespace CustomFragmentInjectable {
    export interface Props {
        ${tsTypes}
    }
}
const CustomFragmentInjectable = {}
export default CustomFragmentInjectable;
            `.trim();

        result["/node_modules/@vanilla/injectables/CustomFragment.d.ts"] = definition;

        return result;
    }, [
        editor.typeDefinitions,
        // Intentionally using the hash here.
        // Re-ordering of properties in the customSchema would cause the type definitions to change otherwise, resulting in a flickering of the toolbar.
        stableObjectHash(editor.form.customSchema ?? {}),
    ]);

    return (
        <MonacoEditor
            onValidate={field.onMonacoValidate}
            className={classes.textEditor}
            typescriptDefinitions={typeDefinitions}
            language={"react"}
            value={field.value}
            onChange={(newVal) => {
                field.setValue(newVal ?? "");
            }}
            theme={editor.editorTheme}
            editorOptions={editor.editorOptions}
        />
    );
}
