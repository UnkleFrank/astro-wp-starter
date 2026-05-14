// src/lib/woocommerce.ts
// WooCommerce REST API client (v3)
// Requires WC API keys — set in .env:
//   WP_URL=https://yoursite.com
//   WC_KEY=ck_xxxxxxxxxxxx
//   WC_SECRET=cs_xxxxxxxxxxxx

import type { WCProduct } from './types';

const BASE    = import.meta.env.WP_URL?.replace(/\/$/, '');
const WC_KEY  = import.meta.env.WC_KEY;
const WC_SEC  = import.meta.env.WC_SECRET;

function wcHeaders(): HeadersInit {
  if (!WC_KEY || !WC_SEC) throw new Error('WC_KEY and WC_SECRET must be set in .env');
  const credentials = btoa(`${WC_KEY}:${WC_SEC}`);
  return { Authorization: `Basic ${credentials}` };
}

async function wcFetch<T>(path: string, params: Record<string, string | number> = {}): Promise<T> {
  const url = new URL(`${BASE}/wp-json/wc/v3${path}`);
  Object.entries(params).forEach(([k, v]) => url.searchParams.set(k, String(v)));
  const res = await fetch(url.toString(), { headers: wcHeaders() });
  if (!res.ok) throw new Error(`WC API error ${res.status} for ${url}`);
  return res.json() as Promise<T>;
}

async function wcFetchAll<T>(path: string, params: Record<string, string | number> = {}): Promise<T[]> {
  const perPage = 100;
  const first = await fetch(
    Object.assign(new URL(`${BASE}/wp-json/wc/v3${path}`), {
      search: new URLSearchParams({ per_page: String(perPage), ...Object.fromEntries(Object.entries(params).map(([k, v]) => [k, String(v)])) }).toString()
    }),
    { headers: wcHeaders() }
  );
  if (!first.ok) throw new Error(`WC API error ${first.status}`);
  const total = parseInt(first.headers.get('X-WP-TotalPages') ?? '1');
  const firstPage = await first.json() as T[];
  if (total <= 1) return firstPage;

  const rest = await Promise.all(
    Array.from({ length: total - 1 }, (_, i) =>
      wcFetch<T[]>(path, { per_page: perPage, page: i + 2, ...params })
    )
  );
  return [...firstPage, ...rest.flat()];
}

// ── Products ──────────────────────────────────────────────────────────────────

/** Get all published products. */
export async function getAllProducts(): Promise<WCProduct[]> {
  return wcFetchAll<WCProduct>('/products', { status: 'publish' });
}

/** Get products by category slug (useful for ad categories). */
export async function getProductsByCategory(categorySlug: string): Promise<WCProduct[]> {
  // First get category ID from slug
  const cats = await wcFetch<{ id: number; slug: string }[]>('/products/categories', { slug: categorySlug });
  if (!cats.length) return [];
  return wcFetchAll<WCProduct>('/products', { status: 'publish', category: cats[0].id });
}

/** Get a single product by slug. */
export async function getProductBySlug(slug: string): Promise<WCProduct | null> {
  const products = await wcFetch<WCProduct[]>('/products', { slug, status: 'publish' });
  return products[0] ?? null;
}

/** Get ad products — by convention, products in the "ads" category. */
export async function getAdProducts(): Promise<WCProduct[]> {
  try {
    return await getProductsByCategory('ads');
  } catch {
    return [];
  }
}

/** Extract a meta value from WC product meta_data array. */
export function getProductMeta(product: WCProduct, key: string): unknown {
  return product.meta_data.find(m => m.key === key)?.value ?? null;
}
