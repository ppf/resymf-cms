import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import path from 'node:path';

export default defineConfig({
  plugins: [
    vue(),
  ],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, 'assets'),
    },
  },
  build: {
    manifest: true,
    outDir: 'public/build',
    rollupOptions: {
      input: {
        app: './assets/app.js',
        admin: './assets/admin.js',
        cms: './assets/cms.js',
        admin_vue: './assets/vue/admin-dashboard-app.js',
        admin_users_vue: './assets/vue/admin-users-app.js',
        cms_vue: './assets/vue/cms-app.js',
      },
    },
  },
  server: {
    strictPort: true,
    port: 5173,
    host: 'localhost',
  },
});
