/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { useDebouncedInput } from "@dashboard/hooks";
import { css, cx } from "@emotion/css";
import {
    MonacoOptionsPreset,
    prepareMonaco,
    type IMonacoCommonProps,
    type MonacoDiffEditorInstance,
    type MonacoInstance,
} from "@library/textEditor/MonacoUtils";
import { DiffEditor, type DiffOnMount } from "@monaco-editor/react";
import { useEffect, useRef, useState } from "react";
prepareMonaco();

interface IProps extends IMonacoCommonProps {
    original: string;
    modified: string;
    hideUntilReady?: boolean;
}

export function MonacoDiffEditor(props: IProps) {
    const [isEditorReady, setIsEditorReady] = useState(false);

    const isReact = props.language === "react";
    let language = isReact ? "typescript" : props.language;

    const monacoRef = useRef<MonacoInstance | null>(null);
    const editorRef = useRef<MonacoDiffEditorInstance | null>(null);

    const handleEditorDidMount: DiffOnMount = async (editor, monaco) => {
        monacoRef.current = monaco;
        editorRef.current = editor;

        // https://github.com/microsoft/monaco-editor/issues/4448
        // Setting the option directly doesn't work.
        editor.getOriginalEditor().updateOptions({
            glyphMargin: false,
        });

        editor.updateOptions({ glyphMargin: false });

        setIsEditorReady(true);
    };
    const debouncedIsReady = useDebouncedInput(isEditorReady, 100);

    useEffect(() => {
        if (isEditorReady && props.theme) {
            monacoRef.current?.editor?.defineTheme?.("customTheme", props.theme);
            monacoRef.current?.editor?.setTheme?.("customTheme");
        }
    }, [isEditorReady, props.theme]);

    return (
        <DiffEditor
            className={cx(props.hideUntilReady && !debouncedIsReady && classes.hidden, props.className)}
            onMount={handleEditorDidMount}
            originalModelPath={isReact ? "original.tsx" : undefined}
            modifiedModelPath={isReact ? "modified.tsx" : undefined}
            theme={props.theme ? "customTheme" : undefined}
            language={language}
            options={{
                ...MonacoOptionsPreset.Full,
                renderSideBySide: false,
                scrollBeyondLastLine: false,
                hideUnchangedRegions: {
                    enabled: true,
                },
                renderWhitespace: "all",
                renderOverviewRuler: false,
                renderGutterMenu: false,
                enableSplitViewResizing: false,
                renderMarginRevertIcon: false,
                glyphMargin: false,
                automaticLayout: true,
                roundedSelection: false,
                readOnly: true,
            }}
            original={props.original}
            modified={props.modified}
        />
    );
}

const classes = {
    hidden: css({ opacity: 0 }),
};
