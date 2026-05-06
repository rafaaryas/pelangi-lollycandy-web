-- Legacy -> Normalized mapping for Pelangi Lollycandy
-- Source tables: produk, varian_produk
-- Target tables: categories, products, product_variants

INSERT INTO categories (name, slug, description, is_active, created_at, updated_at)
SELECT DISTINCT
    p.kategori_produk,
    LOWER(REPLACE(TRIM(p.kategori_produk), ' ', '-')),
    'Migrated from legacy table produk',
    1,
    NOW(),
    NOW()
FROM produk p
WHERE p.kategori_produk IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM categories c
      WHERE c.slug = LOWER(REPLACE(TRIM(p.kategori_produk), ' ', '-'))
  );

INSERT INTO products (
    category_id, name, slug, description, price_from, price_strike, badge, sku, weight_gram,
    flavor, shopee_url, favorite_clicks, published_at, is_active, created_at, updated_at
)
SELECT
    c.id,
    p.nama_produk,
    CONCAT(LOWER(REPLACE(TRIM(p.nama_produk), ' ', '-')), '-', p.id_produk),
    COALESCE(p.deskripsi, 'Migrated legacy product'),
    0,
    NULL,
    'none',
    CONCAT('LEGACY-', p.id_produk),
    0,
    NULL,
    NULL,
    0,
    NULL,
    CASE WHEN LOWER(COALESCE(p.status_produk, '')) = 'aktif' THEN 1 ELSE 0 END,
    NOW(),
    NOW()
FROM produk p
JOIN categories c ON c.slug = LOWER(REPLACE(TRIM(p.kategori_produk), ' ', '-'))
WHERE NOT EXISTS (
    SELECT 1 FROM products np WHERE np.name = p.nama_produk
);

INSERT INTO product_variants (product_id, size, flavor, stock_info, price, is_active, created_at, updated_at)
SELECT
    np.id,
    COALESCE(v.ukuran, '-'),
    COALESCE(v.rasa, '-'),
    CASE WHEN v.stok_varian IS NULL THEN NULL ELSE CONCAT('Stok: ', v.stok_varian) END,
    COALESCE(v.harga_jual, 0),
    1,
    NOW(),
    NOW()
FROM varian_produk v
JOIN produk p ON p.id_produk = v.id_produk
JOIN products np ON np.name = p.nama_produk
WHERE NOT EXISTS (
    SELECT 1 FROM product_variants pv
    WHERE pv.product_id = np.id
      AND pv.size = COALESCE(v.ukuran, '-')
      AND pv.flavor = COALESCE(v.rasa, '-')
      AND pv.price = COALESCE(v.harga_jual, 0)
);
