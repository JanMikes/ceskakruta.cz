-- Places & users section fixtures (PLAN §2.15, §2.16, §4.6), id range 4000-4999.
-- Closed days are relative to "today" -> inserted inside the tests, not here.

-- Places (orders 4500.. are higher than base fixtures -> listed first with `order DESC`)
-- 4001 = shop with days 1 (2 before, 12:00), 3 (0 before, 18:30), 7 (9 before, 08:15); disabled days stored as NULL/NULL
-- 4002 = hidden in admin, 4003 = CoolBalík delivery, 4004 = deleted, 4005 = inactive
INSERT INTO `place` (`id`, `is_cool_balik`, `order`, `name`, `title`, `force_delivery_address`, `force_packing`, `description`,
    `allow_order_day_1_days_before`, `allow_order_day_1_time`, `allow_order_day_2_days_before`, `allow_order_day_2_time`,
    `allow_order_day_3_days_before`, `allow_order_day_3_time`, `allow_order_day_4_days_before`, `allow_order_day_4_time`,
    `allow_order_day_5_days_before`, `allow_order_day_5_time`, `allow_order_day_6_days_before`, `allow_order_day_6_time`,
    `allow_order_day_7_days_before`, `allow_order_day_7_time`,
    `opening_hours`, `opening_hours_email`, `ins_dt`, `show_on_old_web`, `allow_only_wholesale`, `hide_in_admin`, `active_flag`, `del_flag`) VALUES
(4001, 0, 4500, 'PU Prodejna Dny', 'PU Prodejna Dny, Dlouhá 1', 0, 0, '<p>Popis prodejny</p>',
    2, '12:00:00', NULL, NULL, 0, '18:30:00', NULL, NULL, NULL, NULL, NULL, NULL, 9, '08:15:00',
    'Po-Pá 8-12', 'Po-Pá 8-12 (email)', '2024-01-01 00:00:00', 0, 0, 0, 1, 0),
(4002, 0, 4400, 'PU Skrytá prodejna', 'PU Skrytá prodejna', 0, 0, NULL,
    NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL,
    NULL, NULL, '2024-01-01 00:00:00', 0, 0, 1, 1, 0),
(4003, 1, 4300, 'PU Rozvoz Cool', 'PU Rozvoz Cool, celá ČR', 1, 1, NULL,
    NULL, NULL, 1, '10:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL,
    NULL, NULL, '2024-01-01 00:00:00', 0, 0, 0, 1, 0),
(4004, 0, 4200, 'PU Smazaná prodejna', 'PU Smazaná prodejna', 0, 0, NULL,
    NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL,
    NULL, NULL, '2024-01-01 00:00:00', 0, 0, 0, 1, 1),
(4005, 0, 4100, 'PU Neaktivní prodejna', 'PU Neaktivní prodejna', 0, 0, NULL,
    NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL,
    NULL, NULL, '2024-01-01 00:00:00', 0, 0, 0, 0, 0);

-- Per-place prices of 4001 (source for "Převzít ceny z prodejny"): 2 active rows, 1 inactive, 1 deleted
INSERT INTO `product_place` (`id`, `product_id`, `place_id`, `price`, `active_flag`, `del_flag`, `ins_dt`) VALUES
(4001, 100, 4001, 300, 1, 0, '2024-01-01 00:00:00'),
(4002, 101, 4001, 95, 1, 0, '2024-01-01 00:00:00'),
(4003, 1, 4001, 130, 0, 0, '2024-01-01 00:00:00'),
(4004, 2, 4001, 140, 1, 1, '2024-01-01 00:00:00');

-- Users (password = MD5(CONCAT('test1', <plain>, 'test2')), see fixtures.sql)
-- 4001 = 3 orders + bans, 4002 = admin, 4003 = no wholesale, 4004 = wholesale, 4005 = edit target
INSERT INTO `user` (`id`, `name`, `email`, `password`, `preferred_place_id`, `phone`, `delivery_street`, `delivery_city`, `delivery_zip`,
    `company_invoicing`, `company_name`, `company_id`, `company_vat_id`, `invoicing_street`, `invoicing_city`, `invoicing_zip`,
    `is_admin`, `allow_wholesale`, `is_ceskakruta`, `ins_dt`, `active_flag`, `del_flag`) VALUES
(4001, 'PU Objednávající', 'pu-orders@places-users.test', MD5(CONCAT('test1', 'orders-password', 'test2')), 1, '+420600000001', NULL, NULL, NULL,
    0, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, '2025-03-04 05:06:07', 1, 0),
(4002, 'PU Druhý Admin', 'pu-admin@places-users.test', MD5(CONCAT('test1', 'second-admin-password', 'test2')), NULL, '+420600000002', NULL, NULL, NULL,
    0, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, '2025-03-05 10:00:00', 1, 0),
(4003, 'PU Maloobchod', 'pu-plain@places-users.test', MD5(CONCAT('test1', 'plain-password', 'test2')), NULL, '+420600000003', NULL, NULL, NULL,
    0, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, '2025-03-06 10:00:00', 1, 0),
(4004, 'PU Velkoobchod', 'pu-wholesale@places-users.test', MD5(CONCAT('test1', 'wholesale2-password', 'test2')), 22, '+420600000004', NULL, NULL, NULL,
    1, 'PU Firma s.r.o.', '12345678', 'CZ12345678', 'Firemní 1', 'Brno', '60200', 0, 1, 0, '2025-03-07 10:00:00', 1, 0),
(4005, 'PU Editovaný', 'pu-edit@places-users.test', MD5(CONCAT('test1', 'edit-password', 'test2')), 7, '+420600000005', 'Stará 5', 'Přerov', '75002',
    0, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, '2025-03-08 10:00:00', 1, 0);

INSERT INTO `order` (`id`, `user_id`, `place_id`, `date`, `email`, `phone`, `name`, `pay_by_card`, `note`, `price_total`, `ins_dt`) VALUES
(4001, 4001, 1, '2025-04-01', 'pu-orders@places-users.test', '+420600000001', 'PU Objednávající', 0, '', 100, '2025-03-20 10:00:00'),
(4002, 4001, 1, '2025-04-08', 'pu-orders@places-users.test', '+420600000001', 'PU Objednávající', 0, '', 200, '2025-03-27 10:00:00'),
(4003, 4001, 7, '2025-04-15', 'pu-orders@places-users.test', '+420600000001', 'PU Objednávající', 1, '', 300, '2025-04-03 10:00:00');

-- Bans of 4001: active 100 + 1 (counted, pre-checked), inactive 41 and deleted 42 (not counted); 4002 has one ban that must survive edits of 4001
INSERT INTO `user_banned_product` (`id`, `user_id`, `product_id`, `active_flag`, `del_flag`, `ins_dt`) VALUES
(4001, 4001, 100, 1, 0, '2025-01-01 00:00:00'),
(4002, 4001, 1, 1, 0, '2025-01-01 00:00:00'),
(4003, 4001, 41, 0, 0, '2025-01-01 00:00:00'),
(4004, 4001, 42, 1, 1, '2025-01-01 00:00:00'),
(4005, 4002, 100, 1, 0, '2025-01-01 00:00:00');
