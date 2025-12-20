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
    cssCodeSplit: true,
    rollupOptions: {
      input: {
        app: 'resources/assets/js/app.js',
        auth: 'resources/assets/js/auth.js',
        'vue-bundle': 'resources/assets/js/vue-bundle.js',
        editors: 'resources/assets/js/editors.js',
        ga: 'resources/assets/js/ga.js'
      },
      output: {
        entryFileNames: 'js/[name].js',
        format: 'es',
        // Prevent automatic chunk splitting
        manualChunks: undefined,
        assetFileNames: (assetInfo) => {
          if (assetInfo.name.endsWith('.css')) {
            const baseName = assetInfo.name.replace(/\.css$/, '');
            return `css/${baseName}.css`;
          }
          return 'assets/[name].[ext]';
        }
      }
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
