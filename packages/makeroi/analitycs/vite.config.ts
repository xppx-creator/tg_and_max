import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import { resolve } from 'node:path';
import { appConfigsPlugin } from '@makeroi/app-config/vite';

/** Корень пакета (node_modules / composer.json). Vite `root` — resources/spa. */
const APP_ROOT = resolve(__dirname);

export default defineConfig({
  root: resolve(APP_ROOT, 'resources/spa'),
  base: '/analytics/',
  plugins: [
    vue(),
    // Тот же virtual, что у виджетов через vite-utils — без своего плагина на каждое SPA.
    appConfigsPlugin({ root: APP_ROOT }),
  ],
  resolve: {
    alias: {
      '@': resolve(APP_ROOT, 'resources/spa/src'),
    },
  },
  css: {
    preprocessorOptions: {
      scss: {
        // globals подтягиваются из @makeroi/components в base.scss
      },
    },
  },
  build: {
    outDir: resolve(APP_ROOT, 'dist'),
    emptyOutDir: true,
    manifest: false,
    rollupOptions: {
      input: resolve(APP_ROOT, 'resources/spa/index.html'),
    },
  },
});
