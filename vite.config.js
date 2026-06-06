import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
  plugins: [
    laravel({
      input: ["resources/css/app.css", "resources/js/app.js"],
      refresh: true,
    }),
    tailwindcss(),
  ],
  server: {
    watch: {
      ignored: ["**/storage/framework/views/**"],
    },
    host: "0.0.0.0",
    strictPort: true,
    hmr: {
      host: "localhost",
      clientPort: 5176, // port HOST
    },
  },
});
