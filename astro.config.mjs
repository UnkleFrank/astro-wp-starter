import { defineConfig } from 'astro/config';

// Pure static output — no adapter needed.
// Works on Netlify, Cloudflare Pages, 20i, GitHub Pages, or any static host.

export default defineConfig({
  output: 'static',

  // Reads SITE_URL from .env — set this to your production domain
  site: process.env.SITE_URL || 'https://yoursite.com',

  // Vite env vars — WP_URL, WC_KEY, WC_SECRET are accessed via import.meta.env
  vite: {
    build: {
      // Increase chunk size warning limit for large static builds
      chunkSizeWarningLimit: 1024,
    },
  },
});
