-- ==============================================================================
-- PriceHub: Full PostgreSQL / Supabase Schema for Electronics Price Aggregator
-- ==============================================================================

-- 1. Extensions
CREATE EXTENSION IF NOT EXISTS "pg_trgm";
CREATE EXTENSION IF NOT EXISTS "btree_gist";

-- 2. Categories
CREATE TABLE IF NOT EXISTS public.categories (
    id INT PRIMARY KEY,
    parent_id INT REFERENCES public.categories(id) ON DELETE SET NULL,
    name TEXT NOT NULL,
    slug TEXT NOT NULL UNIQUE,
    icon TEXT NOT NULL DEFAULT 'box',
    facets JSONB NOT NULL DEFAULT '[]'::jsonb,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMPTZ NOT NULL DEFAULT timezone('utc'::text, now())
);

-- 3. Brands
CREATE TABLE IF NOT EXISTS public.brands (
    id SERIAL PRIMARY KEY,
    name TEXT NOT NULL UNIQUE,
    slug TEXT NOT NULL UNIQUE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT timezone('utc'::text, now())
);

-- 4. Shops / Donor Retailers
CREATE TABLE IF NOT EXISTS public.shops (
    id TEXT PRIMARY KEY,
    name TEXT NOT NULL,
    kind TEXT NOT NULL CHECK (kind IN ('retail', 'marketplace', 'crossborder', 'classifieds')),
    mode TEXT NOT NULL CHECK (mode IN ('prices', 'link_only')),
    url_template TEXT NOT NULL,
    color TEXT NOT NULL DEFAULT '#0055ff',
    active BOOLEAN NOT NULL DEFAULT true,
    created_at TIMESTAMPTZ NOT NULL DEFAULT timezone('utc'::text, now())
);

-- 5. Products
CREATE TABLE IF NOT EXISTS public.products (
    id INT PRIMARY KEY,
    category_id INT NOT NULL REFERENCES public.categories(id) ON DELETE RESTRICT,
    brand TEXT NOT NULL,
    title TEXT NOT NULL,
    slug TEXT NOT NULL,
    mpn TEXT,
    barcode TEXT,
    img TEXT NOT NULL,
    pub SMALLINT NOT NULL DEFAULT 1 CHECK (pub IN (0, 1)),
    specs JSONB NOT NULL DEFAULT '{}'::jsonb,
    attrs JSONB NOT NULL DEFAULT '{}'::jsonb,
    min_price INT NOT NULL DEFAULT 0,
    max_price INT NOT NULL DEFAULT 0,
    offers_cnt INT NOT NULL DEFAULT 0,
    shops_cnt INT NOT NULL DEFAULT 0,
    has_mp BOOLEAN NOT NULL DEFAULT false,
    has_cb BOOLEAN NOT NULL DEFAULT false,
    price_drop NUMERIC(5,2) NOT NULL DEFAULT 0.0,
    popularity INT NOT NULL DEFAULT 0,
    created_at TIMESTAMPTZ NOT NULL DEFAULT timezone('utc'::text, now()),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT timezone('utc'::text, now())
);

-- 6. Offers
CREATE TABLE IF NOT EXISTS public.offers (
    id BIGSERIAL PRIMARY KEY,
    product_id INT NOT NULL REFERENCES public.products(id) ON DELETE CASCADE,
    shop_id TEXT NOT NULL REFERENCES public.shops(id) ON DELETE CASCADE,
    offer_key TEXT NOT NULL UNIQUE,
    price INT,
    landed_price INT,
    stock SMALLINT NOT NULL DEFAULT 1,
    condition TEXT NOT NULL DEFAULT 'new',
    origin TEXT NOT NULL DEFAULT 'RU',
    seller JSONB,
    note TEXT,
    url TEXT NOT NULL,
    img TEXT,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT timezone('utc'::text, now())
);

-- 7. Price History
CREATE TABLE IF NOT EXISTS public.price_history (
    id BIGSERIAL PRIMARY KEY,
    product_id INT NOT NULL REFERENCES public.products(id) ON DELETE CASCADE,
    shop_id TEXT NOT NULL REFERENCES public.shops(id) ON DELETE CASCADE,
    price INT NOT NULL,
    in_stock SMALLINT NOT NULL DEFAULT 1,
    recorded_at DATE NOT NULL DEFAULT CURRENT_DATE,
    CONSTRAINT uq_history_product_shop_date UNIQUE (product_id, shop_id, recorded_at)
);

