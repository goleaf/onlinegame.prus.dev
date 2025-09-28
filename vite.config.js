import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
  plugins: [
    laravel({
      input: [
        'resources/css/app.css',
        'resources/js/app.js',
        'resources/css/travian-game.css',
        'resources/css/travian-assets.css',
        'resources/js/travian-game.js',
        'resources/js/travian-realtime.js',
      ],
      refresh: true,
    }),
    tailwindcss(),
  ],
  build: {
    rollupOptions: {
      output: {
        assetFileNames: (assetInfo) => {
          const info = assetInfo.name.split('.');
          const ext = info[info.length - 1];
          if (/\.(css)$/.test(assetInfo.name)) {
            return `css/[name]-[hash].${ext}`;
          }
          if (/\.(js)$/.test(assetInfo.name)) {
            return `js/[name]-[hash].${ext}`;
          }
          return `assets/[name]-[hash].${ext}`;
        },
      },
    },
  },
});
