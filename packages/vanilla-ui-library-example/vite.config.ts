import { defineConfig } from "vite";
import path from "path";
import react from "@vitejs/plugin-react-swc";

// https://vite.dev/config/
export default defineConfig({
    plugins: [react()],
    build: {},
    root: path.resolve(__dirname),
});
