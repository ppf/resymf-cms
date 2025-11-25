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
        'category-grid-app': './assets/vue/category-grid-app.js',
        'category-form-app': './assets/vue/category-form-app.js',
        'user-form-app': './assets/vue/user-form-app.js',
        'page-form-app': './assets/vue/page-form-app.js',
        'theme-grid-app': './assets/vue/theme-grid-app.js',
        'page-grid-app': './assets/vue/page-grid-app.js',
      },
    },
  },
  server: {
    strictPort: true,
    port: 5173,
    host: 'localhost',
  },
});
