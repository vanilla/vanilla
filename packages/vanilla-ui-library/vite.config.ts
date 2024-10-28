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
                    "@vanilla/plugin-emotion",
                    {
                        sourceMap: false,
                        autoLabel: "always",
                        labelFormat: "[filename]-[local]",
                    },
                ],
            ],
        }),
        dts({
            exclude: ["**/*.stories.tsx", "**/*.spec.ts*", resolve(__dirname, "dist/**/*")],
            rollupTypes: true,
            tsconfigPath: resolve(__dirname, "../../tsconfig.json"),
            copyDtsFiles: true,
            root: __dirname,
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
            external: ["react", "react-dom", "react/jsx-runtime", "@tanstack/react-query"],
            output: {
                globals: {
                    react: "React",
                    "react-dom": "React-dom",
                    "react/jsx-runtime": "react/jsx-runtime",
                },
            },
        },
    },
    resolve: {
        ...makeViteCommonConfig().resolve,
    },
});
