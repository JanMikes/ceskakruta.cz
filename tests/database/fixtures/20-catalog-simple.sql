-- Calendars, content, vendors, sortiment (PLAN §4.6 id range 2000–2999).
-- Rows used as edit targets by tests/Admin/{Calendar,Content,Vendor,Sortiment}; list ordering tests arrange their own rows.

INSERT INTO `calendar_dead` (`id`, `farma_doubrava`, `farma_rychvald`, `week_number`, `year`, `week_age`, `price_mix`, `weight`, `price_man`, `price_woman`, `note`, `ins_dt`) VALUES
(2001, 1, 0, 20, 2025, 5, '100', '2-3 kg', '110', '90', 'Fixture poznámka', '2025-01-01 08:00:00');

INSERT INTO `calendar_cold` (`id`, `product_tp_id`, `week_number`, `year`, `weight_from`, `weight_to`, `ins_dt`) VALUES
(2001, 1, 20, 2025, 5, 7, '2025-01-01 08:00:00');

INSERT INTO `calendar_alive` (`id`, `product_tp_id`, `farma_rychvald`, `farma_doubrava`, `week_number`, `year`, `weight_from`, `weight_to`, `ins_dt`) VALUES
(2001, 2, 0, 1, 20, 2025, 9, 11, '2025-01-01 08:00:00');

INSERT INTO `content` (`id`, `order`, `code`, `name`, `text`, `ins_dt`) VALUES
(2001, 20010, 'test_editable', 'Editovatelná sekce', '<p>Původní text</p>', '2025-01-01 08:00:00');

INSERT INTO `vendor` (`id`, `order`, `place`, `name`, `address`, `city`, `zip`, `email`, `web`, `phone`, `ins_dt`) VALUES
(2001, 20010, 'Brno', 'Fixture prodejce', 'Krátká 2', 'Brno', '60200', 'fixture-vendor@example.test', 'https://fixture-vendor.example.test', '+420555555555', '2025-01-01 08:00:00');

INSERT INTO `sortiment` (`id`, `category_id`, `order`, `name`, `text`, `ins_dt`) VALUES
(2001, 4, 20010, 'Fixture sortiment', '<p>Fixture text</p>', '2025-01-01 08:00:00');
