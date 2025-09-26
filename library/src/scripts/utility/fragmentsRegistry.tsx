/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import * as React from "react";

import { formatUrl, getMeta, siteUrl } from "@library/utility/appUtils";
import { logError, resolveImports, resolveObject, uuidv4 } from "@vanilla/utils";

import type { TypeDefinitions } from "@library/textEditor/MonacoUtils";
export interface IFragmentPreviewData<T extends Record<string, any> = Record<string, any>> {
    name: string;
    description?: string;
    previewDataUUID: string;
    data: T;
    previewProps?: Record<string, any>;
}

export interface IFragmentDefinition {
    fragmentType: string;
    templateTsx: () => Promise<string>;
    templateCss?: () => Promise<string | null>;
    previewData?: () => Promise<IFragmentPreviewData[]>;
    previewWrapper?: () => Promise<React.ComponentType<any>>;
    docs?: () => Promise<string | null>;
}

export interface ILoadedFragmentDefinition {
    fragmentType: string;
    templateTsx: string;
    templateCss: string | null;
    docs: string | null;
    previewData: IFragmentPreviewData[];
    previewWrapper: React.ComponentType<any> | null;
}

export interface IRegisteredInjectable {
    injectableID: string;
    module: () => Promise<any>;
}

const registeredFragments: Record<string, IFragmentDefinition> = {};
const registeredInjectables: Record<string, IRegisteredInjectable> = {};

// TODO: plugin specific registration or filtering
// We can either glob look inside the plugins/applications directory then do some filtering by enabled application after the fact or have registration per plugin.

const injectables = import.meta.glob<any>(["../widget-fragments/*.injectable.ts*"], {
    // Still need to see if it's possible to make this an async import.
    eager: true,
});

const fragmentTsxTemplates = import.meta.glob<string>(["../widget-fragments/*.template.tsx"], {
    query: "?raw",
    import: "default",
});

const fragmentCssTemplates = import.meta.glob<string>(["../widget-fragments/*.template.css"], {
    query: "?raw",
    import: "default",
});

const fragmentPreviewDatas = import.meta.glob<IFragmentPreviewData[]>(["../widget-fragments/*.previewData.ts*"], {
    import: "default",
});

const fragmentDocs = import.meta.glob<string>(["../widget-fragments/*.docs.md"], {
    query: "?raw",
    import: "default",
});

const previewWrappers = import.meta.glob<React.ComponentType<any>>(["../widget-fragments/*.preview.tsx"], {
    import: "default",
});

for (const [importPath, injectable] of Object.entries(injectables)) {
    const injectableID = basename(importPath).replace(/\.injectable\.tsx?$/, "");
    registeredInjectables[`@vanilla/injectables/${injectableID}`] = { module: injectable, injectableID };
}

for (const [importPath, tsxTemplate] of Object.entries(fragmentTsxTemplates)) {
    const fragmentType = basename(importPath).replace(/\.template\.tsx$/, "");
    const cssTemplate = fragmentCssTemplates[importPath.replace(/\.template\.tsx$/, ".template.css")] ?? null;
    const previewData =
        fragmentPreviewDatas[importPath.replace(/\.template\.tsx$/, ".previewData.tsx")] ??
        fragmentPreviewDatas[importPath.replace(/\.template\.tsx$/, ".previewData.ts")] ??
        (() => {
            return Promise.resolve([]);
        });

    const docs = fragmentDocs[importPath.replace(/\.template\.tsx$/, ".docs.md")] ?? null;
    const previewWrapper = previewWrappers[importPath.replace(/\.template\.tsx$/, ".preview.tsx")] ?? null;

    registeredFragments[fragmentType] = {
        fragmentType,
        templateTsx: tsxTemplate,
        templateCss: cssTemplate,
        docs: docs,
        previewData,
        previewWrapper,
    };
}

function basename(str: string): string {
    return str.split("/").pop() ?? str;
}

export function getRegisteredFragments(): Record<string, IFragmentDefinition> {
    return registeredFragments;
}
export function getRegisteredInjectables(): Record<string, IRegisteredInjectable> {
    return registeredInjectables;
}

export async function loadFragmentDefinition(definition: IFragmentDefinition): Promise<ILoadedFragmentDefinition> {
    const [templateTsx, templateCss, previewData, docs, previewWrapper] = await Promise.all([
        definition.templateTsx(),
        definition.templateCss?.() ?? Promise.resolve(null),
        definition.previewData?.() ?? Promise.resolve([]),
        definition.docs?.() ?? Promise.resolve(null),
        definition.previewWrapper?.() ?? Promise.resolve(null),
    ]);

    return {
        fragmentType: definition.fragmentType,
        templateTsx,
        templateCss,
        previewData,
        previewWrapper,
        docs,
    };
}

declare global {
    interface Window {
        VANILLA_INJECTABLES: Record<string, any>;
        VanillaReact: typeof React;
    }
}

export function injectInjectables() {
    const globalVar: Record<string, any> = {};
    for (const [injectablePath, registered] of Object.entries(getRegisteredInjectables())) {
        globalVar[registered.injectableID] = registered.module;
    }
    window.VANILLA_INJECTABLES = globalVar;
    window.VanillaReact = React;
}

export async function fetchRegisteredInjectablesTypeDefinitions(): Promise<TypeDefinitions> {
    const dtsPromises: Record<string, () => Promise<string>> = {};

    for (const [importPath, injectable] of Object.entries(getRegisteredInjectables())) {
        const cacheBuster = import.meta.hot ? uuidv4() : getMeta("context.cacheBuster");
        const dtsFilePath = siteUrl(`/dist/v2/injectables/${injectable.injectableID}.d.ts?v=${cacheBuster}`);
        dtsPromises[`/node_modules/${importPath}.d.ts`] = () =>
            fetch(dtsFilePath).then((res) =>
                res.text().catch((e) => {
                    logError(`No d.ts file found for injectable ${injectable.injectableID}.`);
                    return "";
                }),
            );
    }

    const result = await resolveImports(dtsPromises);
    return result;
}
