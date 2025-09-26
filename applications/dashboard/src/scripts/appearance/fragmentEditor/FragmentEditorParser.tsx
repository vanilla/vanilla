/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { FragmentEditorEsBuildPlugin } from "@dashboard/appearance/fragmentEditor/FragmentEditorEsBuildPlugin";
import { useCurrentUser } from "@library/features/users/userHooks";
import MeBox from "@library/headers/mebox/MeBox";
import { assetUrl } from "@library/utility/appUtils";
import { logError } from "@vanilla/utils";
import * as esbuild from "esbuild-wasm";
import { createElement, Fragment } from "react";
import prettierTypescript from "prettier/parser-typescript";
import prettierCss from "prettier/parser-postcss";
import * as prettier from "prettier/standalone";
import * as React from "react";
import type { IFragmentEditorForm } from "@dashboard/appearance/fragmentEditor/FragmentEditor.context";
declare global {
    interface Window {
        _ESBUILD_WASM_INITIALIZED_: boolean;
        _ESBUILD_WASM_PROMISE_: Promise<any>;
    }
}
export class FragmentEditorParser {
    static async prettifyForm(form: IFragmentEditorForm): Promise<IFragmentEditorForm> {
        const newForm = { ...form };
        const commonConfig = {
            printWidth: 120,
            tabWidth: 4,
            semi: true,
            trailingComma: "all",
        };
        const prettyJs = await prettier.format(form.jsRaw, {
            ...commonConfig,
            parser: "typescript",
            plugins: [prettierTypescript],
            language: "typescript",
        });
        newForm.jsRaw = prettyJs;

        const prettyCss = await prettier.format(form.css, {
            ...commonConfig,
            language: "css",
            parser: "css",
            plugins: [prettierCss],
        });
        newForm.css = prettyCss;

        return {
            ...form,
            jsRaw: prettyJs,
            css: prettyCss,
        };
    }

    static async transformJs(jsRaw: string): Promise<string> {
        if (!window._ESBUILD_WASM_INITIALIZED_) {
            if (!window._ESBUILD_WASM_PROMISE_) {
                window._ESBUILD_WASM_PROMISE_ = esbuild.initialize({
                    wasmURL: assetUrl("/dist/v2/esbuild.wasm"),
                });
            }
            try {
                await window._ESBUILD_WASM_PROMISE_;
            } catch (err) {
                throw new Error(
                    "Failed to initialize esbuild. Did you forget to run a production build to copy the .wasm chunk?",
                );
            }

            window._ESBUILD_WASM_INITIALIZED_ = true;
        }

        const buildPlugin = FragmentEditorEsBuildPlugin.init().file("entry.tsx", jsRaw).addSystemComponents().build();
        const built = await esbuild.build({
            bundle: true,
            jsx: "transform",
            jsxFactory: "window.VanillaReact.createElement",
            jsxFragment: "window.VanillaReact.Fragment",
            target: "es2020",
            format: "esm",
            loader: {
                ".tsx": "tsx",
                ".ts": "ts",
                ".js": "tsx",
                ".jsx": "tsx",
            },
            resolveExtensions: [".tsx", ".js", ".ts"],
            entryPoints: ["entry.tsx"],
            plugins: [buildPlugin],
        });
        let builtContents = built.outputFiles?.[0]?.text;

        if (!builtContents) {
            throw new Error("Failed to build javascript");
        }
        return builtContents;
    }
    static async parseJs(jsRaw: string): Promise<React.ComponentType<any>> {
        try {
            window.VanillaReact = React;

            const transformed = await FragmentEditorParser.transformJs(jsRaw);

            const evalString = `data:text/javascript;charset=utf-8,${encodeURIComponent(transformed)}`;

            const result = await import(/* @vite-ignore */ evalString);

            if (typeof result.default !== "function") {
                throw new Error("File default export was not a function.");
            }

            return result.default;
        } catch (error) {
            logError(error);
            throw error;
        }
    }
}
