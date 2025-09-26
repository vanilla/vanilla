/// <reference types="vite/client" />
import { resolve } from "path";
import { defineConfig } from "vite";
import react from "@vitejs/plugin-react-swc";
import dts from "vite-plugin-dts";
import { makeViteCommonConfig } from "../../build/vite.commonConfig";

// https://vitejs.dev/config/
export default defineConfig({
    plugins: [
        react({
            plugins: [
                [
                    "@swc/plugin-emotion",
                    {
                        sourceMap: false,
                        autoLabel: "always",
                        labelFormat: "[filename]-[local]",
                    },
                ],
            ],
        }),
    ],
    root: "../../",
    build: {
        emptyOutDir: true,
        outDir: resolve(__dirname, "dist"),
        lib: {
            fileName: "index",
            entry: resolve(__dirname, "index.ts"),
            formats: ["es"],
        },
        rollupOptions: {
            external: [
                "react",
                "@vanilla/utils",
                "@vanilla/dom-utils",
                "@vanilla/react-utils",
                "@vanilla/icons",
                "react-dom",
                "react/jsx-runtime",
                "@tanstack/react-query",
            ],
        },
    },
    resolve: {
        ...makeViteCommonConfig().resolve,
    },
});
