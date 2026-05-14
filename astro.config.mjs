import { defineConfig } from 'astro/config';
import netlify from '@astrojs/netlify';

export default defineConfig({
  output: 'static',
  adapter: netlify(),

  // Set this to your production domain
  site: 'https://yoursite.com',

  // Vite env vars — WP_URL, WC_KEY, WC_SECRET are accessed via import.meta.env
  vite: {
    build: {
      // Increase chunk size warning limit for large static builds
      chunkSizeWarningLimit: 1024,
    },
  },
});
