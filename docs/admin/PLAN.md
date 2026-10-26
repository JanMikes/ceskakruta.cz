# Admin migration: Nette `BackendModule` → Symfony `/admin`

Goal: rebuild every feature of the old Nette admin (`~/www/ceska-kruta/app/BackendModule`) in this Symfony app,
with a new design, CQRS (form submit → controller → Messenger command → sync handler), and PHPUnit coverage,
so the Nette app (old.ceskakruta.cz) can be switched off completely.

Status legend used in checklists: `[ ]` todo, `[x]` done.

## 0. Hard constraints
1. **Shared database, no schema changes.** The old Nette app and this app use the same production DB. Until the old app is off:
   no new tables/columns, no type changes, no migrations. Only read/write existing columns.
2. **Data written by the new admin must stay readable by the old app** during the parallel run
   (e.g. keep `photo.path = 'upload/recipes'`, keep `order` semantics, keep `packages_size` JSON format, keep MD5 password hashes).
3. **Nothing is deleted on the old hosting** for now; old.ceskakruta.cz will later only get a 301 to ceskakruta.cz.
4. **Parity, not redesign of features**: replicate what the old admin could do (+ the few explicit extras decided in §6).
5. **Production DB facts** (from the 2026-10-26 dump, imported only into a throwaway local container, since removed):
   - **MariaDB 10.11.19** in production. ✅ Done 2026-10-26: local `docker-compose.yml` switched `mariadb:10.1` → `mariadb:10.11`
     on a **new volume `mariadb-10-11-data`** (old `ceskakrutacz_mariadb-data` volume kept untouched for rollback), local data restored
     from a backup dump, `.env` `serverVersion` → `10.11.19-MariaDB`.
     10.11's default `sql_mode` includes **`STRICT_TRANS_TABLES`** (10.1's doesn't) → why commit 0ce4cfc was needed; tests run strict too.
   - **Production schema is the source of truth.** Local drift fixed 2026-10-26 (local `mariadb-dump --no-data` is now identical to prod):
     `newsletter.email` → `varchar(200)`; `place.allow_only_wholesale` → nullable signed `tinyint(1) DEFAULT 0` after `description`;
     `product.unit` → `NOT NULL` **without default** (prod has `COMMENT 'kg'` instead of a default → inserts must always set `unit`);
     added prod's duplicate FK `order_ibfk_5` (coupon); `order_item_ibfk_1` → `ON DELETE CASCADE`; dropped local-only `doctrine_migration_versions`.
     Charsets/collations/engines/indexes were already identical.
   - Sizes: 15 729 orders (3 not synced to Pohoda), 862 users (5 admins), 6 828 active newsletter subscribers.
   - The dump contains customer PII → never commit it or anything derived with data; only the `--no-data` schema is used for tests.

---

## 1. Scope: what exists in the old app

### 1.1 What is live vs. dead code

| Old presenter | Status | Notes |
|---|---|---|
| `Sign` | **live** | login form (email + password + "persistent" checkbox) |
| `Dashboard` | live (empty page) | template has no content |
| `User:changePassword` | **live** | own password change |
| `Actuality` | **live** | + enqueues newsletter on create |
| `CalendarDead` ("Rozkrmená krůťata") | **live** | frontend reads it (`GetAliveCalendar`) |
| `CalendarCold` ("Chlazené krůty") | **live** | frontend reads it (`GetColdProductsCalendar`) |
| `CalendarAlive` ("Živé krůty") | live in old admin, unused by new frontend | **port** (customer may drop later) |
| `Product` ("Maso a výrobky") | **live** | |
| `Recipe` (per product) | **live** | photo upload |
| `Sortiment` ("Doplňkový sortiment") | live in old admin, unused by new frontend | **port** |
| `Content` ("Texty") | **live** | frontend `ContentService` reads by `code` |
| `Vendor` ("Prodejci") | live in old admin, unused by new frontend | **port** |
| `Email` (newsletter subscribers) | **live** | |
| `Place` ("Prodejny") + closed days | **live** | |
| `Users` + banned products + wholesale | **live** | |
| `Order` (list, filter, detail, CSV, Pohoda, Boxxi) | **live** | the most important section |
| `ProductCategory`, `ProductPhoto`, `ProductVariant`, `RecommendedProduct` | **dead** | reference factories/entities/templates that don't exist → do NOT port |
| `CoolBalik` API sending | **dead** | fully commented out, replaced by Boxxi → do NOT port |
| KCFinder (CKEditor file browser) | **dead** | `'disabled' => true` and never enabled via session → do NOT port |
| Coupons | **never existed** in old admin | `coupon` table managed directly in DB → not in scope |

### 1.2 Cron
The only cron in the old app is **`Frontend:Cron:sendEmail`** (URL `/cron/send-email`) — the newsletter sender.
No other cron module exists. The new app already has `/cron/recurring-order-trigger` (unrelated, keep).

**Production scheduling (checked in `~/www/lily.srv`)**: ceskakruta.cz is hosted by a third party (95.80.214.220, no CLI),
so jobs are fired by `curl` from lily: `apps/ceskakruta/cron.d/ceskakruta` → `*/2 2-20 * * *` `lily-cron-run ceskakruta
recurring-order-trigger -- curl -fsS --max-time 60 https://ceskakruta.cz/cron/recurring-order-trigger` (D79, moved from spare 2026-10-15).
**Newsletter cron is live, but not on lily** (verified in the production dump, 2026-10-26): queue backlog = **0**,
actuality #48 (2026-10-05) was sent to 6 837 subscribers, last batch 2026-10-10 06:00; batch timestamps (`…:00:04`, `…:20:02`,
`…:40:03`, around the clock) show a **~10-minute trigger**. Production volume: 6 828 active subscribers, 48 actualities, 135 653 sent queue rows.

The trigger is a crontab line **on the production VPS** (Q13 resolved):
`*/10 * * * *  /usr/bin/curl -o /dev/null 'https://old.ceskakruta.cz/cron/send-email' >/dev/null 2>&1`
→ hits the old Nette app (200). Errors are fully silenced (`-o /dev/null`, no `-f`) → a failure would go unnoticed.
Until the new endpoint is deployed this line must keep pointing to `old.ceskakruta.cz` (`ceskakruta.cz/cron/send-email` is a 404 today).

### 1.3 Hidden dependencies on the old app (must be solved before switching it off)
1. **Recipe images are hot-linked from the old domain**: `templates/recipes.html.twig:20` and
   `templates/recipe_detail.html.twig:14` use `https://old.ceskakruta.cz/{{ recipe.path }}/{{ recipe.filename }}`.
   Production: 10 active recipes with a photo, 12 `photo` rows under `upload/recipes/` (the 3 `img/photogallery/3` photos are not
   referenced by any recipe). The files exist only on the old app's hosting → copy the whole `www/upload/recipes/` dir manually via FTP (§3.9).
2. **`public/.htaccess` redirects `/admin*` → `https://old.ceskakruta.cz/admin/sign/in` with a 301.**
   Removed when the new admin goes live (§3.10). Browsers cache 301s → switch it to 302 as soon as possible.
3. **Newsletter cron** must be moved from the old URL to the new trigger (§3.8) — gap-first, never both at once.
4. **Secrets** must be put into production `.env.local` (§3.11).
5. No HTML content (content/actuality/product/sortiment/place/recipe texts) references old upload paths (checked in the DB) ✅.

### 1.4 Security issues in the old admin (do NOT replicate)
- **Any logged-in user can access the admin**: `SecuredPresenter` only checks `isLoggedIn()` and `Authorizator::isAllowed()` returns `true`.
  The new admin requires `ROLE_ADMIN` (`user.is_admin = 1`, already mapped in `UserProvider`).
- All grid actions (delete, activate, reorder, wholesale toggle, Pohoda send) are **GET links without CSRF** → new: POST + CSRF token.
- Hard-coded secrets (Pohoda basic auth, Boxxi API key, reCAPTCHA, Google Maps key) → env.
- Passwords are salted MD5 — must stay as-is while the old app shares the DB (constraint 2), see §8.

---

## 2. Feature inventory (exact behaviour to replicate)

Common behaviour in the old grids (`TRecordHandlers` + `Entity`), referred to below as **[A] [D] [AA] [X] [M]**:
- **[A]/[D] Activate / Deactivate** a row → `active_flag = 1/0`, flash "Záznam úspěšně aktivován/deaktivován".
- **[AA] Activate all** → `active_flag = 1` for all inactive non-deleted rows; button disabled when there are 0 inactive rows.
- **[X] Delete** → soft delete `del_flag = 1` (with JS confirm "Opravdu smazat?"), flash "Záznam úspěšně smazán".
- **[M] Move up / down** (`changeRowOrder`): list sorted by `order DESC`; if row's `order` is empty, set it to `id*10` first;
  swap `order` with the nearest row in the direction (UP = nearest `order >=`, DOWN = nearest `order <=`); if equal, ±1.
  Optional scope condition (recipes per product, sortiment per category). Up arrow hidden on first row, down on last row.
  Redirect back with anchor `#item-{id}`.
- All lists exclude `del_flag = 1` rows. Inserts set `ins_dt`, `ins_process`; updates set `upd_process`.
  Orderable tables (`content`, `vendor`, `sortiment`, `sortiment_category`, `place`) get `order = id*10` after insert.
- Forms: "Přidat"/"Upravit" submit → redirect to list (`#item-{id}`); on edit also "Uložit a pokračovat v editaci" → stay on form.
  Flash "Záznam byl úspěšně uložen!". Missing record on edit → redirect to list.
