import path from 'path';
import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
  server:  {
    port: 5173,
    host: '0.0.0.0',
    hmr: true
  },
  resolve: {
    alias: {
      "@": path.resolve(__dirname, "./resources/scripts/src"),
      '@components': path.resolve(__dirname, './resources/scripts/src/components'),
    },
  },
  plugins: [react()],
  build: {
    manifest: true,
    outDir: 'resources/scripts/dist',
    emptyOutDir: true,
    rollupOptions: {
      input: {
        main: path.resolve(__dirname, './resources/scripts/src/app.jsx'),
      },
      output: {
        entryFileNames: '[name].js',
        chunkFileNames: '[name].js',
        assetFileNames: '[name].[ext]',
      },
    },
  },
});