-- Orders section fixtures (id range 5000–5999, PLAN §4.6). Synthetic data only.
-- 5901–5906: hand-made orders, the SAME data the Pohoda/Boxxi golden files were rendered from with the old app
--            (tests/fixtures/pohoda/_renderer/orders.json) → keep both in sync.
--   5901 shop place 1, cash, 2 items, source set ("Nový web")
--   5902 delivery place 7, cash, empty note, half turkey (no split outside CoolBalík), source NULL
--   5903 CoolBalík place 21, card, Fri 2024-06-21 → expedition Thu → split WEB5903 + WEB5903K; items inserted in reverse of detail sort order
--   5904 shop place 1, coupon LETO10 -10 %
--   5905 CoolBalík place 21, card, Tue 2024-06-25 → expedition Mon → no split; note with quotes and ampersand
--   5906 CoolBalík place 21, already synced to Pohoda and Boxxi
-- 5001–5060: generated orders "Strankovani NN" for pagination (place 1, unsynced, no items).
-- user_id is NULL on purpose (does not change order counts asserted by the users section).

INSERT INTO `order` (`id`, `user_id`, `place_id`, `date`, `email`, `phone`, `name`, `pay_by_card`, `delivery_street`, `delivery_city`, `delivery_postal_code`,
    `note`, `price_total`, `source`, `coupon_code`, `coupon_percent_value`, `price_total_before_discount`,
    `pohoda_sync_dt`, `boxxi_sync_dt`, `cool_balik_price`, `cool_balik_box_count`, `ins_dt`) VALUES
(5901, NULL, 1, '2024-06-14', 'jana.novakova@example.test', '+420600100200', 'Jana Nováková', 0, NULL, NULL, NULL,
    'Vyzvednu odpoledne', 845, 'web', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-06-10 08:15:00'),
(5902, NULL, 7, '2024-06-19', 'petr.svoboda@example.test', '+420600300400', 'Petr Svoboda', 0, 'Hlavní 12', 'Přerov', '75002',
    '', 1728, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-06-12 19:40:00'),
(5903, NULL, 21, '2024-06-21', 'marie.dvorakova@example.test', '+420600500600', 'Marie Dvořáková', 1, 'Školní 7', 'Olomouc', '77900',
    'Doručit po 16. hodině', 1467, 'web', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-06-14 07:05:00'),
(5904, NULL, 1, '2024-06-18', 'eva.mala@example.test', '+420600700800', 'Eva Malá', 0, NULL, NULL, NULL,
    'Děkuji', 520, 'web', 'LETO10', 10, 578, NULL, NULL, NULL, NULL, '2024-06-11 12:00:00'),
(5905, NULL, 21, '2024-06-25', 'tomas.cerny@example.test', '+420600900100', 'Tomáš Černý', 1, 'Nádražní 3', 'Ostrava', '70200',
    'Zvonit u "Černých" & nechat u dveří', 4487, 'web', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-06-18 21:30:00'),
(5906, NULL, 21, '2024-06-21', 'karel@example.test', '+420601000200', 'Karel Synchronizovaný', 0, 'Polní 1', 'Opava', '74601',
    '', 1445, 'web', NULL, NULL, NULL, '2024-06-15 09:00:00', '2024-06-20 10:30:00', 1500, 1, '2024-06-13 10:00:00');

INSERT INTO `order_item` (`id`, `order_id`, `product_id`, `product_tp_id`, `is_sliced`, `is_packed`, `amount`, `price_per_unit`, `price_packing`, `price_total`,
    `weight_from`, `weight_to`, `ins_dt`) VALUES
(5901, 5901, 100, NULL, 0, 0, 2, 289, 0, 578, NULL, NULL, '2024-06-10 08:15:00'),
(5902, 5901, 101, NULL, 0, 0, 3, 89, 0, 267, NULL, NULL, '2024-06-10 08:15:00'),
(5903, 5902, 100, NULL, 0, 0, 4, 289, 0, 1156, NULL, NULL, '2024-06-12 19:40:00'),
(5904, 5902, 2, 1, 0, 0, 1, 127, 0, 572, 4, 5, '2024-06-12 19:40:00'),
(5905, 5903, 101, NULL, 0, 0, 1, 89, 0, 89, NULL, NULL, '2024-06-14 07:05:00'),
(5906, 5903, 100, NULL, 1, 1, 2, 289, 0, 578, NULL, NULL, '2024-06-14 07:05:00'),
(5907, 5903, 1, 1, 0, 0, 1, 123, 0, 800, 6, 7, '2024-06-14 07:05:00'),
(5908, 5904, 100, NULL, 0, 0, 2, 289, 0, 578, NULL, NULL, '2024-06-11 12:00:00'),
(5909, 5905, 41, 2, 0, 0, 1, 129, 0, 1419, 10, 12, '2024-06-18 21:30:00'),
(5910, 5905, 101, NULL, 0, 0, 2, 89, 0, 178, NULL, NULL, '2024-06-18 21:30:00'),
(5911, 5905, 100, NULL, 0, 0, 10, 289, 0, 2890, NULL, NULL, '2024-06-18 21:30:00'),
(5912, 5906, 100, NULL, 0, 0, 5, 289, 0, 1445, NULL, NULL, '2024-06-13 10:00:00');

-- 60 orders for pagination: ids 5001–5060, dates 2025-01-02 … 2025-03-02 (outside every date filter used in tests)
INSERT INTO `order` (`id`, `user_id`, `place_id`, `date`, `email`, `phone`, `name`, `pay_by_card`, `note`, `price_total`, `ins_dt`)
SELECT 5000 + seq, NULL, 1, DATE_ADD('2025-01-01', INTERVAL seq DAY), CONCAT('strankovani', seq, '@example.test'), '+420600000000',
    CONCAT('Strankovani ', LPAD(seq, 2, '0')), 0, '', 100, DATE_ADD('2025-01-01 10:00:00', INTERVAL seq HOUR)
FROM seq_1_to_60;