- WYSIWYG (CKEditor 4, `allowedContent = true`, styles: Nadpis h2, Podnadpis h3, Odstavec p, Menší text span.small) on rich text fields.

### 2.1 Authentication
- [ ] Login page: email ("Uživatelské jméno"), password, checkbox "Trvalé přihlášení" (14 days, otherwise session 1 h).
- [ ] Invalid credentials → "Byly zadány neplatné přihlašovací údaje!".
- [ ] After login redirect to originally requested admin URL (backlink) or dashboard.
- [ ] Already logged-in admin visiting login → redirect to dashboard.
- [ ] Logout (navbar), redirect to login.
- [ ] Navbar shows logged admin's name, links to change password.
- [ ] **New**: only `ROLE_ADMIN` may log in / access; non-admin gets refused.

### 2.2 Change own password (`User:changePassword`)
- [ ] Fields: old password (required, min 4), new password (required, min 4, max 20), new password again (must equal).
- [ ] Wrong old password → error on field "Bylo zadáno chybné staré heslo!".
- [ ] Success → hash stored (same MD5 hasher, constraint 2), user stays logged in, flash "Heslo bylo úspěšně změněno!".

### 2.3 Dashboard
- [ ] Empty in old app. New: landing page (optionally a few counters, e.g. orders not synced to Pohoda).

### 2.4 Aktuality (`actuality`)
- List sorted `date DESC`; columns: Datum (d.m.Y), Nadpis; [A]/[D], Edit, [X], [AA].
- Form: `date` (required date), `name` "Nadpis" (required, max 50), `text` "Text email" (WYSIWYG), `text_full` "Text web" (WYSIWYG).
- **On create**: for every newsletter subscriber (`newsletter` active & not deleted) insert `actuality_newsletter (actuality_id, email)`;
  duplicates ignored (unique index `email, actuality_id` → `INSERT IGNORE`).
- Edit page title includes actuality name.

### 2.5 Newsletter cron (`Cron:sendEmail`)
Each run:
1. **Fill missing**: take the latest actuality (`ORDER BY id DESC LIMIT 1`); find up to 50 distinct emails from `newsletter`
   (`del_flag=0, active_flag=1, ins_dt < actuality.ins_dt`) not yet in `actuality_newsletter` for that actuality → insert them.
2. **Send** next 10 rows from `actuality_newsletter` (`is_sent_flag=0, active_flag=1, del_flag=0`):
   subject = actuality name, body = newsletter template (logo, `<h1>` name, `actuality.text` raw HTML, unsubscribe footer),
   unsubscribe link `https://ceskakruta.cz/odhlasit-newsletter/{email}/{md5('unsubscribe'.email.'email')}`
   (the new app already has route `unsubscribe_newsletter` with the same hash ✅).
3. Mark row `is_sent_flag = 1`; log each send.

### 2.6 Rozkrmená krůťata (`calendar_dead`)
- List sorted `year ASC, week_number ASC, id DESC`; columns: Týden "W/YYYY (d.m. – d.m. YYYY)" (ISO week Mon–Sun),
  Farma Doubrava Ano/Ne, Farma Rychvald Ano/Ne, Stáří v týdnech; [A]/[D], Edit, [X], [AA].
- Form: `farma_doubrava`, `farma_rychvald` (checkboxes), `week_number` (required int 1–53), `year` (required int 2014…current+2, default current),
  `week_age` (optional int), `weight` "Přibližná váha" (text 20), `price_mix` "Cena mix", `price_man` "Cena krocan", `price_woman` "Cena krůta" (text 20), `note` (text 40).

### 2.7 Chlazené krůty (`calendar_cold`)
- List sorted `year, week_number ASC, product_tp_id DESC, id DESC`; columns: Týden, Produkt (`lst_product_tp.name`), Váha "X až Y"; [A]/[D], Edit, [X], [AA].
- Form: `product_tp_id` (required select from `lst_product_tp`, prompt "-- Vyberte --"), `week_number`, `year` (as above),
  `weight_from`, `weight_to` (required float).

### 2.8 Živé krůty (`calendar_alive`)
- List like cold + Farma Doubrava / Farma Rychvald columns.
- Form: cold fields + `farma_doubrava`, `farma_rychvald`.

### 2.9 Produkty – Maso a výrobky (`product`)
- List sorted `order DESC`; columns: Název, Cena/kg "{price} Kč", button "Recepty (n)" (count of non-deleted recipes) → recipes list,
  [A]/[D] (**hidden for type 3, 4** = whole turkey products), [M], Edit, [X] (**hidden for type 3, 4**), [AA].
- Form for create, and edit of type 1/2:
  `name` (required, max 50), `type` (required: 1 Maso, 2 Výrobky), `unit` (ks|kg, default kg), `weight_per_unit` "Váha vč. balení v KG"
  (float, default 1, help "U produktů na váhu nechat 1"), `price` (int), `code` "Kód produktu (do Pohody)",
  `can_be_sliced_flag`, `can_be_packed_flag` (toggles visibility of `price_pack` + `force_packing`), `force_packing`,
  `price_pack` (optional int), `text_additional` "Text pod čarou" (WYSIWYG), `text` "Popis" (WYSIWYG).
- Edit of **type 3/4**: only `price`, `code`, `text` (unit/weight untouched).
- Save rules: empty `price_pack` → NULL; `can_be_packed_flag = false` → `force_packing = false`.
- **Extra (decided)**: `packages_size` "Velikosti balení (g)" editable for type 1/2 — input "500, 1000, 1500" (positive ints, deduplicated,
  sorted ascending), stored exactly as today: JSON int array `[500,1000,1500]`, empty → `[]` (frontend `GetProducts` does
  `json_decode` → `array<int>`; 51 products already have values).
- Not editable (kept as-is): `half_of_product_id`; per-place prices `product_place` (old form had a dead `prices` default, no fields).

### 2.10 Recepty (`recipe`, per product)
- Route requires existing product, otherwise redirect to products list. Back button to products.
- List sorted `name ASC`; columns: Název, Obrázek (thumbnail); [A]/[D], Edit, [X], [AA].
- Form: `name` (required, max 50), `image` "Foto" (optional upload, JPG/GIF/PNG; current image preview on edit), `text` (WYSIWYG).
- Upload: store under `upload/recipes/{uniqid}-{recipeId}.{ext}`, insert `photo (filename, path, width, height)`, set `recipe.photo_id`.
  (Old app also generated thumbnails `admin`, `recipe`, `recipe-list`; the new frontend only uses the original → thumbnails not needed.)

### 2.11 Doplňkový sortiment (`sortiment`, `sortiment_category`)
- Index: buttons for each category (categories also listed in navbar dropdown). No category management.
- Per category list sorted `order DESC`: Název; [A]/[D], [M] (**up arrow hidden for first 3 rows**), Edit, [X] (**hidden for ids 1, 2**), [AA].
- Form: `name` (required), `text` (WYSIWYG); `category_id` from URL.

### 2.12 Texty (`content`)
- List sorted `order DESC`: Sekce (name), Edit. ("Přidat" button commented out in old UI → no create.)
- Edit form: **only `text`** (WYSIWYG).

### 2.13 Prodejci (`vendor`)
- List sorted `order DESC`: Název prodejce, Město; [A]/[D], [M], Edit, [X], [AA].
- Form: `place` "Místo", `name`, `address`, `city` (max 50), `zip` (max 5), `email` (optional, valid email), `web`, `phone`.

### 2.14 Emaily (`newsletter`)
- List sorted `email ASC`: Email, Přidáno (d.m.Y H:i:s); [X].

### 2.15 Prodejny (`place`, `place_closed_day`)
One page with two lists:
- **Closed days**: rows with `date >= today − 2 days`, sorted `date ASC`: Datum zavřeno, Prodejna (name or **"Všechny"** when `place_id IS NULL`); [X].
  Button "Přidat výjimku" → form: `date` (required), `place_id` (optional select, prompt "- Všechny prodejny -", places ordered `order DESC`).
- **Places**: `hide_in_admin = 0`, sorted `order DESC`: Název, Rozvoz Ano/Ne (+ "(Cool balík)"), 7 columns Mon–Sun:
  "Povoleno / Dnů před: N / (resulting weekday HH:MM)" or "-"; [A]/[D], [M], Edit, [AA]. No delete.
- Form: on **create only** `prices_from_place_id` "Převzít ceny z prodejny" (copies active `product_place` rows — kept, decided);
  `name` "Název krátký" (required), `title` "Název dlouhý" (required), `allow_only_wholesale`, `force_delivery_address` "Je rozvoz?",
  `is_cool_balik`, `force_packing`; for each day 1–7: `enable_day_N` checkbox (default = `days_before IS NOT NULL`, toggles inputs),
  `allow_order_day_N_days_before` (int), `allow_order_day_N_time` (HH:MM, default 23:59);
  `description` "Text do sekce Prodejny" (WYSIWYG), `opening_hours` "Otevírací doba (web)", `opening_hours_email` "Otevírací doba (e-mail)".
- Save: disabled day → both `days_before` and `time` NULL.
- JS helper: next to "days before" input show resulting weekday name (`day − daysBefore`, wrapped 1–7); HH:MM validation.
- Not editable in old admin (`address_1..3`, `map_url`, `phone`) → out of scope.

### 2.16 Uživatelé (`user`, `user_banned_product`)
- List sorted `id DESC`: ID, Registrace (d.m.Y H:i), E-mail, Jméno, Počet objednávek (count of orders by `user_id`),
  "N zakázaných" button → banned products page, Velkoobchod "Povolen + [Zakázat]" / "Nepovolen + [Povolit]", Upravit.
