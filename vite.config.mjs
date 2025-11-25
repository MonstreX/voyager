import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import { viteStaticCopy } from 'vite-plugin-static-copy';
import inject from '@rollup/plugin-inject';
import path from 'path';

export default defineConfig({
  plugins: [
    vue(),
    inject({
      $: 'jquery',
      jQuery: 'jquery'
    }),
    viteStaticCopy({
      targets: [
        { src: 'node_modules/tinymce/skins', dest: 'js' },
        { src: 'resources/assets/js/skins', dest: 'js' },
        { src: 'node_modules/tinymce/themes/silver', dest: 'js/themes' },
        { src: 'node_modules/tinymce/models/dom', dest: 'js/models' },
        { src: 'node_modules/tinymce/icons/default', dest: 'js/icons' },
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
        assetFileNames: (assetInfo) => {
          if (assetInfo.name.endsWith('.css')) {
            return 'css/app.css';
          }
          return 'assets/[name].[ext]';
        },
        format: 'iife',
        globals: {
          moment: 'moment'
        }
      },
      external: ['moment', 'jquery', 'select2', 'bootstrap', 'datatables.net', 'datatables-bootstrap3-plugin', 'bootstrap-toggle', 'nestable2', 'jquery-match-height', 'dropzone', 'eonasdan-bootstrap-datetimepicker']
    }
  },
  resolve: {
    alias: {
      'vue': 'vue/dist/vue.esm-bundler.js',
      '@': path.resolve(__dirname, 'resources/assets/js')
    }
  }
});
