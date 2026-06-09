import { defineConfig, loadEnv } from "vite";
import laravel from "laravel-vite-plugin";
import tailwindcss from "@tailwindcss/vite";

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), "");

  // Saat VITE_TUNNEL_URL diset (misal: https://vite-xxx.trycloudflare.com),
  // HMR akan menggunakan tunnel agar bisa diakses dari HP / device lain.
  const tunnelHost = env.VITE_TUNNEL_URL
    ? env.VITE_TUNNEL_URL.replace(/^https?:\/\//, "").replace(/\/$/, "")
    : null;

  const hmr = tunnelHost
    ? { host: tunnelHost, clientPort: 443, protocol: "wss" }
    : { host: "localhost", clientPort: 5176 };

  return {
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
      hmr,
    },
  };
});