- Wholesale toggle → `allow_wholesale`, flash "Velkoprodej byl povolen/zakázán pro uživatele: {email}".
- Edit form (no create, no delete): `email` (required, valid), `name`, `phone`, `preferred_place_id` "Doprava"
  (places `title` ordered `order DESC`, **plus option id 22 labelled "Rozvoz Česká Krůta - Velkoobchod všechny dny"**),
  `delivery_street`, `delivery_city`, `delivery_zip`, `company_invoicing`, `company_name`, `company_id` (IČO), `company_vat_id` (DIČ),
  `invoicing_street`, `invoicing_city`, `invoicing_zip`, `allow_wholesale`, `is_ceskakruta` "Uživatel/pobočka České Krůty".
- **Extra (decided)**: `is_admin` "Administrátor" checkbox; an admin cannot remove their own admin flag (form + handler guard);
  admins visible in the list (badge). Note: in the old app `is_admin` meant nothing (any user could log in to admin).
- Banned products page: checkbox list of all active products (sorted by name), pre-checked = currently banned (`active_flag=1`);
  save = hard delete all rows for user + insert one row per checked product. Count in list = active & non-deleted bans.

### 2.17 Objednávky (`order`, `order_item`)
- **List** sorted `id DESC`:
  - Filter (GET): `date_from`, `date_to` (on delivery `date`), `customer_name` (LIKE %…%); "Filtrovat" + "Resetovat".
  - **Extra (decided)**: pagination (50 per page; filter kept across pages; total count shown). Old app rendered all orders.
  - Columns: checkbox (only when not synced to Pohoda), Č. (+ "Nový web" badge when `source` is set), Datum provedení (`ins_dt`),
    Zákazník (name, street, city, zip), Cena ("(Sleva -X%)" when coupon + `price_total`), Místo (place name), Datum (`date`), Poznámka,
    Pohoda (sync datetime, or "Nepropsáno" + "Propsat" button), Boxxi (only for `place.is_cool_balik`: sync datetime + `cool_balik_price` Kč,
    or "Nepropsáno" + "Propsat" → Boxxi page), Detail.
  - Bulk: "select all" checkbox with counter "(checked/total)" (current page), button "Označené poslat do Pohody".
- **CSV export** (button "CSV export", `id DESC`): header `Číslo, Datum provedení, Zákazník, Cena, Místo, Datum, Poznámka`;
  Zákazník = "name, street, city zip"; filename `ceska-kruta-objednavky.csv`.
  **Extra (decided)**: export respects the active filter (all pages). Old app ignored it.
- **Detail**: Č. (+ "Nový web"), Datum provedení, Zákazník (name, phone, email, street, city zip), Sleva (coupon code + -X% or "-"),
  Cena celkem, Platba kartou Ano/Ne, Místo, Datum, Poznámka; items sorted `product_tp_id DESC, product_id ASC`:
  Položka, Cena j. ("{price} Kč / kg|unit"), Počet ("{amount} {unit}"), Váha ("from - to kg" or "-"), Cena celkem.
- **Pohoda send** (single + bulk), `Pohoda::sendOrderRequest`:
  - For delivery places (`force_delivery_address`): search address in Pohoda address book (XML filter by name/city/street/zip);
    if not found, create it; use returned `id` as `partnerIdentity`.
  - `dateTo` (expedition) and `dateFrom` (slaughter) calculation — exact rules in old `libs/services/Pohoda.php`
    (`calculateDateTo`, `calculateDateFrom`; special cases for CoolBalík places and for shops other than place 1 without delivery).
  - Split: for CoolBalík places when expedition weekday is Tue or Thu, items with `weight_from` (whole turkeys) go into a second
    order `WEB{id}K` with its own `dateFrom`; the rest goes into `WEB{id}`.
  - XML (`order.xml.latte`): priceLevel BALÍK/ROZVOZ for delivery, centre 200, note "Sleva -X%. Objednáno na: d.m.Y, Poznámka z formuláře: …",
    numberRequested `checkDuplicity`, dates, place name, partner address, paymentType "Plat.kartou"/"Hotově",
    carrier coolbalik/ceskakruta/prodejny, items: code (special mapping for turkey `product_tp_id` 1/2 × `product_id` 1/2/41/42 →
    600001/600002/600040/600041/600045/600046/600047/600048, else `product.code`), text (`OrderService::getOrderItemName`), quantity, unit price, store EXPEDICE.
  - Sets `pohoda_sync_dt`; flash "Objednávka propsána do Pohody".
- **Boxxi** page (CoolBalík places): order summary + items (with "kg/ks" hint for ks products) + form:
  `weight` "Finální váha (kg)" (default = Σ amount×avg(weight_from,weight_to) | amount×weight_per_unit for ks | amount),
  `cool_balik_price` "Finální cena" (default `price_total`), `cool_balik_box_count` "Počet boxů" (default `ceil(weight/20)`),
  `date` "Datum doručení" (default order date). Submit "Uložit a propsat": update `date`, `cool_balik_price`, `cool_balik_box_count`,
  `cool_balik_weight`, `boxxi_weight`; if `boxxi_sync_dt IS NULL` POST `/v1/shipments` (payload in old `libs/services/Boxxi.php`:
  fixed sender address, COD = price, DeliverAt = date 12:00, EveningDelivery, TotalWeight, PackageCount…) and set `boxxi_sync_dt`;
  flash "Objednávka propsána do Boxxi"; redirect to list.

### 2.18 Navigation (old navbar order)
Aktuality · Rozkrmená krůťata · Chlazené krůty · Živé krůty · Maso a výrobky · Doplňkový sortiment (dropdown of categories) ·
Texty · Prodejci · Emaily · Prodejny · Uživatelé · Objednávky · [user name → change password] · Odhlásit.

---

## 3. Target architecture

### 3.1 Conventions (match the existing codebase)
- Controllers: `src/Controller/Admin/*Controller.php`, single-action `__invoke`, attribute routes, prefix `/admin`, route names `admin_*`.
  ⚠ `config/services.php` loads only `src/Controller/{*Controller.php}` → change to `src/Controller/**/{*Controller.php}`.
- Writes: `src/Message/Admin/*` (readonly final, `fromFormData()` named constructor) + `src/MessageHandler/Admin/*Handler.php`
  (`#[AsMessageHandler]`), dispatched via `MessageBusInterface` (sync, `doctrine_transaction` middleware already on the bus).
- Reads: `src/Query/Admin/*` services using DBAL (same as existing `src/Query/*`), returning readonly value objects in `src/Value/Admin/*`
  with typed array shapes (PHPStan max).
- Forms: `src/FormData/Admin/*` + `src/FormType/Admin/*`.
- DB writes via DBAL on legacy tables (no new Doctrine entities, no schema changes — §0): always set `ins_dt`/`ins_process` or `upd_process`,
  **cast bools to int** (MariaDB strict mode — see commit 0ce4cfc), fill `ins_user_id`/`upd_user_id` with the admin id (columns exist, old app never filled them).
- Templates: `templates/admin/**`, own layout `templates/admin/base.html.twig`; separate Encore entry `admin` (Bootstrap 5, Stimulus).
- Domain exceptions in `src/Exceptions/` (e.g. `RecordNotFound`), controllers catch `HandlerFailedException` like `EditUserInfoController`.

**Section recipe** (reference implementation: Prodejci / vendor, Phase 4):
- Read model `Value/Admin/AdminVendor` + query `Query/Admin/GetAdminVendors` (`all()`, `oneById()` throws `Exceptions\RecordNotFound`).
- `FormData/Admin/VendorFormData` (constraints, `fromVendor()`) + `FormType/Admin/VendorFormType` (`getBlockPrefix()` = table, field = column).
- Commands `Message/Admin/Vendor/{CreateVendor,EditVendor}` (`fromFormData()`, `columns()`), handlers write through
  `Repository/Admin/AdminRecordRepository` (audit columns, `orderable: true` → `order = id*10`); create handler returns the new id,
  controller reads it via Messenger `HandleTrait`.
- Controllers `Controller/Admin/Vendor/{VendorList,VendorCreate,VendorEdit}Controller` — edit: unknown id → redirect to list,
  `_continue` button → back to the form; list passes `CountInactiveRecords` for "Aktivovat vše".
- Templates `templates/admin/vendor/{list,form}.html.twig`: grid macros `admin/_grid.html.twig`, buttons `admin/_form_buttons.html.twig`.
- Rich text fields use `FormType/Admin/WysiwygType` (textarea + Stimulus `wysiwyg`).

### 3.2 Security
- **Separate `admin` firewall** (`^/admin`) with its own `form_login` (`/admin/login`), `logout` (`/admin/logout`),
  `remember_me` (14 days, checkbox = old "Trvalé přihlášení") and a `UserChecker` that rejects non-admins at login with a clear message.
  The firewall must be declared **before** `main` (whose pattern `^/` matches everything).
- `access_control`: `^/admin/login` PUBLIC, `^/admin` ROLE_ADMIN — **must be placed before** the existing `^(/|/cron)` PUBLIC rule
  (first match wins; that rule matches every URL).
- ⚠ Bug to fix first: `Value\User::getUserIdentifier()` returns the **id**, but `UserProvider::loadUserByIdentifier()` looks up by **email**.
  Sessions work (refresh uses email), but `remember_me` would fail. Make the identifier the email (or let the provider accept both).
- All mutating grid actions: POST forms with CSRF token (`csrf_token('admin-action')`), never GET.

