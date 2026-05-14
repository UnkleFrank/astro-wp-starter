// src/lib/wordpress.ts
// WordPress REST API client
// Set WP_URL in your .env file: WP_URL=https://yoursite.com

import type { WPPage, WPPost, WPCategory, WPTag, WPMedia, WPSiteInfo } from './types';

const BASE = import.meta.env.WP_URL?.replace(/\/$/, '');
if (!BASE) throw new Error('WP_URL is not set in your .env file');

const API = `${BASE}/wp-json/wp/v2`;

// ── Helpers ───────────────────────────────────────────────────────────────────

async function wpFetch<T>(path: string, params: Record<string, string | number> = {}): Promise<T> {
  const url = new URL(`${API}${path}`);
  Object.entries(params).forEach(([k, v]) => url.searchParams.set(k, String(v)));
  const res = await fetch(url.toString());
  if (!res.ok) throw new Error(`WP API error ${res.status} for ${url}`);
  return res.json() as Promise<T>;
}

async function wpFetchAll<T>(path: string, params: Record<string, string | number> = {}): Promise<T[]> {
  const perPage = 100;
  const first = await fetch(
    Object.assign(new URL(`${API}${path}`), {
      search: new URLSearchParams({ per_page: String(perPage), ...Object.fromEntries(Object.entries(params).map(([k, v]) => [k, String(v)])) }).toString()
    })
  );
  if (!first.ok) throw new Error(`WP API error ${first.status}`);
  const total = parseInt(first.headers.get('X-WP-TotalPages') ?? '1');
  const firstPage = await first.json() as T[];
  if (total <= 1) return firstPage;

  const rest = await Promise.all(
    Array.from({ length: total - 1 }, (_, i) =>
      wpFetch<T[]>(path, { per_page: perPage, page: i + 2, ...params })
    )
  );
  return [...firstPage, ...rest.flat()];
}

// ── Site Info ─────────────────────────────────────────────────────────────────

export async function getSiteInfo(): Promise<WPSiteInfo> {
  const res = await fetch(`${BASE}/wp-json`);
  return res.json() as Promise<WPSiteInfo>;
}

// ── Pages ─────────────────────────────────────────────────────────────────────

export async function getAllPages(): Promise<WPPage[]> {
  return wpFetchAll<WPPage>('/pages', { status: 'publish', _embed: 1 });
}

export async function getPageBySlug(slug: string): Promise<WPPage | null> {
  const pages = await wpFetch<WPPage[]>('/pages', { slug, status: 'publish', _embed: 1 });
  return pages[0] ?? null;
}

export async function getPageById(id: number): Promise<WPPage | null> {
  try {
    return await wpFetch<WPPage>(`/pages/${id}`, { _embed: 1 });
  } catch {
    return null;
  }
}

/**
 * Build a map of page ID → full path slug array.
 * Handles parent/child nesting: /services/web-design/ → ['services', 'web-design']
 */
export function buildPagePaths(pages: WPPage[]): Map<number, string[]> {
  const byId = new Map(pages.map(p => [p.id, p]));
  const cache = new Map<number, string[]>();

  function getPath(page: WPPage): string[] {
    if (cache.has(page.id)) return cache.get(page.id)!;
    if (!page.parent) {
      const path = [page.slug];
      cache.set(page.id, path);
      return path;
    }
    const parent = byId.get(page.parent);
    const parentPath = parent ? getPath(parent) : [];
    const path = [...parentPath, page.slug];
    cache.set(page.id, path);
    return path;
  }

  pages.forEach(p => getPath(p));
  return cache;
}

// ── Posts ─────────────────────────────────────────────────────────────────────

export async function getAllPosts(perPage = 100): Promise<WPPost[]> {
  return wpFetchAll<WPPost>('/posts', { status: 'publish', _embed: 1, per_page: perPage });
}

export async function getPostBySlug(slug: string): Promise<WPPost | null> {
  const posts = await wpFetch<WPPost[]>('/posts', { slug, status: 'publish', _embed: 1 });
  return posts[0] ?? null;
}

export async function getPostsByCategory(categoryId: number, perPage = 20): Promise<WPPost[]> {
  return wpFetchAll<WPPost>('/posts', { categories: categoryId, status: 'publish', _embed: 1, per_page: perPage });
}

// ── Categories ────────────────────────────────────────────────────────────────

export async function getAllCategories(): Promise<WPCategory[]> {
  return wpFetchAll<WPCategory>('/categories', { hide_empty: 1 });
}

export async function getCategoryBySlug(slug: string): Promise<WPCategory | null> {
  const cats = await wpFetch<WPCategory[]>('/categories', { slug });
  return cats[0] ?? null;
}

// ── Tags ──────────────────────────────────────────────────────────────────────

export async function getAllTags(): Promise<WPTag[]> {
  return wpFetchAll<WPTag>('/tags', { hide_empty: 1 });
}

// ── Media ─────────────────────────────────────────────────────────────────────

export async function getMediaById(id: number): Promise<WPMedia | null> {
  try {
    return await wpFetch<WPMedia>(`/media/${id}`);
  } catch {
    return null;
  }
}

// ── Helpers ───────────────────────────────────────────────────────────────────

/** Extract featured image URL at a given size (falls back to full). */
export function getFeaturedImageUrl(post: WPPage | WPPost, size = 'large'): string | null {
  const media = post._embedded?.['wp:featuredmedia']?.[0];
  if (!media) return null;
  return media.media_details?.sizes?.[size]?.source_url ?? media.source_url ?? null;
}

/** Format a WP date string to a readable format. */
export function formatDate(dateStr: string): string {
  return new Date(dateStr).toLocaleDateString('en-US', {
    year: 'numeric', month: 'long', day: 'numeric',
  });
}

/** Get first N words of rendered HTML content as plain text excerpt. */
export function excerptFromContent(html: string, words = 30): string {
  const text = html.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
  return text.split(' ').slice(0, words).join(' ') + '…';
}
