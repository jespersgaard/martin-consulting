import { defineConfig } from 'astro/config';
import sitemap from '@astrojs/sitemap';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
  site: 'https://www.jespersgaard.com',
  trailingSlash: 'always',
  build: { format: 'directory' },
  integrations: [sitemap({ lastmod: new Date() })],
  vite: { plugins: [tailwindcss()] },
});
