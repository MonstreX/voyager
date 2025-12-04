import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import { viteStaticCopy } from 'vite-plugin-static-copy';
import inject from '@rollup/plugin-inject';
import path from 'path';

export default defineConfig({
  base: '/vendor/voyager/',
  plugins: [
    vue(),
    viteStaticCopy({
      targets: [
        { src: 'node_modules/ace-builds/src-noconflict', dest: 'js/ace/libs' }
      ]
    })
  ],
  build: {
    outDir: 'publishable/assets',
    emptyOutDir: false,
    rollupOptions: {
      input: 'resources/assets/js/app.js',
      output: {
        entryFileNames: 'js/app.js',
        format: 'iife',
        assetFileNames: (assetInfo) => {
          if (assetInfo.name.endsWith('.css')) {
            return 'css/app.css';
          }
          return 'assets/[name].[ext]';
        },
        globals: {
          jquery: '$'
        }
      },
      external: []
    }
  },
  resolve: {
    alias: {
      'vue': 'vue/dist/vue.esm-bundler.js',
      '@': path.resolve(__dirname, 'resources/assets/js'),
      '/vendor/voyager': path.resolve(__dirname, 'publishable/assets')
    }
  },
  css: {
    preprocessorOptions: {
      scss: {
        silenceDeprecations: ['import', 'global-builtin'],
        quietDeps: true,
      }
    }
  }
});
