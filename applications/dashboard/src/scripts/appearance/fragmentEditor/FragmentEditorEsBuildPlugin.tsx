/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { useCurrentUser } from "@library/features/users/userHooks";
import MeBox from "@library/headers/mebox/MeBox";
import { getRegisteredInjectables } from "@library/utility/fragmentsRegistry";
import type * as esbuild from "esbuild-wasm";
import * as React from "react";

export class FragmentEditorEsBuildPlugin {
    private files: Record<string, string> = {};

    public static init(): FragmentEditorEsBuildPlugin {
        return new FragmentEditorEsBuildPlugin();
    }

    public file(name: string, content: string): FragmentEditorEsBuildPlugin {
        this.files[name] = content;
        return this;
    }

    public addSystemComponents(): FragmentEditorEsBuildPlugin {
        const injectables = getRegisteredInjectables();
        for (const [injectableModule, injectableDefinition] of Object.entries(injectables)) {
            this.file(
                `/vanilla/injectables/${injectableDefinition.injectableID}.tsx`,
                `
const injectable = window.VANILLA_INJECTABLES?.["${injectableDefinition.injectableID}"] ?? null;
if (!injectable) {
    console.error("Injectable ${injectableDefinition.injectableID} not found");
}
export default injectable.default ?? injectable;
                `,
            );
        }

        this.file(
            "/react.tsx",
            `
export default window.VanillaReact;

${Object.keys(React)
    .filter((reactExport) => reactExport !== "default")
    .map((reactExport) => `export const ${reactExport} = window.VanillaReact.${reactExport};`)
    .join("\n")}
`,
        );

        return this;
    }

    public build = (): esbuild.Plugin => {
        const files = this.files;
        return {
            name: "virtual",
            setup: (build: esbuild.PluginBuild): void => {
                build.onResolve({ filter: /@vanilla\/injectables\/.*/ }, (args) => {
                    const injectableID = args.path.split("/").pop();
                    return {
                        path: `/vanilla/injectables/${injectableID}.tsx`,
                        namespace: "virtual",
                    };
                });
                build.onResolve({ filter: /^react$/ }, (args) => {
                    return {
                        path: "/react.tsx",
                        namespace: "virtual",
                    };
                });
                // Intercept all import paths and resolve them to the virtual namespace
                build.onResolve({ filter: /.*/ }, (args) => {
                    return {
                        path: args.path,
                        namespace: "virtual",
                    };
                });

                // When a URL is loaded, we want to actually download the content
                // from the internet. This has just enough logic to be able to
                // handle the example import from unpkg.com but in reality this
                // would probably need to be more complex.
                build.onLoad(
                    { filter: /.*/, namespace: "virtual" },
                    (args: esbuild.OnLoadArgs): esbuild.OnLoadResult | null => {
                        const res = files[args.path] ?? null;
                        if (res != null) {
                            return {
                                contents: res,
                                loader: "tsx",
                            };
                        }

                        return null;
                    },
                );
            },
        };
    };
}
