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
import { usePreviewDataSchema } from "@dashboard/appearance/fragmentEditor/FragmentsApi.hooks";
import { DashboardSchemaForm } from "@dashboard/forms/DashboardSchemaForm";
import { useLayoutCatalog } from "@dashboard/layout/layoutSettings/LayoutSettings.hooks";
import { css } from "@emotion/css";
import { SchemaFormBuilder } from "@library/json-schema-forms";
import MonacoEditor from "@library/textEditor/MonacoEditor";
import type { IFragmentPreviewData } from "@library/utility/fragmentsRegistry";

interface IProps {
    previewData: IFragmentPreviewData;
    onChange: (previewData: Partial<IFragmentPreviewData>) => void;
}

export function FragmentEditorPreviewData(props: IProps) {
    const { editorTheme, editorOptions, form, settings } = useFragmentEditor();

    const field = useFragmentTabFormField(FragmentEditorEditorTabID.Preview(props.previewData));
    const ownSchema = form.customSchema;
    const serverSchema = usePreviewDataSchema(form.fragmentType);
    const schema = ownSchema && form.fragmentType === "CustomFragment" ? ownSchema : serverSchema;

    const { previewData, onChange } = props;
    const editorClasses = fragmentEditorClasses();

    return (
        <div className={classes.root}>
            <div className={classes.formContainer}>
                <DashboardSchemaForm
                    instance={previewData}
                    onChange={(newVal) => {
                        onChange(newVal(previewData));
                    }}
                    schema={SchemaFormBuilder.create()
                        .textBox("name", "Preview Name", "Set a descriptive name for this set of preview data.")
                        .textArea(
                            "description",
                            "Preview Description",
                            "Set a description for this set of preview data.",
                        )
                        .getSchema()}
                />
            </div>
            <MonacoEditor
                onValidate={field.onMonacoValidate}
                jsonSchema={schema}
                language={"json"}
                className={editorClasses.textEditor}
                theme={editorTheme}
                value={JSON.stringify(previewData.data, null, 4)}
                onChange={(newVal) => {
                    try {
                        onChange({ data: JSON.parse(newVal ?? "{}") });
                    } catch (err) {
                        // Ignore JSON parse errors, user is probably still typing.
                    }
                }}
                editorOptions={editorOptions}
            />
        </div>
    );
}

const classes = {
    root: css({
        display: "flex",
        flexDirection: "column",
        height: "100%",
    }),
    formContainer: css({
        padding: "12px  28px",
    }),
};