### 3.3 Generic grid actions
Instead of ~40 near-identical commands, one set of generic commands over a **whitelisted enum**:
```php
enum AdminTable: string { case Actuality = 'actuality'; case CalendarDead = 'calendar_dead'; ... }
```
- Commands: `ActivateRecord`, `DeactivateRecord`, `ActivateAllRecords(table, scope?)`, `SoftDeleteRecord`, `MoveRecord(table, id, Direction, scope?)`.
- Enum methods hold per-table rules: `isOrderable()`, `canDelete(row)` (product type 3/4 ✗, sortiment id 1/2 ✗, place ✗),
  `canToggle(row)` (product type 3/4 ✗), `scopeColumn()` (`recipe.product_id`, `sortiment.category_id`).
- Routes: `POST /admin/record/{table}/{id}/{action}` and `POST /admin/record/{table}/activate-all`, redirect back to `Referer` / provided `_target` + `#item-{id}`.
- Trade-off: one place to secure and test vs. less explicit per-section code. Per-table rules are enforced in the handler, not only hidden in templates.
- ⚠ Old bug: `activateAll()` ignores the scope (on recipes of one product it activates recipes of **all** products; same for sortiment categories).
  New: scoped to the current list.

### 3.4 Route map

| Route name | Method | Path |
|---|---|---|
| `admin_login` / `admin_logout` | GET+POST / GET | `/admin/login`, `/admin/logout` |
| `admin_dashboard` | GET | `/admin` |
| `admin_change_password` | GET+POST | `/admin/zmena-hesla` |
| `admin_actualities` / `_add` / `_edit` | GET / GET+POST | `/admin/aktuality`, `/admin/aktuality/pridat`, `/admin/aktuality/{id}` |
| `admin_calendar_dead*` | | `/admin/rozkrmena-krutata[/pridat\|/{id}]` |
| `admin_calendar_cold*` | | `/admin/chlazene-kruty[/pridat\|/{id}]` |
| `admin_calendar_alive*` | | `/admin/zive-kruty[/pridat\|/{id}]` |
| `admin_products*` | | `/admin/produkty[/pridat\|/{id}]` |
| `admin_recipes*` | | `/admin/produkty/{productId}/recepty[/pridat\|/{id}]` |
| `admin_sortiment` / `admin_sortiment_category*` | | `/admin/sortiment`, `/admin/sortiment/{categoryId}[/pridat\|/{id}]` |
| `admin_contents` / `admin_content_edit` | | `/admin/texty`, `/admin/texty/{id}` |
| `admin_vendors*` | | `/admin/prodejci[/pridat\|/{id}]` |
| `admin_newsletter_emails` | GET | `/admin/emaily` |
| `admin_places` / `_add` / `_edit` | | `/admin/prodejny[/pridat\|/{id}]` |
| `admin_place_closed_day_add` | GET+POST | `/admin/prodejny/zavreno/pridat` |
| `admin_users` / `admin_user_edit` / `admin_user_banned_products` | | `/admin/uzivatele`, `/admin/uzivatele/{id}`, `/admin/uzivatele/{id}/zakazane-produkty` |
| `admin_user_wholesale` | POST | `/admin/uzivatele/{id}/velkoobchod` (`allow=0/1`) |
| `admin_orders` | GET | `/admin/objednavky` (+ `?date_from&date_to&customer_name&page`) |
| `admin_orders_export` | GET | `/admin/objednavky/export.csv` (+ same filter params) |
| `admin_order_detail` | GET | `/admin/objednavky/{id}` |
| `admin_order_pohoda` / `admin_orders_pohoda_bulk` | POST | `/admin/objednavky/{id}/pohoda`, `/admin/objednavky/pohoda` |
| `admin_order_boxxi` | GET+POST | `/admin/objednavky/{id}/boxxi` |
| `admin_record_*` | POST | `/admin/record/{table}/...` (generic actions, §3.3) |
| `cron_newsletter_send` | GET | `/cron/send-email?token=…` (§3.8 — same path the VPS crontab calls) |

### 3.5 Commands (all `src/Message/Admin`)
`ChangeAdminPassword` · `CreateActuality` (+ enqueue newsletter) · `EditActuality` ·
`CreateCalendarDeadEntry` / `EditCalendarDeadEntry` · `CreateCalendarColdEntry` / `EditCalendarColdEntry` · `CreateCalendarAliveEntry` / `EditCalendarAliveEntry` ·
`CreateProduct` / `EditProduct` (incl. `packages_size`) · `CreateRecipe` / `EditRecipe` (optional uploaded photo) · `CreateSortimentItem` / `EditSortimentItem` ·
`EditContent` · `CreateVendor` / `EditVendor` · `CreatePlace` (+ copy prices) / `EditPlace` · `AddPlaceClosedDay` ·
`EditUser` (incl. `is_admin` + self-guard) · `SetUserWholesale` · `SetUserBannedProducts` · `SendOrderToPohoda` · `SendOrderToBoxxi` · `SendNewsletterBatch` ·
generic `ActivateRecord` / `DeactivateRecord` / `ActivateAllRecords` / `SoftDeleteRecord` / `MoveRecord`.

### 3.6 Integrations
- `Services/Pohoda/PohodaClient` (Symfony HttpClient scoped client, env `POHODA_URL`, `POHODA_AUTHORIZATION`) +
  `PohodaOrderXmlFactory` (Twig XML templates ported 1:1 from `libs/services/Pohoda/*.latte`) +
  `PohodaDates` (pure functions for `dateTo`/`dateFrom`, unit tested).
- Boxxi: reuse the existing scoped client `coolbalik.client` (`config/packages/framework.php`, already points to Boxxi with `COOLBALIK_TOKEN`).
  `Services/Boxxi/BoxxiShipmentFactory` builds the payload; `ShipmentWeightCalculator` (weight + boxes).
- Item code/name: port `OrderService::getOrderItemCode()` / `getOrderItemName()` from the old app (read `getOrderItemName` there before porting).

### 3.7 Frontend tooling for admin
- WYSIWYG — Tiptap (§3.12).
- Dates: Symfony `DateType` `widget: single_text` (native date picker) or existing `datepicker_controller`.
- Stimulus controllers: `confirm` (exists), `toggle` (exists – packing / day toggles), `select_all` (orders), `place_day_hint`, `wysiwyg`.
- Optional later: drag & drop reordering (SortableJS) instead of up/down arrows.

### 3.8 Newsletter cron — HTTP trigger via curl
- `GET /cron/send-email?token=…` → controller dispatches `SendNewsletterBatch`, returns JSON `{"queued": n, "sent": [ids…]}`
  (same style as `RecurringOrderTriggerController`). HTTP 5xx on failure so lily's `curl -f` alerts (`LilyCronJobFailed`).
- Token: env `CRON_TOKEN`, compared with `hash_equals`; missing/wrong → 403. (The existing recurring-order trigger stays untouched.)
- Logic lives in the handler, so a later move to a real CLI cron is a thin `bin/console` command calling the same message.
- Keep the production pace: **10 mails per run, every 10 minutes, around the clock** (what the old trigger does today).
- Route path: **`/cron/send-email`** — same path as the old app, so the cutover only changes the domain (+ token) in the VPS line.
- Cutover (gap-first, like D79 — old and new must never run together, both read the same `actuality_newsletter` queue and could double-send):
  1. deploy the new endpoint (inactive until called);
  2. right after a campaign is fully sent (backlog 0): edit the one VPS line `old.ceskakruta.cz` → `ceskakruta.cz` (+ `?token=…`);
  3. improve the VPS line: `curl -fsS --max-time 60 'https://ceskakruta.cz/cron/send-email?token=…'` (errors visible, token),
     or move it to lily (`lily-cron-run`, alerts via `LilyCronJobFailed`). Recommended: lily, delete the VPS line (one place for all ceskakruta crons).
- Token: env `CRON_TOKEN`; with the token required the current VPS line (no token) gets 403 → the cutover step 3 is mandatory, not optional.
- **Mail transport must stay PHP `mail()`** (server-configured delivery; decided). Production Symfony `MAILER_DSN`/`MAILER_ORDERS_DSN`
  are SMTP — **not** used for the newsletter. Symfony has no built-in `mail()` transport (`native://`/`sendmail://` spawn the sendmail
  binary via `proc_open`, often disabled on hosting, and the docs discourage `native://`). → custom transport:
  - `src/Services/Mailer/PhpMailTransport` (extends `AbstractTransport`, calls `mail($to, $subject, $body, $headers)` with the
    encoded message, envelope sender via `-f` like Nette's `SendmailMailer`) + `PhpMailTransportFactory` (scheme `phpmail://default`,
    tag `mailer.transport_factory`);
  - new transport `newsletter` → env `MAILER_NEWSLETTER_DSN` (prod `phpmail://default`, dev `smtp://mailer:1025` → Mailpit);
  - newsletter messages select it via `X-Transport: newsletter` header (same mechanism as the existing `orders` transport).

### 3.9 User uploads (persistent storage)
- Production is an **FTP deploy to third-party shared hosting** (`.github/workflows/release.yml`, `server-dir: ./web/`), not Docker —
  there are no volumes in production. Persistent storage = a directory the deploy never touches.
- Uploads root: `public/upload/` (env `UPLOADS_DIR`, default `%kernel.project_dir%/public/upload`), files keep the old relative
  layout so existing `photo.path` values (`upload/recipes`) work unchanged (constraint 2).
  - add `/public/upload/` to `.gitignore`
  - add `**/public/upload/**` to the FTP-Deploy-Action `exclude` list (so a deploy can never delete/overwrite user files)
  - dev: persisted via the existing `.:/app` bind mount.
