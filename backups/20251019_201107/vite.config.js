import laravel from "laravel-vite-plugin";
import { defineConfig } from "vite";
import { VitePWA } from "vite-plugin-pwa";
import { visualizer } from "rollup-plugin-visualizer";
import { analyzer } from "vite-bundle-analyzer";
const ANALYZE = process.env.ANALYZE === "true";

export default defineConfig({
    plugins: [
        laravel({
            input: ["resources/css/app.css", "resources/js/app.js"],
            refresh: true,
        }),
        VitePWA({
            registerType: "autoUpdate",
            includeAssets: ["favicon.ico", "robots.txt", "apple-touch-icon.png"],
            manifest: {
                name: "COPRRA",
                short_name: "COPRRA",
                description: "COPRRA Progressive Web App",
                theme_color: "#ffffff",
                background_color: "#ffffff",
                display: "standalone",
                scope: "/",
                start_url: "/",
                icons: [
                    { src: "/icons/icon-192x192.png", sizes: "192x192", type: "image/png" },
                    { src: "/icons/icon-512x512.png", sizes: "512x512", type: "image/png" },
                    { src: "/icons/icon-512x512.png", sizes: "512x512", type: "image/png", purpose: "any maskable" },
                ],
            },
            workbox: {
                clientsClaim: true,
                skipWaiting: true,
                cleanupOutdatedCaches: true,
                globPatterns: ["**/*.{js,css,html,svg,png,jpg,jpeg,webp}"]
            },
            devOptions: {
                enabled: true
            }
        }),
        // Enable Vite Bundle Analyzer in static mode without opening a browser
        analyzer({ analyzerMode: "static", openAnalyzer: false, fileName: "analyzer", defaultSizes: "gzip", summary: true, enabled: ANALYZE }),
    ],

    // Build optimization
    build: {
        // Output directory for production build
        outDir: "public/build",
        emptyOutDir: true,
        // Enable source maps for debugging (disable in production)
        sourcemap: ANALYZE,

        // Minification settings
        minify: "terser",
        terserOptions: {
            compress: {
                drop_console: true,
                drop_debugger: true,
                pure_funcs: ["console.log", "console.info", "console.debug"],
            },
            mangle: {
                safari10: true,
            },
        },

        // Asset handling
        assetsInlineLimit: 4096,

        // Chunk size warnings
        chunkSizeWarningLimit: 1000,

        // Code splitting for better caching
        rollupOptions: {
            output: {
                // Group commonly used dependencies into a single vendor chunk only when used
                manualChunks: (id) => {
                    if (id.includes("node_modules")) {
                        if (/axios|alpinejs|lodash/.test(id)) {
                            return "vendor";
                        }
                    }
                    // Let Rollup decide for other modules to avoid empty chunks
                },
            },
            // Add bundle analyzer (visualizer) only when ANALYZE is true
            plugins: [
                ...(ANALYZE
                    ? [
                        visualizer({
                            filename: "public/build/stats.html",
                            template: "treemap",
                            gzipSize: true,
                            brotliSize: true,
                        }),
                    ]
                    : []
                ),
            ],
        },
    },

    // Optimize dependencies
    optimizeDeps: {
        include: ["axios", "alpinejs", "lodash"],
    },

    // Development server
    server: {
        host: "0.0.0.0",
        port: 5173,
        strictPort: true,
        fs: {
            strict: true,
        },
        hmr: {
            host: "localhost",
        },
    },

    // CSS handling
    css: {
        devSourcemap: true,
    },

    // Resolve configuration
    resolve: {
        alias: {
            "@": "/resources/js",
            "~": "/resources",
        },
    },
});
