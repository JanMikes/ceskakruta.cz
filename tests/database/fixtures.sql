-- Deterministic test data, loaded by tests/bootstrap.php after schema.sql.
-- Synthetic only: never copy rows from a production dump here.
-- Reference data (lookup tables, turkey product ids 1/2/41/42 + 47-50) mirrors production because code depends on these ids.
-- Passwords use the test salts from .env.test: MD5(CONCAT(SECURITY_SALT_1, password, SECURITY_SALT_2)).

-- Lookup tables (same ids as production)
INSERT INTO `lst_product_tp` (`id`, `name`, `ins_dt`, `active_flag`, `del_flag`) VALUES
(1, 'Krůta', '2014-01-01 00:00:00', 1, 0),
(2, 'Krocan', '2014-01-01 00:00:00', 1, 0);

INSERT INTO `lst_thumbnail_tp` (`id`, `code`, `name`, `width`, `height`, `ins_dt`, `active_flag`, `del_flag`) VALUES
(1, 'admin', 'Admin', 120, 120, '2014-01-01 00:00:00', 1, 0),
(2, 'photogallery', 'Photogallery', 233, 180, '2014-01-01 00:00:00', 1, 0),
(3, 'recipe', 'recipe', 228, 130, '2014-01-01 00:00:00', 1, 0),
(4, 'recipe-list', 'recipe-list', 307, 130, '2014-01-01 00:00:00', 1, 0);

INSERT INTO `sortiment_category` (`id`, `order`, `name`, `ins_dt`, `active_flag`, `del_flag`) VALUES
(1, 40, 'Mouky', '2014-01-01 00:00:00', 1, 0),
(2, 30, 'Oleje', '2014-01-01 00:00:00', 1, 0),
(3, 20, 'Koření', '2014-01-01 00:00:00', 1, 0),
(4, 10, 'Ostatní', '2014-01-01 00:00:00', 1, 0);

-- Places: 1 = own shop (Rychvald, special case in Pohoda dates), 7 = own delivery, 21 = CoolBalík delivery,
-- 22 = own delivery for wholesale (special option in user form), 30 = hidden in admin
INSERT INTO `place` (`id`, `is_cool_balik`, `order`, `name`, `title`, `force_delivery_address`, `force_packing`, `description`,
    `allow_order_day_2_days_before`, `allow_order_day_2_time`, `allow_order_day_5_days_before`, `allow_order_day_5_time`,
    `opening_hours`, `opening_hours_email`, `ins_dt`, `show_on_old_web`, `allow_only_wholesale`, `hide_in_admin`, `active_flag`, `del_flag`) VALUES
