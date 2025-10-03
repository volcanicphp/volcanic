import { defineConfig } from "vite";
import { resolve } from "path";
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
    base: "/vendor/volcanic/",

    plugins: [tailwindcss()],

    build: {
        outDir: "resources/dist",
        emptyOutDir: true,
        manifest: true,
        rollupOptions: {
            input: {
                playground: resolve(__dirname, "resources/js/playground.js"),
                playgroundStyles: resolve(
                    __dirname,
                    "resources/css/playground.css"
                ),
            },
            output: {
                entryFileNames: "[name].js",
                chunkFileNames: "[name].js",
                assetFileNames: "[name].[ext]",
            },
        },
    },

    resolve: {
        alias: {
            "@": resolve(__dirname, "resources"),
        },
    },
});
