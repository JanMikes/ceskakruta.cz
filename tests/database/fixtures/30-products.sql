-- Products & recipes section (PLAN §2.9, §2.10), id range 3000-3999.
-- 3001 meat per kg with packing + packages, 3002 inactive product without recipes.
INSERT INTO `product` (`id`, `hide_on_old_web`, `order`, `code`, `price`, `half_of_product_id`, `name`, `type`, `can_be_packed_flag`, `can_be_sliced_flag`,
    `price_pack`, `text`, `text_additional`, `force_packing`, `packages_size`, `unit`, `weight_per_unit`, `ins_dt`, `active_flag`, `del_flag`) VALUES
(3001, 0, 3020, '300100', 199, NULL, 'Krůtí stehno test', 1, 1, 1, 20, '<p>Stehno</p>', '<p>Pod čarou stehno</p>', 1, '[250,500]', 'kg', 1, '2024-01-01 00:00:00', 1, 0),
(3002, 0, 3010, '300200', 149, NULL, 'Krůtí klobása test', 2, 0, 0, NULL, '<p>Klobása</p>', NULL, 0, '[]', 'ks', 0.4, '2024-01-01 00:00:00', 0, 0);

INSERT INTO `photo` (`id`, `filename`, `path`, `width`, `height`, `ins_dt`, `ins_process`) VALUES
(3001, '5f0c1a2b3c4d5-3001.jpg', 'upload/recipes', 800, 600, '2024-01-01 00:00:00', 'fixture');

-- Recipes of product 3001: listed by name -> 3002 (Dušené), 3004 (Grilované, inactive), 3001 (Pečené, with photo);
-- 3003 is soft-deleted (not listed, not counted). Count shown on the product list = 3.
INSERT INTO `recipe` (`id`, `product_id`, `photo_id`, `name`, `text`, `ins_dt`, `active_flag`, `del_flag`) VALUES
(3001, 3001, 3001, 'Pečené stehno', '<p>Pečeme</p>', '2024-01-01 08:00:00', 1, 0),
(3002, 3001, NULL, 'Dušené stehno', '<p>Dusíme</p>', '2024-01-01 08:00:00', 1, 0),
(3003, 3001, NULL, 'Avokádové stehno', '<p>Smazáno</p>', '2024-01-01 08:00:00', 1, 1),
(3004, 3001, NULL, 'Grilované stehno', '<p>Grilujeme</p>', '2024-01-01 08:00:00', 0, 0);