- Frontend: replace `https://old.ceskakruta.cz/{{ path }}/{{ filename }}` with `asset(path ~ '/' ~ filename)` once files are copied.
- Today the only user uploads are **recipe photos** (12 `photo` rows in production) — copied manually via FTP from the old hosting.
- **Product photos stay manual** (decided): committed static files `public/img/produkty/{id}.jpg` (looked up via `asset_exists`), no admin upload.
- **No hosting backup** of uploads (confirmed). Mitigation: keep a local copy of every manual upload; optionally later a nightly
  `lftp mirror` of `web/public/upload/` from lily (same pattern as the existing curl cron) — not part of this project.

### 3.10 Domains & redirects
- Now: change the `/admin` rule in `public/.htaccess` from `R=301` to `R=302` (so browsers stop caching it).
- At admin go-live: **remove** that rule; the new `/admin` serves the admin.
- At old-app switch-off: on the old hosting replace the old app's `www/.htaccess` with a 301 from `old.ceskakruta.cz/*` →
  `https://ceskakruta.cz/` (`/admin*` → `https://ceskakruta.cz/admin`). Old files are **kept**.
- Do §3.9 (recipe images local) **before** the old-domain 301, otherwise recipe images break.

### 3.11 Secrets → production `.env.local`
Checked (values masked) `~/Downloads/.env.local` = production Symfony env: `APP_ENV`, `APP_DEBUG`, `DATABASE_URL`, `MAILER_DSN` + `MAILER_ORDERS_DSN`
(both SMTP with credentials), `SECURITY_SALT_1/2`, `COOLBALIK_TOKEN`, `SENTRY_DSN`. **Missing for the admin**: `POHODA_URL`,
`POHODA_AUTHORIZATION`, `CRON_TOKEN`, `MAILER_NEWSLETTER_DSN`, `UPLOADS_DIR` (optional, has default).
Checked `~/Downloads/config.local.neon` (the old production Nette `app/config/config.local.neon`): it contains **only**
`database.default` (driver, host, dbname, user, password) and `mailer.developRecipients`. **No SMTP block** → the old app sent all
mail (incl. newsletters) via PHP `mail()` on the hosting. Other secrets are hard-coded in committed old code.
Never commit values; set them in production `web/.env.local` (already excluded from FTP deploy). After transferring, keep the
neon file in a password manager rather than in `~/Downloads`.

| Old location | What | New env var | Action |
|---|---|---|---|
| prod `config.local.neon` → `database.default` | DB credentials (shared DB) | `DATABASE_URL` | already set in prod `.env.local` ✅ — **change `serverVersion=10.1.0-MariaDB` → `10.11.19-MariaDB`** (prod runs 10.11; done in repo `.env`) |
| prod `config.local.neon` → `mailer.developRecipients` | dev-mode mail redirect | — | not needed (dev uses Mailpit) |
| (none — PHP `mail()`) | newsletter transport | `MAILER_NEWSLETTER_DSN=phpmail://default` | **new** (custom transport, §3.8); prod `MAILER_DSN`/`MAILER_ORDERS_DSN` are SMTP and stay for other mails |
| `config.parameters.neon` → `authentication` salts | password salts | `SECURITY_SALT_1`, `SECURITY_SALT_2` | already used by `PasswordHasher` — verify identical (logins work → most likely ✅) |
| `config.parameters.neon` → `mailer.from` | sender address | existing mailer `from` config | check newsletter uses the same sender |
| `libs/services/Pohoda.php` | Pohoda URL (`http://<ip>:<port>/xml`) + `STW-Authorization` header | `POHODA_URL`, `POHODA_AUTHORIZATION` | **new** — copy values from old code into prod `.env.local` |
| `libs/services/Boxxi.php` | `x-api-key` | `COOLBALIK_TOKEN` (used by `coolbalik.client` → Boxxi) | verify it's the same key |
| — | newsletter trigger token | `CRON_TOKEN` | **new** — generate (`openssl rand -hex 32`), put in prod `.env.local` + lily cron line |
| `config.neon` → `recaptcha`, `googleMapsApiKey` | old frontend only | — | not needed |

Also: Pohoda endpoint is plain **HTTP** to a fixed IP with basic auth — credentials travel unencrypted. Out of scope,
but worth asking the Pohoda/mServer admin whether HTTPS is available.

### 3.12 WYSIWYG editor — Tiptap (decided)
What the editor has to handle (measured on all rich-text columns in the local DB): mostly `p, strong, a, span, br, h2`,
plus `b, em, ul/li, h3, h1`, **6 `<font>`**, **41 `align=`**, **225 inline `style=`** (Word/web paste junk: Helvetica font-family,
`rgb(88,88,90)` colours, `margin-left: 106.2pt`…), **base64 `<img src="data:…">`** inside content, and `target=` on links.

| Option | Licence | Legacy HTML | Verdict |
|---|---|---|---|
| **Tiptap** (ProseMirror-based, headless) | MIT | normalizes (unknown tags/attrs dropped, documented) unless modelled as extensions | **chosen** |
| Plain ProseMirror | MIT | same | too low-level (menus/schema/commands hand-built) |
| Lexical | MIT | normalizes | React-first |
| TinyMCE 8 | GPLv2+ / commercial | keeps everything | GPL in an MIT project, heavy |
| CKEditor 5 | GPL / commercial | GHS can allow all | GPL, heavy |

Setup: `@tiptap/core` + StarterKit (p, h2/h3, bold, italic, lists, hr), Link (keep `href`, `target`, `rel`), Image (`allowBase64: true`),
TextAlign (maps legacy `align=`/`text-align`), custom `small` mark (`span.small`, old "Menší text" preset), Table only if found in data
(none found). Toolbar in our admin design, Stimulus `wysiwyg_controller` syncing into the hidden `<textarea>` (form stays a plain Symfony field).
Escape hatch: **"HTML" toggle** — raw textarea; when the field is saved from raw mode the HTML is stored as-is.
Accepted consequence: inline paste styles (`font-family`, colours, `pt` margins) and `<font>` disappear on the next save of that row
(they conflict with the new design anyway). Rows are untouched until someone edits them.
Guard: **dry-run normalization report** (phase 4): a Node script runs every rich-text row through Tiptap `generateJSON → generateHTML`
and reports rows that lose images, links, headings, lists or text; anything flagged is reviewed before go-live.

---

## 4. Testing strategy

### 4.1 Infrastructure (Phase 0)
- ✅ `docker-compose.yml` on `mariadb:10.11` with production-identical schema (§0.5). CI test job uses `mariadb:10.11` as well.
- Install dev deps: `phpunit/phpunit` (^11), `dama/doctrine-test-bundle` (⚠ `config/packages/test/dama_doctrine_test_bundle.php`
  already configures it but the package isn't installed → test env can't boot today), register bundle for `test` env.
