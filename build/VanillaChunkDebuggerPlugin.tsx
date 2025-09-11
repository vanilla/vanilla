/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { Plugin, UserConfig, mergeConfig } from "vite";

/**
 * Plugin for loading tracking which scripts are loaded in a page for use with the chunk debugger.
 */
export const VanillaChunkDebuggerPlugin = async (): Promise<Plugin<any>> => {
    const plugin: Plugin = {
        name: "vite-vanilla-chunk-debugger-plugin",
        config(config, env) {
            const ownConfig: UserConfig = {
                build: {
                    rollupOptions: {
                        output: {
                            intro(chunk) {
                                return `
                                if (!Array.isArray(window.__VANILLA_CHUNK_DEBUGGER__)) {
                                    window.__VANILLA_CHUNK_DEBUGGER__ = [];
                                }

                                window.__VANILLA_CHUNK_DEBUGGER__.push("${chunk.fileName}");
                                `.trim();
                            },
                        },
                    },
                },
            };
            return mergeConfig(config, ownConfig);
        },
    };
    return plugin;
};
