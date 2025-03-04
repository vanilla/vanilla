import React, { useState, useContext, useEffect } from "react";
import Editor, { loader as monaco, OnChange, OnMount } from "@monaco-editor/react";
import { DarkThemeIcon, LightThemeIcon } from "@library/icons/common";
import textEditorClasses from "./textEditorStyles";
import { getMeta, siteUrl } from "@library/utility/appUtils";
import type { editor as Monaco } from "monaco-editor/esm/vs/editor/editor.api";
import { cx } from "@emotion/css";
import type { JsonSchema } from "@library/json-schema-forms";

// FIXME: [VNLA-1206] https://higherlogic.atlassian.net/browse/VNLA-1206
if (process.env.NODE_ENV !== "test") {
    monaco.config({
        paths: {
            vs: siteUrl(`/dist/v2/monaco-editor-52-0/min/vs`),
        },
    });
}
export interface ITextEditorProps {
    language: string;
    // A URI pointing to a JSON schema to validate the document with.
    jsonSchemaUri?: string;
    jsonSchema?: JsonSchema;
    typescriptDefinitions?: string;
    value?: string;
    onChange?: OnChange;
    editorDidMount?: OnMount;
    minimal?: boolean;
    noPadding?: boolean;
    className?: string;
}

type VsTheme = "vs-light" | "vs-dark";

interface IContext {
    theme: VsTheme;
    setTheme: (theme: VsTheme) => void;
}

const context = React.createContext<IContext>({
    theme: "vs-dark",
    setTheme: () => {},
});

export function TextEditorContextProvider(props: { children: React.ReactNode }) {
    const [theme, setTheme] = useState<VsTheme>("vs-light");

    return <context.Provider value={{ theme, setTheme }}>{props.children}</context.Provider>;
}

function useTextEditorContext() {
    return useContext(context);
}

const minimalOptions: Monaco.IEditorConstructionOptions = {
    lineNumbers: "on",
    minimap: { enabled: false },
    scrollbar: {
        vertical: "hidden",
        verticalScrollbarSize: 0,
    },
    scrollBeyondLastLine: false,
    overviewRulerLanes: 0,
    glyphMargin: false,
    folding: false,
    lineDecorationsWidth: 12,
    lineNumbersMinChars: 3,
    inlineSuggest: {
        enabled: true,
    },
    suggest: {
        showInlineDetails: true,
        preview: true,
    },
    wordWrap: "on",
};

const fullOptions: Monaco.IEditorConstructionOptions = {
    lineNumbers: "on",
    minimap: { enabled: false },
    glyphMargin: false,
    lineDecorationsWidth: 12,
    overviewRulerLanes: 0,
    lineNumbersMinChars: 3,
};

// Force expand suggestions open.
// https://github.com/microsoft/monaco-editor/issues/2241#issuecomment-751985364
const overrideServices = {
    storageService: {
        get() {},
        remove() {},
        getBoolean(key) {
            // Yes this makes all boolean settings "true".
            // There isn't really a good way to inherit the original values while still doing the override.
            // Better than making all booleans false?
            // We definitely want to remove this as soon as that issue is resolved.
            return true;
        },
        store() {},
        onWillSaveState() {},
        onDidChangeStorage() {},
    },
};

export default function TextEditor(props: ITextEditorProps) {
    const { language, value, onChange, jsonSchema } = props;
    const { theme, setTheme } = useTextEditorContext();
    const [useColorChangeOverlay, setColorChangeOverlay] = useState(false);
    const [isEditorReady, setIsEditorReady] = useState(false);
    const classes = textEditorClasses();

    useJsonSchema(props.jsonSchemaUri ?? null);
    useTypeDefinitions(props.typescriptDefinitions);

    useEffect(() => {
        applyJsonSchema(jsonSchema);
    }, [jsonSchema]);

    const handleEditorDidMount: OnMount = () => {
        setIsEditorReady(true);
    };

    function toggleTheme() {
        setTheme(theme === "vs-light" ? "vs-dark" : "vs-light");
        setColorChangeOverlay(true);

        setTimeout(() => {
            setColorChangeOverlay(false);
        }, 300);
    }

    const loadingOverlay = useColorChangeOverlay && <div className={classes.colorChangeOverlay(theme)}></div>;

    const themeModeButton = theme === "vs-light" ? <LightThemeIcon /> : <DarkThemeIcon />;
    return (
        <div
            onClick={(e) => {
                e.stopPropagation();
            }}
            className={cx(classes.root(theme, props.minimal, props.noPadding), props.className)}
        >
            <button type="button" onClick={toggleTheme} className={classes.themeToggleIcon} disabled={!isEditorReady}>
                {themeModeButton}
            </button>
            <Editor
                theme={theme}
                language={language}
                onMount={handleEditorDidMount}
                options={props.minimal ? minimalOptions : fullOptions}
                value={value}
                onChange={onChange}
                overrideServices={overrideServices}
            />
            {loadingOverlay}
        </div>
    );
}

function applyJsonSchema(schema: any, uri?: string): void {
    void monaco.init().then((monaco) => {
        monaco.languages.json.jsonDefaults.setModeConfiguration({
            colors: true,
            completionItems: true,
            diagnostics: true,
            documentFormattingEdits: true,
            documentRangeFormattingEdits: true,
            documentSymbols: true,
            foldingRanges: true,
            hovers: true,
            selectionRanges: true,
            tokens: true,
        });

        monaco.languages.json.jsonDefaults.setDiagnosticsOptions({
            validate: true,
            enableSchemaRequest: true,
            schemas: [
                {
                    uri: uri ?? "#",
                    fileMatch: ["*"],
                    schema: schema,
                },
            ],
        });
    });
}

function useJsonSchema(schemaUri: string | null) {
    useEffect(() => {
        if (!schemaUri) {
            return;
        }
        const url = new URL(schemaUri);
        url.searchParams.append("h", getMeta("context.cacheBuster"));
        const busterUrl = url.toString();
        void monaco.init().then((monaco) => {
            monaco.languages.json.jsonDefaults.setModeConfiguration({
                colors: true,
                completionItems: true,
                diagnostics: true,
                documentFormattingEdits: true,
                documentRangeFormattingEdits: true,
                documentSymbols: true,
                foldingRanges: true,
                hovers: true,
                selectionRanges: true,
                tokens: true,
            });
            void fetch(busterUrl)
                .then((res) => res.json())
                .then((json) => {
                    return applyJsonSchema(json, busterUrl);
                });
        });
    }, [schemaUri]);
}

function useTypeDefinitions(fileContents?: string) {
    useEffect(() => {
        if (!fileContents) {
            return;
        }

        void monaco.init().then((monaco) => {
            monaco.languages.typescript.javascriptDefaults.addExtraLib(fileContents);
        });
    }, [fileContents]);
}