- `phpunit.dist.xml`, `tests/bootstrap.php`, `.env.test` (`DATABASE_URL` → `ceskakruta_test`, salts, dummy tokens), PSR-4 `CeskaKruta\Web\Tests\` → `tests/`.
- Test database (schema isn't managed by migrations — legacy tables, and must not change — §0):
  - commit `tests/database/schema.sql` = **production schema** (`--no-data` from the prod dump, i.e. with the drifts in §0.5) + `tests/database/fixtures.sql`
    (synthetic data only, no rows from the prod dump)
    (small, deterministic: 1 admin, 1 non-admin user, places incl. a CoolBalík and a delivery place, `lst_product_tp` 1/2,
    products of types 1–4 incl. `packages_size`, sortiment categories 1–4, 1–2 rows per admin table, orders with items incl. turkey items).
    `composer test:db` (re)creates the DB; DAMA rolls back each test.
  - Done 2026-10-26: prod vs local schema diffed (§0.5). Re-diff whenever a new prod dump is taken.
- Helpers: `tests/AdminWebTestCase` (`loginAsAdmin()`, `loginAsUser()`, `assertFlash()`, `fetchRow(table, id)`),
  `MockHttpClient` for Pohoda/Boxxi, `MailerAssertionsTrait` for newsletter.
- Add `composer test` script + a `phpunit` job in `.github/workflows/test.yml` (MariaDB service); run PHPStan on `tests/` too.

### 4.5 Phase 0 — what was built
- Dev deps: `phpunit/phpunit` 11.5, `dama/doctrine-test-bundle` 8.2 (test env only), `phpstan/phpstan-phpunit` 1.4 — no production package changed in `composer.lock`.
- `phpunit.dist.xml` (DAMA extension, fail on deprecations/notices/warnings), `.env.test` (kernel class, `ceskakruta_test` DB,
  test-only salts `test1`/`test2`, null mailers), `config/packages/test/webpack_encore.php` (`strict_mode: false` → no JS build needed in CI).
- `tests/database/schema.sql` = production structure (no data); `tests/database/fixtures.sql` = synthetic users (admin / customer / wholesale),
  lookup tables, places 1/7/21/22/30, products incl. turkey ids 1/2/41/42/47–50. Extended per phase.
- `tests/bootstrap.php` → `Tests\Support\TestDatabase::recreate()` drops + recreates the test DB on every run (refuses any DB name not ending `_test`),
  executes statements one by one so SQL errors surface.
- Base classes `Tests\Support\IntegrationTestCase` (`connection()`, `fetchInt()`, `fetchString()`) and `Tests\Support\WebTestCase`;
  both restore Symfony's exception handler after each test (Symfony 7.1 + PHPUnit 11 "risky" warning).
- `tests/Infrastructure/*`: separate DB on 10.11 in strict mode, production schema, fixture passwords match `PasswordHasher`, DAMA rollback, homepage via test client.
- PHPStan (max) now also analyses `tests/`. CI: new `phpunit` job with `mariadb:10.11` service. FTP release excludes `tests/`, `docs/`, test config.
- Run locally: `docker compose exec web composer run-script test`. Host ports are configurable
  (`WEB_PORT=8180 ADMINER_PORT=8100 docker compose up -d`, see CLAUDE.md).

### 4.2 Test layers
1. **Inventory / smoke** — `tests/Admin/AdminPagesSmokeTest.php`: data provider with **every admin GET page** (§3.4):
   anonymous → redirect to `/admin/login`; non-admin → refused (403); admin → 200 and expected `<h1>`.
   Written in full in Phase 1 → it *is* the red TODO list for the whole project.
2. **Functional** (WebTestCase) per section: submit forms → assert DB row + flash + redirect; validation errors re-render the form.
3. **Handler integration** (KernelTestCase): dispatch command → assert DB (incl. `ins_process`, `upd_user_id`, bool→int).
4. **Unit**: Pohoda dates, Pohoda item codes, Pohoda XML (**golden files**: render the old Latte templates for 3–4 fixture orders
   once from the old app and store the XML in `tests/fixtures/pohoda/*.xml` — characterization tests prove 1:1 parity),
   Boxxi payload, weight/box calculation, reorder algorithm, place weekday hint, ISO week range formatting, packages_size parsing.

### 4.3 How the "TODO via failing tests" works
- Phase 1 writes **all** test classes below with real assertions (no `markTestIncomplete`, which would show as "incomplete" rather than fail).
  The suite is red; every phase turns its part green.
- **No exclusion groups (decided)**: the whole suite runs everywhere, CI is red until the last phase. Work happens on branch
  `feature/admin`, which is merged into `main` only when everything is green — `main` (and therefore the FTP release) is not affected.
  Hotfixes for the live site go to `main` directly as before.
- Tests are **black-box** where possible (HTTP + DB), so the implementation stays free. Only pure logic (Pohoda dates/codes/XML,
  Boxxi weights, packages size, weekday hint, ISO week range, `mail()` transport) is unit tested; those classes exist from Phase 1 as
  **skeletons** with final signatures that throw `\LogicException('Not implemented yet')` (keeps PHPStan green while tests are red).
- Handler-level tests from §4.4 are written as functional tests through the HTTP endpoint when that covers the same behaviour.

### 4.4 Test list (the TODO list)

**Security & shell** — `tests/Admin/Security/`
- [ ] `AdminLoginTest::testLoginPageRenders`
- [ ] `AdminLoginTest::testAdminCanLogIn` (redirect to dashboard)
- [ ] `AdminLoginTest::testInvalidCredentialsShowError`
- [ ] `AdminLoginTest::testNonAdminCannotLogIn`
- [ ] `AdminLoginTest::testRedirectsToOriginallyRequestedPageAfterLogin`
- [ ] `AdminLoginTest::testLoggedAdminVisitingLoginIsRedirectedToDashboard`
- [ ] `AdminLoginTest::testRememberMeKeepsAdminLoggedIn`
- [ ] `AdminLogoutTest::testLogout`
- [ ] `AdminAccessTest::testAnonymousIsRedirectedToLogin` (provider: all admin URLs)
- [ ] `AdminAccessTest::testNonAdminIsForbidden` (provider: all admin URLs)
- [ ] `AdminAccessTest::testMutatingActionsRequirePostAndCsrf` (provider: all `admin_record_*`, wholesale, pohoda)
- [ ] `AdminPagesSmokeTest::testPageLoads` (provider: all admin GET pages incl. edit pages of fixture rows)
- [ ] `AdminLayoutTest::testNavigationContainsAllSections` (+ sortiment categories dropdown, user name, logout)
- [ ] `ChangePasswordTest::testChangesPassword` (stored hash = legacy MD5 format) / `testRejectsWrongOldPassword` / `testRejectsMismatch` / `testValidatesLength`

**Generic grid actions** — `tests/Admin/Grid/`
- [ ] `ActivateRecordHandlerTest` / `DeactivateRecordHandlerTest` (every `AdminTable`)
- [ ] `ActivateAllRecordsHandlerTest::testActivatesAllInactive` / `testRespectsScope`
- [ ] `SoftDeleteRecordHandlerTest::testSetsDelFlag` / `testRefusesProductType3And4` / `testRefusesSortiment1And2` / `testRefusesPlace`
- [ ] `ToggleRecordHandlerTest::testRefusesProductType3And4`
- [ ] `MoveRecordHandlerTest::testMoveUp` / `testMoveDown` / `testInitialisesEmptyOrder` / `testEqualOrderTieBreak` / `testRespectsScope` / `testNoNeighbourIsNoop`
- [ ] `GridActionControllerTest::testRedirectsBackWithAnchorAndFlash`
- [ ] `AdminTableTest::testUnknownTableIs404`

**Aktuality + newsletter**
- [ ] `ActualityListTest::testListsSortedByDateDesc` / `testActivateAllDisabledWhenNoneInactive`
- [ ] `CreateActualityTest::testCreates` / `testValidation` / `testEnqueuesNewsletterForActiveSubscribers` / `testIgnoresDeletedAndInactiveSubscribers`
- [ ] `EditActualityTest::testEdits` / `testSaveAndContinueStaysOnForm` / `testUnknownIdRedirectsToList`
- [ ] `SendNewsletterBatchHandlerTest::testFillsMissingForLatestActuality` (max 50, only subscribers older than actuality, not already queued)
- [ ] `SendNewsletterBatchHandlerTest::testSendsAtMostTenAndMarksSent`
- [ ] `SendNewsletterBatchHandlerTest::testEmailHasSubjectBodyAndUnsubscribeLink` (link valid for `unsubscribe_newsletter`)
- [ ] `NewsletterCronControllerTest::testTriggerReturnsJsonAndSends` (path `/cron/send-email`) / `testRejectsMissingOrWrongToken` / `testFailureReturns5xx`
- [ ] `NewsletterCronControllerTest::testUsesNewsletterTransport` (`X-Transport: newsletter`)
- [ ] `PhpMailTransportTest::testPassesRecipientsSubjectBodyAndHeadersToMail` (injected `mail()` callable) / `testSetsEnvelopeSender` / `testFailureThrowsTransportException`
- [ ] `PhpMailTransportFactoryTest::testSupportsPhpmailScheme`
- [ ] `NewsletterEmailsListTest::testListsSortedByEmail` / `testDelete`

**Calendars** (each of dead / cold / alive)
- [ ] `Calendar*ListTest::testListSortingAndColumns` (incl. week date range text)
- [ ] `Create/EditCalendar*Test::testCreates` / `testEdits` / `testWeekRange1To53` / `testYearRange2014ToCurrentPlus2` / `testDefaultYearIsCurrent`
- [ ] cold/alive: `testProductTypeRequired`, `testWeightsMustBeNumbers`; dead: `testWeekAgeOptionalInt`

**Products & recipes**
- [ ] `ProductListTest::testColumnsAndRecipeCount` / `testTurkeyProductsHaveNoToggleAndDelete`
- [ ] `CreateProductTest::testCreates` / `testValidation` (name, type required; weight float; price int) / `testAlwaysWritesUnit` (prod column has no default)
- [ ] `EditProductTest::testEditsMeatProduct` / `testTurkeyProductOnlyPriceCodeText` / `testEmptyPricePackIsNull` / `testUnpackableClearsForcePacking`
- [ ] `EditProductTest::testEditsPackagesSize` (stored `[500,1000,1500]`) / `testEmptyPackagesSizeStoresEmptyArray` / `testRejectsInvalidPackagesSize`
- [ ] `PackagesSizeTest` (unit: parse "1000, 500, 500" → `[500,1000]`; JSON round-trip compatible with `GetProducts`)
- [ ] `RecipeListTest::testUnknownProductRedirects` / `testListSortedByName` / `testShowsImage`
- [ ] `CreateRecipeTest::testCreatesWithoutPhoto` / `testCreatesWithPhoto` (file stored, `photo` row, `photo_id` set) / `testRejectsNonImage`
- [ ] `EditRecipeTest::testEdits` / `testReplacesPhoto` / `testKeepsPhotoWhenNoUpload`

**Sortiment, content, vendors**
- [ ] `SortimentIndexTest::testListsCategories`; `SortimentCategoryTest::testUnknownCategoryRedirects` / `testFirstThreeRowsCannotMoveUp` / `testIds1And2CannotBeDeleted`
- [ ] `Create/EditSortimentItemTest::testCreatesInCategory` / `testEdits` / `testOrderSetAfterInsert`
- [ ] `ContentListTest::testListSortedByOrder`; `EditContentTest::testOnlyTextIsEditable` / `testRawHtmlModeSavesAsIs`
- [ ] `VendorListTest::testColumns`; `Create/EditVendorTest::testCreates` / `testEmailValidation` / `testMaxLengths` / `testOrderSetAfterInsert`

**Places**
- [ ] `PlacesPageTest::testShowsClosedDaysFromTwoDaysAgo` / `testClosedDayAllPlacesLabel` / `testHidesHideInAdminPlaces` / `testDayColumnsShowResultingWeekdayAndTime`
- [ ] `AddPlaceClosedDayTest::testAddsForPlace` / `testAddsForAllPlaces` / `testDateRequired`; `DeletePlaceClosedDayTest`
- [ ] `CreatePlaceTest::testCreates` / `testCopiesPricesFromPlace` / `testDisabledDayStoresNulls` / `testTimeFormatValidation` / `testDefaultTime2359`
- [ ] `EditPlaceTest::testEdits` / `testEnableDayCheckboxesReflectData` / `testNoPriceCopyFieldOnEdit`
- [ ] `PlaceDayHintTest` (unit: day − daysBefore wrap 1–7)

**Users**
- [ ] `UserListTest::testColumnsOrdersCountAndBannedCount` / `testAdminBadge`
- [ ] `SetUserWholesaleTest::testAllow` / `testCancel` (flash with email)
- [ ] `EditUserTest::testEdits` / `testEmailRequiredAndValid` / `testPreferredPlaceIncludesWholesaleOption22` / `testUnknownIdRedirects`
- [ ] `EditUserTest::testGrantsAndRevokesAdmin` / `testCannotRevokeOwnAdmin`
- [ ] `SetUserBannedProductsTest::testShowsActiveProductsPrechecked` / `testReplacesBans` / `testClearAll`

**Orders**
- [ ] `OrderListTest::testListSortedByIdDesc` / `testColumns` (source badge, coupon, place, Pohoda/Boxxi state)
- [ ] `OrderListTest::testFilterByDateRange` / `testFilterByCustomerName` / `testReset` / `testCheckboxOnlyForUnsynced` / `testBoxxiColumnOnlyForCoolBalik`
- [ ] `OrderListTest::testPaginates50PerPage` / `testPaginationKeepsFilter`
- [ ] `OrderExportTest::testCsvHeaderRowsAndFilename` / `testExportRespectsFilter` / `testExportIncludesAllPages`
- [ ] `OrderDetailTest::testShowsAllFieldsAndItemsSorted` / `testUnknownIdRedirects`
- [ ] `PohodaDatesTest` (unit, provider over all weekdays × {shop, shop place 1, delivery, CoolBalík} × {turkey, other})
- [ ] `PohodaItemCodeTest` (unit: 8 turkey mappings + fallback to `product.code`)
- [ ] `PohodaOrderXmlTest::testMatchesGoldenFile` (shop order, delivery order with address id, CoolBalík split Tue/Thu, coupon note, card payment)
- [ ] `SendOrderToPohodaHandlerTest::testSearchesThenCreatesAddressForDelivery` / `testUsesFoundAddress` / `testShopOrderSkipsAddress` / `testSplitsTurkeysForCoolBalik` / `testSetsSyncDate` / `testHttpErrorKeepsOrderUnsynced`
- [ ] `PohodaControllerTest::testSingleSend` / `testBulkSendOnlyChecked` / `testBulkContinuesAfterFailureAndReports`
- [ ] `ShipmentWeightCalculatorTest` (turkey avg weight, ks × weight_per_unit, kg amount; boxes = ceil(w/20))
- [ ] `BoxxiPageTest::testDefaults` (weight, price, boxes, date) / `testShowsOrderSummary`
- [ ] `SendOrderToBoxxiHandlerTest::testUpdatesOrderAndSendsPayload` / `testDoesNotResendWhenAlreadySynced` / `testSetsSyncDate`

**Uploads & old-app switch-off**
- [ ] `RecipePhotoStorageTest::testStoresUnderUploadsDirWithLegacyRelativePath` (`photo.path = 'upload/recipes'`)
- [ ] `RecipeImagesTest::testRecipeImagesAreServedLocally` (frontend templates no longer reference `old.ceskakruta.cz`)
- [ ] `AdminRedirectTest::testHtaccessNoLongerRedirectsAdminToOldDomain` (asserts `public/.htaccess` content)
- [ ] `DeployConfigTest::testUploadsDirIsExcludedFromFtpDeployAndGit` (asserts `release.yml` exclude + `.gitignore`)

### 4.6 UI / test contract
The tests (Phase 1) and the implementation (Phases 2–12) both follow these rules; changing one means changing the tests.

**Test infrastructure**
- Functional tests extend `Tests\Support\AdminWebTestCase` (`$this->client`, `loginAsAdmin()`, `loginAsCustomer()`, `insert()`,
  `fetchRow()`, `fetchInt()`, `assertFlash()`, `assertRedirectsTo()`, `listedIds()`, `assertListedInOrder()`, `gridActionForm()`,
  `assertNoGridAction()`); unit tests extend PHPUnit `TestCase`, kernel tests `Tests\Support\IntegrationTestCase`.
- HTTP: every client in the test env uses `Tests\Support\HttpMock` (`framework.http_client.mock_response_factory`) — queue responses
  with `HttpMock::queue(new MockResponse(...))`, inspect `HttpMock::requests()`; an unexpected request throws.
- Mail: `MAILER_*_DSN=null://null` in test, assert with Symfony's `MailerAssertionsTrait` (`assertEmailCount`, `getMailerMessages`).
- Fixtures: `tests/database/fixtures.sql` = shared base (users 1 admin / 2 customer / 3 wholesale, places 1/7/21/22/30, products,
  one row per admin table with id 1, orders 1 shop + 2 CoolBalík). Per-section rows in `tests/database/fixtures/NN-section.sql`
  with their own **id range** to avoid collisions: newsletter/actuality 1000–1999, calendars/content/vendors/sortiment 2000–2999,
  products/recipes 3000–3999, places/users 4000–4999, orders 5000–5999, security/grid/shell 6000–6999 and order values ≥ 100000. Lists contain rows of other sections →
  assert relative order (`assertListedInOrder`) and presence/absence of own rows, never the full list.
- Test env values (`.env.test`): `CRON_TOKEN=test-cron-token`, `POHODA_URL=http://pohoda.test/xml`, `POHODA_AUTHORIZATION=test-pohoda-auth`,
  `MAILER_NEWSLETTER_DSN=null://null`. Uploads go to the container parameter `uploads_dir` (test: `%kernel.project_dir%/var/test-uploads`).
- Parallel runs: `docker compose exec -e DATABASE_URL='mysql://root:root@mariadb:3306/<name>_test?serverVersion=10.11.19-MariaDB' web bin/phpunit …`.

**Security**
- Login `/admin/login`: fields `_username`, `_password`, `_remember_me`, `_csrf_token`; button **"Přihlásit"**.
  Invalid credentials → `.alert-danger` "Byly zadány neplatné přihlašovací údaje!"; non-admin → `.alert-danger`
  "Do administrace mají přístup pouze administrátoři.". Remember-me cookie `ADMIN_REMEMBERME` (14 days). Firewall name `admin`.
- Logout: `GET /admin/logout` → redirect `/admin/login`. Anonymous on any admin page → redirect `/admin/login`; logged-in non-admin → 403.
- Every mutation is a POST with hidden `_token` (CSRF id `admin-action`, forms: Symfony form CSRF); wrong/missing token → 403,
  GET on a POST-only route → 405.

**Layout & pages**
- Every page has one `<h1>`. List pages: the h1 is the navigation label (§2.18), e.g. "Aktuality", "Maso a výrobky", "Objednávky".
  Dashboard h1 "Přehled", change password h1 "Změna hesla".
- Navigation (`nav`) has links with the exact labels and order of §2.18; sortiment categories in a dropdown linking to
  `/admin/sortiment/{categoryId}`; the logged admin's name links to `/admin/zmena-hesla`; "Odhlásit" links to `/admin/logout`.
- Flash messages: Bootstrap alerts `.alert-success` / `.alert-danger`, texts as in §2 (Czech, same as the old admin).

**Lists**
- Each row has `id="item-{id}"`; DOM order = sort order. Exception: the closed days list on the places page uses
  `id="closed-day-{id}"` (two lists on one page), its grid redirects use the `#closed-day-{id}` anchor. Edit links point to the edit route (`/admin/<section>/{id}`).
- Generic grid actions (§3.3): `<form method="post" action="/admin/record/{table}/{id}/{action}">`, `{action}` ∈
  `activate|deactivate|delete|move-up|move-down`, hidden `_token` + `_target` (URL to return to). `{table}` = DB table name
  (`actuality, calendar_dead, calendar_cold, calendar_alive, product, recipe, sortiment, vendor, newsletter, place, place_closed_day`).
  Only allowed actions are rendered (hidden action = no form); a forbidden action posted anyway → 403, DB unchanged. Unknown table → 404.
- Activate all: `<form method="post" action="/admin/record/{table}/activate-all">` (+ hidden `scope` = product id / category id for
  recipes / sortiment), its button has `disabled` when the list has no inactive row.
- Action result: redirect to `_target` + `#item-{id}` (activate-all: `_target` only) with flash
  "Záznam úspěšně aktivován" / "Záznam úspěšně deaktivován" / "Záznam úspěšně smazán" / "Pořadí bylo úspěšně změněno!" /
  "Záznamy úspěšně aktivovány" (texts of the old `TRecordHandlers`). Move without a neighbour (first row up / last row down) = no change.
  Reorder ignores deleted rows (old `Entity::findAll`) and, within a scope, rows of other scopes.

**Forms**
- Symfony forms, form name = DB table name (`actuality`, `calendar_dead`, `calendar_cold`, `calendar_alive`, `product`, `recipe`,
  `sortiment`, `content`, `vendor`, `place`, `place_closed_day`, `user`, `boxxi`), field name = DB column name, so fields are
  `actuality[name]`, `place[allow_order_day_3_time]`, … Exceptions: `change_password[old_password]`, `change_password[new_password][first|second]`
  (RepeatedType), `banned_products[products][]` (checkbox per product id), `product[packages_size]` (text "500, 1000"),
  `recipe[image]` (file), `place[enable_day_N]` + `place[prices_from_place_id]`.
- Dates `YYYY-MM-DD` (`single_text`), times `HH:MM`, checkboxes standard (value `1`, unchecked = absent).
- Buttons: **"Uložit"** (create + edit) and **"Uložit a pokračovat v editaci"** (edit only).
- Success: flash "Záznam byl úspěšně uložen!", redirect to the list URL + `#item-{id}`; "save and continue" → redirect to the edit URL.
- Invalid: HTTP **422**, the form re-rendered with errors in `.invalid-feedback` (Bootstrap 5 theme), DB unchanged.
- Edit of an unknown id → redirect (302) to the list.
- Audit: insert sets `ins_dt`, `ins_process = 'admin'`, `ins_user_id = '<admin id>'`; update sets `upd_process = 'admin'`, `upd_user_id = '<admin id>'`.
  Booleans stored as `0/1`.

**Other endpoints**
- Wholesale toggle: `POST /admin/uzivatele/{id}/velkoobchod`, fields `allow` (`0|1`) + `_token`.
- Pohoda: `POST /admin/objednavky/{id}/pohoda` (`_token`), bulk `POST /admin/objednavky/pohoda` with `orders[]` (ids) + `_token`;
  the list page renders both forms (bulk = one form around the checkboxes, button "Označené poslat do Pohody").
- Boxxi: `GET+POST /admin/objednavky/{id}/boxxi`, form `boxxi[weight|cool_balik_price|cool_balik_box_count|date]`, button "Uložit a propsat".
- Newsletter cron: `GET /cron/send-email?token=…` → JSON `{"queued": int, "sent": list<int>}`; wrong/missing token → 403.

---

## 5. Phases

Each phase: write/enable its tests (red) → implement → green → PHPStan → manual check in browser.

| # | Phase | Contents |
|---|---|---|
| 0 ✅ | **Test infrastructure** | §4.1; one trivial green test proving kernel + DB + DAMA work; `phpunit` job in CI — done 2026-10-26 (see §4.5) |
| 1 ✅ | **TODO suite** | write every test from §4.4 (red) incl. route inventory provider — done 2026-10-26: 802 tests, all red for the right reason (404 / skeleton `LogicException`) except infrastructure + the `.htaccess` front-controller guard; Pohoda/Boxxi golden files rendered by the old code (`tests/fixtures/pohoda/_renderer/`) |
| 2 ✅ | **Shell & security** | admin firewall, UserChecker, identifier fix, remember-me, login/logout, layout + nav, dashboard, change password, `admin` Encore entry; `.htaccess` 301 → 302 — done 2026-10-26; `ADMIN_REMEMBERME` is scoped to `path=/admin`; backlink test stays red until `/admin/objednavky` exists (Phase 9) |
| 3 ✅ | **Generic grid** | `AdminTable` enum, 5 generic commands, CSRF POST controller, Twig macros for buttons, reorder — done 2026-10-26: `Value\Admin\Grid\{AdminTable,GridAction}` hold the rules, Twig `admin_grid_allows()` asks the same enum, macros in `templates/admin/_grid.html.twig`; Turbo Drive off in admin (keeps `#item-{id}` anchors); list-driven grid tests go green with the section pages |
| 4 | **Simple CRUD** | Tiptap `wysiwyg` controller + dry-run normalization report → Content → Calendars (dead, cold, alive) → Vendors → Newsletter emails |
| 5 | **Actuality + newsletter** | actuality CRUD, enqueue, `SendNewsletterBatch`, `/cron/newsletter-send` + `CRON_TOKEN`, email template |
| 6 | **Catalogue** | Products (type rules, `packages_size`) → uploads dir (§3.9) → Recipes (upload) → Sortiment |
| 7 | **Places** | closed days, place form with 7 days, day hint controller, price copy |
| 8 | **Users** | list, wholesale toggle, edit (+ `is_admin`), banned products |
| 9 | **Orders – read** | list + filters + pagination, detail, CSV export (filtered) |
| 10 | **Orders – Pohoda** | golden files from old app, dates, codes, XML, client, single + bulk send |
| 11 | **Orders – Boxxi** | calculator, page + form, payload, send |
| 12 | **Go-live & switch-off** | (a) secrets in prod `.env.local` (§3.11); (b) manual FTP copy old `www/upload/recipes/` → new `web/public/upload/recipes/`, recipe templates → `asset()`; (c) remove `/admin` redirect from `public/.htaccess`; (d) newsletter cutover gap-first (§3.8): disable old trigger → lily line; (e) parallel run, old admin used read-only; (f) old.ceskakruta.cz → 301 to ceskakruta.cz, old files kept |
| later | **After old app is off** | §8 items that need schema/hash changes |

Order rationale: shell + generic grid first (everything depends on them); orders last but before switch-off because
they carry the external integrations and the highest risk. If order processing is the daily pain point, phases 9–11 can move right after phase 3.

---

## 6. Decisions & open questions

Decided:
- **Phase 1 review (2026-10-26, accepted recommendations):**
  1. Pohoda: customer name escaped **once** (old templates double-escaped `&` → `&amp;amp;`).
  2. Boxxi: non-2xx / error response → order stays unsynced (`boxxi_sync_dt` NULL) + `.alert-danger` (old app ignored the response).
  3. Bulk Pohoda: flash "Propsáno do Pohody: N objednávek" + `.alert-danger` naming the failed order ids.
  4. Newsletter: row with an invalid e-mail is skipped, marked as processed and logged (does not block the queue).
  5. Newsletter batch `ORDER BY id` (oldest first).
  6. Newsletter: a deleted / inactive latest actuality is not enqueued.
  7. Unsubscribe link stays fixed to `https://ceskakruta.cz`.
  8. User list order count: non-deleted orders only.
  9. Own `is_admin`: disabled checkbox + refused in the handler.
  10. Turkey product (type 3/4): protected fields not in the form and ignored if posted (old form overwrote `unit=ks`, `weight=1`).
  11. Parity kept: week label with ASCII hyphen `10/2024 (04.03. - 10.03. 2024)`; `hide_in_admin` places stay in dropdowns;
      recipe images served from `/upload/recipes/…`.
- **packages_size** written compact (`json_encode`, `[500,1000]`); reader accepts existing hand-written `[500, 1000]` rows (frontend only `json_decode`s).
- **Q1** port `calendar_alive`, `sortiment`, `vendor` (customer may be asked later whether to drop them).
- **Q2** place `address_1..3` / `map_url` / `phone` out of scope.
- **Q3** keep "Převzít ceny z prodejny" on place create; make `packages_size` editable.
- **Q4** newsletter stays an HTTP trigger fired by curl from lily (hosting has no CLI).
- **Q5** orders: pagination + CSV export respects filter.
- **Q6** no coupon management (never in old admin).
- **Q7** `is_admin` editable, can't revoke own.
- **Q8** FTP access available; no hosting backup → recipe photos copied/re-uploaded manually.
- **Q10** product photos stay manual (committed static files), no admin upload.
- **Q11** Tiptap (§3.12).
- **Q12** `CRON_TOKEN` on the newsletter trigger.
- Separate admin firewall.

- **Q9** resolved by the prod dump: no backlog (0 unsent), newsletter is actively used → port as-is, same pace.

- Newsletter keeps PHP `mail()` (custom `phpmail://` transport, §3.8).

- **Q13** newsletter trigger = VPS crontab `*/10` curl to `https://old.ceskakruta.cz/cron/send-email` (§1.2).