-- ==============================================================================
-- Indexes for Sub-millisecond Performance
-- ==============================================================================

CREATE INDEX IF NOT EXISTS idx_products_cat_pub ON public.products(category_id, pub);
CREATE INDEX IF NOT EXISTS idx_products_brand ON public.products(brand);
CREATE INDEX IF NOT EXISTS idx_products_min_price ON public.products(min_price);
CREATE INDEX IF NOT EXISTS idx_products_pop ON public.products(popularity DESC);
CREATE INDEX IF NOT EXISTS idx_products_drop ON public.products(price_drop DESC);
CREATE INDEX IF NOT EXISTS idx_products_slug ON public.products(slug);
CREATE INDEX IF NOT EXISTS idx_products_attrs ON public.products USING gin(attrs);
CREATE INDEX IF NOT EXISTS idx_products_title_trgm ON public.products USING gin(title gin_trgm_ops);
CREATE INDEX IF NOT EXISTS idx_products_brand_trgm ON public.products USING gin(brand gin_trgm_ops);

CREATE INDEX IF NOT EXISTS idx_offers_product_landed ON public.offers(product_id, landed_price ASC NULLS LAST);
CREATE INDEX IF NOT EXISTS idx_offers_shop ON public.offers(shop_id);

CREATE INDEX IF NOT EXISTS idx_price_history_product ON public.price_history(product_id, recorded_at ASC);

-- ==============================================================================
-- Security Invoker Views (Per Postgres 15+ Supabase Standards)
-- ==============================================================================

CREATE OR REPLACE VIEW public.v_category_counts WITH (security_invoker = true) AS
SELECT 
    c.id AS category_id,
    c.name,
    c.slug,
    c.parent_id,
    COUNT(p.id) AS products_count,
    MIN(p.min_price) AS min_price,
    MAX(p.max_price) AS max_price
FROM public.categories c
LEFT JOIN public.products p ON p.category_id = c.id AND p.pub = 1
GROUP BY c.id, c.name, c.slug, c.parent_id;

-- ==============================================================================
-- Full-Text & Trigram Search Function
-- ==============================================================================

CREATE OR REPLACE FUNCTION public.search_products(search_term TEXT, max_results INT DEFAULT 20)
RETURNS TABLE (
    id INT,
    title TEXT,
    brand TEXT,
    category_id INT,
    min_price INT,
    img TEXT,
    slug TEXT,
    similarity REAL
) 
LANGUAGE sql STABLE SECURITY INVOKER
AS $$
    SELECT 
        p.id,
        p.title,
        p.brand,
        p.category_id,
        p.min_price,
        p.img,
        p.slug,
        similarity(p.title || ' ' || p.brand, search_term) AS similarity
    FROM public.products p
    WHERE p.pub = 1
      AND (
          p.title ILIKE '%' || search_term || '%'
          OR p.brand ILIKE '%' || search_term || '%'
          OR similarity(p.title || ' ' || p.brand, search_term) > 0.15
      )
    ORDER BY similarity DESC, p.popularity DESC
    LIMIT max_results;
$$;

-- ==============================================================================
-- Row Level Security (RLS) Configuration
-- ==============================================================================

ALTER TABLE public.categories ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.brands ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.shops ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.products ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.offers ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.price_history ENABLE ROW LEVEL SECURITY;

-- 1. Read Policies for Anonymous and Authenticated users
CREATE POLICY "Allow public read access on categories" 
    ON public.categories FOR SELECT TO anon, authenticated USING (true);

CREATE POLICY "Allow public read access on brands" 
    ON public.brands FOR SELECT TO anon, authenticated USING (true);

CREATE POLICY "Allow public read access on shops" 
    ON public.shops FOR SELECT TO anon, authenticated USING (true);

CREATE POLICY "Allow public read access on products" 
    ON public.products FOR SELECT TO anon, authenticated USING (pub = 1);

CREATE POLICY "Allow public read access on offers" 
    ON public.offers FOR SELECT TO anon, authenticated USING (true);

CREATE POLICY "Allow public read access on price_history" 
    ON public.price_history FOR SELECT TO anon, authenticated USING (true);

-- 2. Grants for Data API
GRANT USAGE ON SCHEMA public TO anon, authenticated;
GRANT SELECT ON ALL TABLES IN SCHEMA public TO anon, authenticated;
