import { defineConfig } from 'vite'
import path from 'path'

export default defineConfig({
  base: './',
  publicDir: false,
  build: {
    outDir: 'public/build',
    rollupOptions: {
      input: {
        main: path.resolve(__dirname, 'assets/main.js'),
      },
      output: {
        entryFileNames: 'js/[name].js',
        chunkFileNames: 'js/[name].js',
        assetFileNames: (assetInfo) => {
          if (assetInfo.name?.endsWith('.css')) return 'css/[name].[ext]'
          if (/\.(woff2?|eot|ttf|otf|svg)$/.test(assetInfo.name ?? '')) return 'fonts/[name].[ext]'
          return 'assets/[name].[ext]'
        },
      },
    },
    manifest: false,
  },
  css: {
    preprocessorOptions: {
      css: {
        additionalData: '',
      },
    },
  },
})