(1, NULL, 50, 'Rychvald - Prodejna', 'Rychvald - Prodejna, Rychvald 1215', 0, 0, '<p>Prodejna Rychvald</p>', 1, '12:00:00', 2, '23:59:00', 'Po-Pá 8-16', 'Po-Pá 8-16', '2014-01-01 00:00:00', 1, 0, 0, 1, 0),
(7, NULL, 40, 'Rozvoz domů Přerov', 'Rozvoz domů Přerov + okolí do 20 km', 1, 0, NULL, 2, '23:59:00', NULL, NULL, NULL, NULL, '2014-01-01 00:00:00', 1, 0, 0, 1, 0),
(21, 1, 30, 'Rozvoz CoolBalík', 'Rozvoz CoolBalík', 1, 1, NULL, 3, '10:00:00', 3, '10:00:00', NULL, NULL, '2020-01-01 00:00:00', 0, 0, 0, 1, 0),
(22, 0, 20, 'Rozvoz Česká Krůta - vlastní prodejny', 'Rozvoz Česká Krůta - vlastní prodejny', 1, 0, NULL, 1, '23:59:00', 1, '23:59:00', NULL, NULL, '2020-01-01 00:00:00', 0, 1, 0, 1, 0),
(30, NULL, 10, 'Skrytá prodejna', 'Skrytá prodejna', 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2014-01-01 00:00:00', 0, 0, 1, 1, 0);

-- Products: 1/2/41/42 turkey (type 3) and 47-50 tom (type 4) as in production; 100 meat per kg; 101 product per piece; 102 inactive
INSERT INTO `product` (`id`, `hide_on_old_web`, `order`, `code`, `price`, `half_of_product_id`, `name`, `type`, `can_be_packed_flag`, `can_be_sliced_flag`,
    `price_pack`, `text`, `text_additional`, `force_packing`, `packages_size`, `unit`, `weight_per_unit`, `ins_dt`, `active_flag`, `del_flag`) VALUES
(1, 0, 900, NULL, 123, NULL, 'Krůta celá s droby', 3, 0, 0, NULL, NULL, NULL, 0, '[]', 'ks', 1, '2014-01-01 00:00:00', 1, 0),
(2, 0, 890, NULL, 127, 1, 'Půlka krůty bez drobů', 3, 0, 0, NULL, NULL, NULL, 0, '[]', 'ks', 1, '2014-01-01 00:00:00', 1, 0),
(41, 0, 880, NULL, 129, NULL, 'Porcovaná krůta - celá s droby', 3, 0, 0, NULL, NULL, NULL, 0, '[]', 'ks', 1, '2014-01-01 00:00:00', 1, 0),
(42, 0, 870, NULL, 129, 41, 'Porcovaná krůta - půlka bez drobů', 3, 0, 0, NULL, NULL, NULL, 0, '[]', 'ks', 1, '2014-01-01 00:00:00', 1, 0),
(47, 0, 860, NULL, 123, NULL, 'Krocan celý s droby', 4, 0, 0, NULL, NULL, NULL, 0, '[]', 'ks', 1, '2014-01-01 00:00:00', 1, 0),
(48, 0, 850, NULL, 123, 47, 'Půlka krocana bez drobů', 4, 0, 0, NULL, NULL, NULL, 0, '[]', 'ks', 1, '2014-01-01 00:00:00', 1, 0),
(49, 0, 840, NULL, 123, NULL, 'Porcovaný krocan - celý s droby', 4, 0, 0, NULL, NULL, NULL, 0, '[]', 'ks', 1, '2014-01-01 00:00:00', 1, 0),
(50, 0, 830, NULL, 123, 49, 'Porcovaný krocan - půlka bez drobů', 4, 0, 0, NULL, NULL, NULL, 0, '[]', 'ks', 1, '2014-01-01 00:00:00', 1, 0),
(100, 0, 500, '100100', 289, NULL, 'Krůtí prsa', 1, 1, 1, 15, '<p>Krůtí prsní řízky</p>', '<p>Pod čarou</p>', 0, '[500, 1000]', 'kg', 1, '2014-01-01 00:00:00', 1, 0),
(101, 0, 400, '200100', 89, NULL, 'Krůtí šunka', 2, 0, 1, NULL, '<p>Šunka</p>', NULL, 0, '[]', 'ks', 0.3, '2014-01-01 00:00:00', 1, 0),
(102, 0, 300, '200200', 59, NULL, 'Krůtí paštika', 2, 0, 0, NULL, NULL, NULL, 0, '[]', 'ks', 0.2, '2014-01-01 00:00:00', 0, 0);

-- Users
INSERT INTO `user` (`id`, `name`, `email`, `password`, `preferred_place_id`, `phone`, `company_invoicing`, `is_admin`, `allow_wholesale`, `is_ceskakruta`, `ins_dt`, `active_flag`, `del_flag`) VALUES
(1, 'Admin Testovací', 'admin@example.test', MD5(CONCAT('test1', 'admin-password', 'test2')), NULL, '+420111111111', 0, 1, 0, 0, '2024-01-01 10:00:00', 1, 0),
(2, 'Zákazník Testovací', 'customer@example.test', MD5(CONCAT('test1', 'customer-password', 'test2')), 1, '+420222222222', 0, 0, 0, 0, '2024-01-02 10:00:00', 1, 0),
(3, 'Velkoobchod Testovací', 'wholesale@example.test', MD5(CONCAT('test1', 'wholesale-password', 'test2')), 22, '+420333333333', 1, 0, 1, 0, '2024-01-03 10:00:00', 1, 0);
