// src/lib/types.ts
// TypeScript interfaces for WordPress REST API responses

export interface WPRendered {
  rendered: string;
  protected?: boolean;
}

export interface WPMedia {
  id: number;
  source_url: string;
  alt_text: string;
  media_details: {
    width: number;
    height: number;
    sizes: Record<string, {
      source_url: string;
      width: number;
      height: number;
    }>;
  };
}

export interface WPPage {
  id: number;
  slug: string;
  status: string;
  title: WPRendered;
  content: WPRendered;
  excerpt: WPRendered;
  parent: number;           // 0 = top-level
  menu_order: number;
  link: string;             // full URL — used to compute path
  template: string;
  featured_media: number;
  _embedded?: {
    'wp:featuredmedia'?: WPMedia[];
    'wp:term'?: WPTerm[][];
  };
  acf?: Record<string, unknown>;
  yoast_head_json?: WPYoast;
}

export interface WPPost {
  id: number;
  slug: string;
  status: string;
  date: string;
  modified: string;
  title: WPRendered;
  content: WPRendered;
  excerpt: WPRendered;
  featured_media: number;
  categories: number[];
  tags: number[];
  author: number;
  _embedded?: {
    'wp:featuredmedia'?: WPMedia[];
    'wp:term'?: WPTerm[][];
    author?: WPAuthor[];
  };
  acf?: Record<string, unknown>;
  yoast_head_json?: WPYoast;
}

export interface WPCategory {
  id: number;
  slug: string;
  name: string;
  description: string;
  count: number;
  parent: number;
}

export interface WPTag {
  id: number;
  slug: string;
  name: string;
  count: number;
}

export interface WPTerm {
  id: number;
  slug: string;
  name: string;
  taxonomy: string;
}

export interface WPAuthor {
  id: number;
  name: string;
  slug: string;
  avatar_urls: Record<string, string>;
}

export interface WPYoast {
  title?: string;
  description?: string;
  og_title?: string;
  og_description?: string;
  og_image?: { url: string }[];
  canonical?: string;
  robots?: Record<string, string>;
}

export interface WPSiteInfo {
  name: string;
  description: string;
  url: string;
  home: string;
  gmt_offset: number;
  timezone_string: string;
}

// WooCommerce
export interface WCProduct {
  id: number;
  slug: string;
  name: string;
  status: string;
  description: string;
  short_description: string;
  permalink: string;
  price: string;
  regular_price: string;
  images: {
    id: number;
    src: string;
    alt: string;
  }[];
  categories: { id: number; name: string; slug: string }[];
  tags: { id: number; name: string; slug: string }[];
  acf?: Record<string, unknown>;
  meta_data: { key: string; value: unknown }[];
}
