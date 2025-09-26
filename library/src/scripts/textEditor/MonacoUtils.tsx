/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import type { JsonSchema } from "@library/json-schema-forms";
import { siteUrl } from "@library/utility/appUtils";
import { loader as monaco } from "@monaco-editor/react";
import type { editor as Monaco } from "monaco-editor/esm/vs/editor/editor.api";
import { type Monaco as MonacoInstance } from "@monaco-editor/react";

export { MonacoInstance };

export function prepareMonaco() {
    if (process.env.NODE_ENV !== "test") {
        monaco.config({
            paths: {
                vs: siteUrl(`/dist/v2/monaco-editor-52-0/min/vs`),
            },
        });
    }
}

export type MonacoEditorInstance = Monaco.IStandaloneCodeEditor;
export type MonacoDiffEditorInstance = Monaco.IStandaloneDiffEditor;

export type TypeDefinitions = Record<string, string>;

export type MonacoEditorTheme = Monaco.IStandaloneThemeData;
export type MonacoEditorOptions = Monaco.IEditorConstructionOptions & { useInlayHints?: boolean };
export type MonacoError = Monaco.IMarker;

export const MonacoOptionsPreset = {
    Minimal: {
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
            snippetsPreventQuickSuggestions: false,
        },
        wordWrap: "on",
        bracketPairColorization: {
            enabled: true,
        },
    } as MonacoEditorOptions,

    Full: {
        lineNumbers: "on",
        minimap: { enabled: false },
        glyphMargin: false,
        lineDecorationsWidth: 12,
        overviewRulerLanes: 0,
        scrollbar: {
            verticalScrollbarSize: 10,
        },
        lineNumbersMinChars: 3,
        suggest: {
            showWords: false,
            showInlineDetails: true,
            preview: true,
            snippetsPreventQuickSuggestions: false,
        },
        quickSuggestions: {
            comments: "on",
            strings: "on",
            other: "on",
        },
        inlineSuggest: {
            enabled: true,
        },
        bracketPairColorization: {
            enabled: true,
        },
    } as MonacoEditorOptions,
};

export interface IMonacoCommonProps {
    language: string | "react";
    // A URI pointing to a JSON schema to validate the document with.
    jsonSchemaUri?: string;
    jsonSchema?: JsonSchema;
    typescriptDefinitions?: string | string[] | TypeDefinitions;
    className?: string;
    theme?: MonacoEditorTheme;
    editorOptions?: MonacoEditorOptions;
}
