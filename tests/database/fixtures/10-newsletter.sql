-- Aktuality + newsletter (PLAN §2.4, §2.5, §2.14), id range 1000-1999. Synthetic data only.

-- Actualities: list sorted by date DESC → 1001 (2024-09-15), [base 1 (2024-05-01)], 1000 (2024-03-01), 1003 (2023-12-20); 1002 is deleted
INSERT INTO `actuality` (`id`, `date`, `name`, `text`, `text_full`, `ins_dt`, `active_flag`, `del_flag`) VALUES
(1000, '2024-03-01', 'Jarní akce', '<p>Jarní text do emailu</p>', '<p>Jarní text na web</p>', '2024-03-01 08:00:00', 1, 0),
(1001, '2024-09-15', 'Podzimní nabídka', '<p>Podzimní email</p>', '<p>Podzimní web</p>', '2024-09-15 08:00:00', 1, 0),
(1002, '2025-01-01', 'Smazaná aktualita', '<p>Smazáno</p>', '<p>Smazáno</p>', '2025-01-01 08:00:00', 1, 1),
(1003, '2023-12-20', 'Vánoce 2023', '<p>Vánoční email</p>', '<p>Vánoční web</p>', '2023-12-20 08:00:00', 0, 0);

-- Subscribers: list sorted ins_dt DESC → inactive (1003), marek (1002), adam (1001), zuzana (1000), [base subscriber (1)]; 1004 is deleted
INSERT INTO `newsletter` (`id`, `email`, `ins_dt`, `active_flag`, `del_flag`) VALUES
(1000, 'zuzana@example.test', '2024-02-01 10:00:00', 1, 0),
(1001, 'adam@example.test', '2024-03-05 12:34:56', 1, 0),
(1002, 'marek@example.test', '2024-04-01 09:00:00', 1, 0),
(1003, 'inactive@example.test', '2024-04-02 09:00:00', 0, 0),
(1004, 'deleted@example.test', '2024-04-03 09:00:00', 1, 1);