Open: none blocking. (Note for later: the VPS has a working crontab, so a CLI cron may be possible there after all.)

---

## 7. Risks
- Pohoda XML/date logic is business-critical and subtle → golden-file characterization tests from the old app before porting.
- WYSIWYG re-saving normalizes legacy HTML → dry-run report + raw HTML mode (§3.12).
- `remember_me` + current identifier mismatch (§3.2) → fix in phase 2 with a test.
- Shared DB during parallel run: both admins can edit the same rows (last write wins) → old admin used read-only once the new one is live.
- Newsletter double-send if old and new trigger overlap → gap-first cutover (§3.8).
- Newsletter transport must behave like Nette's `mail()` (headers, encoding, envelope sender) → test the encoded headers in a unit
  test and send one real test newsletter to an internal address on production before switching the cron.
- The current VPS newsletter cron silences all errors → after cutover use `curl -fsS` (or lily `lily-cron-run`) so failures alert.
- Uploads have no backup → excluded from deploy + local copies (§3.9).
- MariaDB 10.1 strict-ish mode (`NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION` locally — check production `sql_mode` in the dump)
  → always write explicit ints for bools and all NOT NULL columns (`place.force_delivery_address`, `ins_dt`, …).
- Duplicate sends to external systems on retries → Pohoda `checkDuplicity` protects; Boxxi guarded by `boxxi_sync_dt`; keep both guards.

## 8. Recommendations beyond parity (not blocking switch-off)
- **After the old app is off** (needs hash change in the shared `user.password` column — would lock users out of the old app before):
  migrate from salted MD5 to `auto` hasher with Symfony `migrate_from` (rehash on login); raise min password length to 8, remove max 20.
- Audit: fill `ins_user_id` / `upd_user_id` with admin id (columns exist, no schema change — included in conventions §3.1).
- Orders CSV with UTF-8 BOM (Excel shows Czech correctly); show actual `source` text instead of "Nový web".
- Uploads backup (nightly `lftp mirror` from lily).
- Users list (862 users) and newsletter emails list (6 828) render everything, as in the old admin — add pagination + search if it gets slow.
- MariaDB 10.1 is EOL since 2020 — plan an upgrade separately.
