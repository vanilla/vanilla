/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { cx } from "@emotion/css";
import { DarkThemeIcon, LightThemeIcon } from "@library/icons/common";
import type { JsonSchema } from "@library/json-schema-forms";
import {
    MonacoOptionsPreset,
    prepareMonaco,
    type IMonacoCommonProps,
    type MonacoEditorInstance,
    type MonacoError,
    type TypeDefinitions,
} from "@library/textEditor/MonacoUtils";
import { getJsxTokenProvider } from "@library/textEditor/MonacoEditorJsxKludge";
import { getMeta } from "@library/utility/appUtils";
import { useUniqueID } from "@library/utility/idUtils";
import { Editor, loader as monaco, Monaco as MonacoInstance, OnChange, OnMount } from "@monaco-editor/react";
import React, { useContext, useEffect, useRef, useState } from "react";
import textEditorClasses from "./MonacoEditor.styles";

prepareMonaco();

interface ITextEditorProps extends IMonacoCommonProps {
    value?: string;
    onChange?: OnChange;
    editorDidMount?: OnMount;
    minimal?: boolean;
    noPadding?: boolean;
    onValidate?: (errors: MonacoError[]) => void;
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

export function TextEditorContextProvider(props: { children: React.ReactNode; defaultTheme?: VsTheme }) {
    const [theme, setTheme] = useState<VsTheme>(props.defaultTheme ?? "vs-light");

    return <context.Provider value={{ theme, setTheme }}>{props.children}</context.Provider>;
}

function useTextEditorContext() {
    return useContext(context);
}

export default function MonacoEditor(props: ITextEditorProps) {
    const { value, onChange, jsonSchema, editorOptions } = props;
    const { theme, setTheme } = useTextEditorContext();
    const [useColorChangeOverlay, setColorChangeOverlay] = useState(false);
    const [isEditorReady, setIsEditorReady] = useState(false);
    const classes = textEditorClasses();

    const isReact = props.language === "react";
    let language = isReact ? "typescript" : props.language;

    const monacoRef = useRef<MonacoInstance | null>(null);
    const editorRef = useRef<MonacoEditorInstance | null>(null);

    useJsonSchema(monacoRef.current, props.jsonSchemaUri ?? null);

    useEffect(() => {
        applyJsonSchema(jsonSchema);
    }, [jsonSchema]);

    const id = useUniqueID("editor");

    const handleEditorDidMount: OnMount = async (editor, monaco) => {
        monacoRef.current = monaco;
        editorRef.current = editor;

        applyTypeDefinitions(monaco, props.typescriptDefinitions, isReact);

        if (language === "typescript") {
            const model = monaco.editor.createModel(value ?? "", "typescript", monaco.Uri.file(`/${id}.tsx`));
            editor.setModel(model);
        }

        // Has to be in a timeout for some reason.
        if (isReact) {
            monaco.languages.onLanguageEncountered("typescript", () => {
                monaco.languages.setMonarchTokensProvider("typescript", getJsxTokenProvider());
            });

            // I have tried a LOT to get this to work withotu a timeout. It works with a shorter one, but I left it a little higher to be safe.
            setTimeout(() => {
                monacoRef.current?.languages.setMonarchTokensProvider("typescript", getJsxTokenProvider());
            }, 100);
        }

        setIsEditorReady(true);
    };

    useEffect(() => {
        if (monacoRef.current) {
            applyTypeDefinitions(monacoRef.current, props.typescriptDefinitions, isReact);
        }
    }, [props.typescriptDefinitions]);

    useEffect(() => {
        if (isEditorReady && props.theme) {
            monacoRef.current?.editor?.defineTheme?.("customTheme", props.theme);
            monacoRef.current?.editor?.setTheme?.("customTheme");
        }
    }, [isEditorReady, props.theme]);

    useEffect(() => {
        if (props.editorOptions?.useInlayHints) {
            monacoRef.current?.languages?.typescript?.typescriptDefaults?.setInlayHintsOptions?.({
                includeInlayParameterNameHints: "literals",
                includeInlayEnumMemberValueHints: true,
                includeInlayFunctionLikeReturnTypeHints: true,
                includeInlayPropertyDeclarationTypeHints: true,
                includeInlayVariableTypeHints: true,
                includeInlayFunctionParameterTypeHints: true,
                includeInlayParameterNameHintsWhenArgumentMatchesName: true,
            });
        } else {
            monacoRef.current?.languages?.typescript?.typescriptDefaults?.setInlayHintsOptions?.({
                includeInlayParameterNameHints: "none",
                includeInlayEnumMemberValueHints: false,
                includeInlayFunctionLikeReturnTypeHints: false,
                includeInlayPropertyDeclarationTypeHints: false,
                includeInlayVariableTypeHints: false,
                includeInlayFunctionParameterTypeHints: false,
                includeInlayParameterNameHintsWhenArgumentMatchesName: false,
            });
        }
    }, [isEditorReady, props.editorOptions?.useInlayHints]);

    function toggleTheme() {
        setTheme(theme === "vs-light" ? "vs-dark" : "vs-light");
        setColorChangeOverlay(true);

        setTimeout(() => {
            setColorChangeOverlay(false);
        }, 300);
    }

    const loadingOverlay = useColorChangeOverlay && <div className={classes.colorChangeOverlay(theme)}></div>;

    const themeModeButton = theme === "vs-light" ? <LightThemeIcon /> : <DarkThemeIcon />;

    const options = props.minimal
        ? { ...MonacoOptionsPreset.Minimal, ...editorOptions }
        : { ...MonacoOptionsPreset.Full, ...editorOptions };

    return (
        <div
            onClick={(e) => {
                e.stopPropagation();
            }}
            className={cx(classes.root(theme, props.minimal, props.noPadding), props.className)}
        >
            {!props.theme && (
                <button
                    type="button"
                    onClick={toggleTheme}
                    className={classes.themeToggleIcon}
                    disabled={!isEditorReady}
                >
                    {themeModeButton}
                </button>
            )}
            <Editor
                onValidate={props.onValidate}
                theme={props.theme ? "customTheme" : theme}
                language={language}
                onMount={handleEditorDidMount}
                options={options}
                value={value}
                onChange={onChange}
                overrideServices={overrideServices}
            />
            {loadingOverlay}
        </div>
    );
}

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

function useJsonSchema(monacoInstance: MonacoInstance | null, schemaUri: string | null) {
    useEffect(() => {
        if (!schemaUri || !monacoInstance) {
            return;
        }
        const url = new URL(schemaUri);
        url.searchParams.append("h", getMeta("context.cacheBuster"));
        const busterUrl = url.toString();

        monacoInstance.languages.json.jsonDefaults.setModeConfiguration({
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
    }, [schemaUri, monacoInstance]);
}

function applyTypeDefinitions(
    monacoInstance: MonacoInstance,
    typeDefinitions?: string | string[] | TypeDefinitions,
    isReact?: boolean,
) {
    if (isReact) {
        monacoInstance.languages.typescript.typescriptDefaults.setCompilerOptions({
            target: monacoInstance.languages.typescript.ScriptTarget.Latest,
            allowNonTsExtensions: true,
            moduleResolution: monacoInstance.languages.typescript.ModuleResolutionKind.NodeJs,
            module: monacoInstance.languages.typescript.ModuleKind.CommonJS,
            noEmit: true,
            esModuleInterop: true,
            jsx: monacoInstance.languages.typescript.JsxEmit.ReactJSX,
            allowJs: true,
        });
    }

    monacoInstance.languages.typescript.typescriptDefaults.setDiagnosticsOptions({
        noSemanticValidation: false,
        noSyntaxValidation: false,
        noSuggestionDiagnostics: false,
    });

    if (!typeDefinitions) {
        return;
    }

    type ExtraLib = {
        content: string;
        filePath?: string;
    };

    let extraLibs: ExtraLib[] = [];
    if (typeof typeDefinitions === "string") {
        extraLibs = [{ content: typeDefinitions }];
    } else if (Array.isArray(typeDefinitions)) {
        extraLibs = typeDefinitions.map((content) => ({ content }));
    } else {
        extraLibs = Object.entries(typeDefinitions).map(([filePath, content]) => ({
            content,
            filePath: `file://${filePath}`,
        }));
    }

    monacoInstance.languages.typescript.javascriptDefaults.setExtraLibs(extraLibs);
    monacoInstance.languages.typescript.typescriptDefaults.setExtraLibs(extraLibs);
}
