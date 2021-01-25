import React, { useState, useContext, useEffect } from "react";
import {
    ControlledEditor,
    ControlledEditorOnChange,
    DiffEditorDidMount,
    EditorDidMount,
    monaco,
} from "@monaco-editor/react";
import { DarkThemeIcon, LightThemeIcon } from "@library/icons/common";
import textEditorClasses from "./textEditorStyles";
import { assetUrl, getMeta, siteUrl } from "@library/utility/appUtils";
import { editor as Monaco } from "monaco-editor/esm/vs/editor/editor.api";

monaco.config({
    paths: {
        vs: assetUrl("/dist/monaco-editor-21-2/min/vs"),
    },
});
export interface ITextEditorProps {
    language: string;
    // A URI pointing to a JSON schema to validate the document with.
    jsonSchemaUri?: string;
    value?: string;
    onChange?: ControlledEditorOnChange;
    editorDidMount?: DiffEditorDidMount;
    minimal?: boolean;
    noPadding?: boolean;
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
    overviewRulerLanes: 0,
    glyphMargin: false,
    folding: false,
    lineDecorationsWidth: 12,
    lineNumbersMinChars: 3,
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
    const { language, value, onChange } = props;
    const { theme, setTheme } = useTextEditorContext();
    const [useColorChangeOverlay, setColorChangeOverlay] = useState(false);
    const [isEditorReady, setIsEditorReady] = useState(false);
    const classes = textEditorClasses();

    useJsonSchema(props.jsonSchemaUri ?? null);

    const handleEditorDidMount: EditorDidMount = (_, editor) => {
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
        <div className={classes.root(theme, props.minimal, props.noPadding)}>
            <button type="button" onClick={toggleTheme} className={classes.themeToggleIcon} disabled={!isEditorReady}>
                {themeModeButton}
            </button>
            <ControlledEditor
                theme={theme}
                language={language}
                editorDidMount={handleEditorDidMount}
                options={props.minimal ? minimalOptions : fullOptions}
                value={value}
                onChange={onChange}
                overrideServices={overrideServices}
            />
            {loadingOverlay}
        </div>
    );
}

function useJsonSchema(schemaUri: string | null) {
    useEffect(() => {
        if (!schemaUri) {
            return;
        }
        const busterUrl = schemaUri + `?h=${getMeta("context.cacheBuster")}`;
        monaco.init().then((monaco) => {
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
            fetch(busterUrl)
                .then((res) => res.json())
                .then((json) => {
                    monaco.languages.json.jsonDefaults.setDiagnosticsOptions({
                        validate: true,
                        enableSchemaRequest: true,
                        schemas: [
                            {
                                uri: busterUrl,
                                fileMatch: ["*"],
                                schema: json,
                            },
                        ],
                    });
                });
        });
    }, [schemaUri]);
}
