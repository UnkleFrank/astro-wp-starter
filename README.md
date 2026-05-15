# Astro + WordPress Starter

A reusable starter kit for building fast static sites with **Astro** on the front end and **WordPress** as the content management system. WordPress handles all content editing; Astro pulls from the WP REST API at build time and deploys a static site to Netlify.

## What's Included

| Feature | Details |
|---------|---------|
| WordPress pages | All pages including nested slugs (`/services/web-design/`) |
| WordPress posts | Blog listing, individual posts, category archives |
| ACF fields | Exposed automatically via `page.acf.field_name` |
| WooCommerce ads | Products in the `ads` category rendered as ad blocks |
| Auto-rebuild | WP plugin fires a deploy hook (Netlify or Cloudflare Pages) on every content save |
| Yoast SEO | Title, description, og tags passed through automatically |
| Rich text | Gutenberg + Classic editor HTML rendered with scoped styles |
| TypeScript | Fully typed WP/WC API responses |

---

## Prerequisites

- **Node.js** 18 or later
- A **WordPress** site (self-hosted, 20i, Cloudways, WP Engine, etc.)
- A **Netlify** account for deployment
- *(Optional)* WooCommerce with REST API keys if using ad blocks
- *(Optional)* ACF or ACF PRO — fields are exposed automatically if the [ACF to REST API](https://wordpress.org/plugins/acf-to-rest-api/) plugin is installed, or if you have ACF PRO (which includes REST API support built in)

---

## Quick Start

### 1. Clone the starter

```bash
# Using degit (recommended — clean copy, no git history)
npx degit UnkleFrank/astro-wp-starter my-new-site
cd my-new-site

# Or clone normally
git clone https://github.com/UnkleFrank/astro-wp-starter.git my-new-site
cd my-new-site
```

### 2. Install dependencies

```bash
npm install
```

### 3. Configure environment variables

```bash
cp .env.example .env
```

Edit `.env`:

```env
SITE_URL=https://my-new-site.com
WP_URL=https://my-wordpress-site.com
```

If you're using WooCommerce ad blocks, also add:

```env
WC_KEY=ck_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
WC_SECRET=cs_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

Generate WC keys in **WP Admin → WooCommerce → Settings → Advanced → REST API**. Read-only permission is sufficient.

### 4. Run locally

```bash
npm run dev
```

Astro fetches content from your WP site at `http://localhost:4321`.

### 5. Deploy

This starter is a pure static build — it works on **Netlify**, **Cloudflare Pages**, **20i**, GitHub Pages, or any static host. Choose one:

#### Option A — Cloudflare Pages *(recommended — free, unlimited sites)*

1. Push your project to GitHub
2. Go to [Cloudflare Dashboard](https://dash.cloudflare.com) → **Pages → Create a project → Connect to Git**
3. Select your repo
4. Build settings:
   - Build command: `npm run build`
   - Build output directory: `dist`
5. Set environment variables under **Settings → Environment variables**:
   - `WP_URL`
   - `SITE_URL`
   - `WC_KEY` / `WC_SECRET` (if using WooCommerce)
6. Deploy
7. For auto-rebuild from WordPress, grab your deploy hook URL from **Pages project → Settings → Builds & deployments → Add deploy hook**, then paste it in **WP Admin → Settings → Astro Rebuild**

#### Option B — Netlify

1. Push your project to GitHub
2. Connect the repo in Netlify
3. Build settings are auto-detected from `netlify.toml` (`npm run build` → `dist/`)
4. Set environment variables in **Netlify → Site → Environment variables**:
   - `WP_URL`
   - `SITE_URL`
   - `WC_KEY` / `WC_SECRET` (if using WooCommerce)
5. Deploy
6. For auto-rebuild from WordPress, grab your build hook URL from **Site → Site settings → Build & deploy → Build hooks**, then paste it in **WP Admin → Settings → Astro Rebuild**

---

## WordPress Setup

### Required: Enable the REST API

The WP REST API is enabled by default in WordPress 5.0+. No configuration needed.

### Required: Install the Rebuild Plugin

This plugin fires your Netlify build hook whenever content is saved, so your static site stays in sync with WordPress automatically.

1. Copy the `wp-plugin/astro-rebuild/` folder to your WordPress installation at:
   ```
   /wp-content/plugins/astro-rebuild/
   ```
2. Activate the plugin in **WP Admin → Plugins**
3. Go to **Settings → Astro Rebuild**
4. Paste your **deploy hook URL**:
   - **Cloudflare Pages:** Pages project → Settings → Builds & deployments → Add deploy hook
   - **Netlify:** Site → Site settings → Build & deploy → Build hooks → Add build hook
5. Save settings

The plugin will now trigger a Netlify rebuild whenever you:
- Publish or update a page or post
- Save ACF fields
- Save or delete a WooCommerce product
- Update a navigation menu
- Create, edit, or delete a taxonomy term

A log of the last 10 rebuilds is shown on the settings page.

### Optional: ACF Fields

If you use Advanced Custom Fields:

- **ACF PRO**: REST API support is built in — no extra setup needed
- **ACF (free)**: Install the [ACF to REST API](https://wordpress.org/plugins/acf-to-rest-api/) plugin

ACF fields are then available in your Astro templates as:

```astro
---
// In [...slug].astro or blog/[slug].astro
const heroText = page.acf?.hero_text as string;
const ctaUrl   = page.acf?.cta_url as string;
---
<h2>{heroText}</h2>
<a href={ctaUrl}>Learn More</a>
```

### Optional: WooCommerce Ad Blocks

1. Enable the WooCommerce REST API and generate read-only keys (**WooCommerce → Settings → Advanced → REST API**)
2. Create a product category called `ads` in WooCommerce
3. Add products to the `ads` category — each product = one ad slot
4. For each product, add a custom meta field (or ACF field) named `ad_url` with the click-through URL
5. Add `<AdBlock />` to any page template:

```astro
---
import AdBlock from '../components/AdBlock.astro';
---
<aside>
  <AdBlock heading="Sponsored" limit={3} />
</aside>
```

---

## Project Structure

```
astro-wp-starter/
├── src/
│   ├── lib/
│   │   ├── wordpress.ts      ← WP REST API client (typed)
│   │   ├── woocommerce.ts    ← WC REST API client (typed)
│   │   └── types.ts          ← TypeScript interfaces
│   ├── layouts/
│   │   └── Layout.astro      ← Base HTML layout
│   ├── components/
│   │   ├── PostCard.astro    ← Blog post card
│   │   ├── RichText.astro    ← WP HTML content renderer
│   │   └── AdBlock.astro     ← WooCommerce ad block
│   └── pages/
│       ├── index.astro            ← Homepage
│       ├── [...slug].astro        ← All WP pages (nested slugs)
│       ├── blog/
│       │   ├── index.astro        ← Blog listing
│       │   └── [slug].astro       ← Individual posts
│       └── category/
│           └── [slug].astro       ← Category archives
├── public/
│   ├── _headers          ← Cloudflare Pages security headers
│   └── _redirects        ← Cloudflare Pages redirects
├── wp-plugin/
│   └── astro-rebuild/
│       └── astro-rebuild.php      ← Auto-rebuild WP plugin
├── .env.example
├── astro.config.mjs
├── netlify.toml          ← Netlify build config & headers
└── package.json
```

---

## Customization

### Colors & Typography

All design tokens are CSS custom properties in `src/layouts/Layout.astro`:

```css
:root {
  --color-primary: #1a8286;   /* change to your brand color */
  --color-dark:    #1a1a2e;
  --color-gray:    #6b7280;
  --color-border:  #e5e7eb;
  --color-light:   #f9fafb;
  --radius:        8px;
  --container:     1100px;
}
```

### Navigation

Add your nav links in the `<slot name="nav" />` in `Layout.astro`, or fetch them from the WP Menu API by extending `wordpress.ts`.

### Adding a Custom Page Template

Create a new `.astro` file in `src/pages/` and fetch the WP page by slug:

```astro
---
import Layout from '../layouts/Layout.astro';
import { getPageBySlug } from '../lib/wordpress';

const page = await getPageBySlug('contact');
if (!page) return Astro.redirect('/404');
---
<Layout title={page.title.rendered} yoast={page.yoast_head_json}>
  <div class="container">
    <h1 set:html={page.title.rendered} />
    <!-- Your custom layout here -->
    <!-- page.acf.your_field for ACF data -->
  </div>
</Layout>
```

### Using a Custom WordPress Post Type

Extend `wordpress.ts` with a new fetch function:

```typescript
export async function getAllProjects(): Promise<WPPost[]> {
  return wpFetchAll<WPPost>('/projects', { status: 'publish', _embed: 1 });
}
```

Then create `src/pages/projects/[slug].astro` following the same pattern as `blog/[slug].astro`.

---

## Environment Variables Reference

| Variable | Required | Description |
|----------|----------|-------------|
| `SITE_URL` | Yes | Your production front-end URL (e.g. `https://mysite.com`) |
| `WP_URL` | Yes | Your WordPress site URL — no trailing slash |
| `WC_KEY` | No | WooCommerce Consumer Key (read-only) |
| `WC_SECRET` | No | WooCommerce Consumer Secret (read-only) |

Set these in `.env` for local development and in **Netlify → Environment variables** for production.

---

## License

MIT — free to use, modify, and distribute.
