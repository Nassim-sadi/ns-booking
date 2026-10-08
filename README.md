# NS Booking Configurator — Standalone WordPress Booking Plugin

> **One shortcode = one booking record.** Packages + Solo/Couple + extras + date + customer form → single `ns_booking` post. No TheGem/theme dependency. Server is source of truth for pricing.

[![WordPress 5.8+](https://img.shields.io/badge/WP-5.8%2B-blue)](https://wordpress.org)
[![PHP 7.4+](https://img.shields.io/badge/PHP-7.4%2B-777bb4)](https://php.net)
[![Vanilla JS](https://img.shields.io/badge/JS-vanilla-f7df1e)](https://developer.mozilla.org)
[![License: GPLv2](https://img.shields.io/badge/license-GPLv2-green)](https://www.gnu.org/licenses/gpl-2.0.html)

Inspired by **reelsinistanbul.com/#booking** — rebuilt as a fully standalone, theme-agnostic plugin.

---

## Why this plugin?

| Problem with TheGem form glue | NS Booking approach |
|---|---|
| Configurator POSTs to an existing Gem form, then glues data | **One atomic POST** creates one `ns_booking` post |
| Frontend total trusted | **Server recalculates** from package + assigned extras — tampered totals ignored |
| Booking data only as serialized JSON | **Queryable postmeta per field** + JSON snapshot for audit |
| Theme lock-in | **Works with any theme** (`Twenty Twenty-Five`, `TheGem`, Elementor) |

---

## Features

- **CPTs:** `ns_booking` (bookings), `ns_booking_package`, `ns_booking_extra` — all under **Bookings** menu
- **Package:** Solo price + Couple price (twin fields) + checklist of assigned extras + featured image + excerpt
- **Extras:** Title + price + image/icon (Media Library or Dashicon) + active toggle
- **Assignment:** Many-to-many — package only shows its assigned extras; unassigned extras are silently ignored (anti-spoof)
- **Frontend:** `[booking_configurator]` or `[ns_booking]` — no build step, Vanilla JS, accessible, responsive
  - Step 1: Package cards (with image), Solo/Couple pills, conditional extras, date (`min = today + lead days`)
  - Step 2: Sticky live summary (detailed, large price, image preview, auto-detects sticky header offset)
  - Step 3: Customer fields — name, email, phone with emoji flags (`🇹🇷 +90 🇺🇸 +1 🇫🇷 +33 …`), message, honeypot
- **Submission:** `POST /wp-json/nsbc/v1/bookings` with `X-WP-Nonce`; fallback `admin-ajax.php?action=nsbc_submit`; nonce + sanitization + honeypot + rate limit `10/min`
- **Storage:** Each booking saves `_booking_package_id`, `_booking_session_type` (`solo|couple`), `_booking_extras` (array IDs), `_booking_extras_labels`, `_booking_date` (`Y-m-d`), `_booking_total_cents` + `_booking_total_formatted`, `_booking_currency`, `_booking_customer_name/email`, `_booking_phone_country/number/full`, `_booking_customer_message`, `_booking_status` (`pending`), `_booking_snapshot` (JSON audit) + `post_title = Booking #123 — Name — 2026-09-15`
- **Admin:** Columns (Package/Session/Date/Total/Customer/Status), filters (package/session/status), sortable date/total, metabox with Recalculate + Resend emails, status `pending/confirmed/cancelled/completed`
- **Settings:** Currency (`EUR/USD/GBP/MAD/TRY/AED/SAR` — default `€`), admin emails (comma list), lead days, blackout dates, phone default + countries, email subjects (tags `{{id}} {{package}} {{session}} {{date}} {{total}}`), theme mode (`auto/light/dark`), background & card colors, card shadow (`auto/none/subtle/medium/strong`)
- **Styling:** CSS vars + `prefers-color-scheme: dark`, transparent cards (inherits site background), `color-scheme: light dark`, large price `~30px`, no jQuery

---

## Install

### A. Local dev (Junction — live edits)
```powershell
# PowerShell — run once
cmd /c mklink /J "C:\Users\nassim\Local Sites\gem6\app\public\wp-content\plugins\ns-booking" "E:\Projects\PackageForm"
# Activate in WP Admin → Plugins → NS Booking Configurator → Activate
```
> Junction is already set for `gem6`. Edits in `E:\Projects\PackageForm` are live after hard refresh (`Ctrl+F5`). If you need a true symlink, re-run as Administrator: `New-Item -ItemType SymbolicLink -Path "...\plugins\ns-booking" -Target "E:\Projects\PackageForm"`

### B. Zip upload
1. Download the ready-to-install plugin zip from **GitHub → Releases** (`ns-booking-X.Y.Z.zip`) — built automatically by the `release.yml` workflow on every `v*` tag; no need to zip the source yourself
2. WP Admin → Plugins → Add New → Upload → Activate
3. On activation, seeds 4 packages (1 Location / 2 Locations / All Locations / Ortaköy) + 7 extras (Flying Dress, Special Dress, Traditional Dress, Men Suit, Hair Styling, Makeup & Hair, Classic Car)

---

## Quick start

1. **WP Admin → Bookings → Booking Extras → Add New** → set Price (`€30` → `30.00`), image via **Select Image** (64×64 SVG/PNG) or Dashicon class, Active ✓
2. **Bookings → Packages → Add New** → Title (e.g. `Premium`), set **Price — Solo** + **Price — Couple**, **Featured Image**, excerpt, tick allowed extras → Publish
3. **Bookings → Settings** → Currency `EUR`, Admin emails `admin@example.com`, Lead days `1`, Blackout `2026-12-25, 2026-01-01`, Phone default `+33`, Phone countries `+90,+33,+49,+1` (flags auto-mapped), email subjects
4. Create a page → add block **Shortcode** → `[booking_configurator]` → Publish → View
5. Submit a test booking with DevTools → Network → modify `total` to `1` → inspect `wp-admin → Bookings` → total is still server-calculated (e.g. `€350`)

---

## Shortcode

```
[booking_configurator]
[ns_booking]   # alias
```

- Works in Gutenberg, Classic, Elementor Text, TheGem. No attributes needed.
- Assets are enqueued only when shortcode is present (`has_shortcode` guard via registration).
- `NSBC` global (via `wp_localize_script`): `restUrl`, `ajaxUrl`, `restNonce`/`ajaxNonce`, `packages` (`{id,label,prices,pricesFormatted,extraIds,imageUrl,excerpt}`), `extras` (`{id,label,price,priceFormatted,iconUrl,iconClass}`), `phoneCountries` (`[{code,flag,label}]`), `currency`, `minDate`, `blackoutDates`, `i18n`.

---

## Pricing — source of truth

```php
// includes/class-nsbc-pricing.php::calculate()
$total = package.price[session] + sum(extras where extra in package._package_extra_ids)
// Stored as cents (int), formatted via NSBC_Pricing::format($cents, $currency)
```

- Extras have a single price (same for Solo/Couple); Solo/Couple only changes the base package price.
- Currency is site-wide, display-only (no FX). Each booking snapshots `_booking_currency` + `_booking_total_formatted`.

---

## Data model

**Packages (`ns_booking_package`)**
- `_package_price_solo` int cents, `_package_price_couple` int cents
- `_package_extra_ids` array<int> (assigned extras)
- `_package_active` bool

**Extras (`ns_booking_extra`)**
- `_extra_price_cents` int, `_extra_icon_id` int (attachment), `_extra_icon_class` string, `_extra_active` bool

**Bookings (`ns_booking`, post_status `pending`)**
- `_booking_package_id`, `_booking_package_label`, `_booking_session_type`, `_booking_extras`, `_booking_extras_labels`, `_booking_date`, `_booking_total_cents`, `_booking_total_formatted`, `_booking_currency`, `_booking_customer_name`, `_booking_customer_email`, `_booking_phone_country/_number/_full`, `_booking_customer_message`, `_booking_status`, `_booking_snapshot` (JSON)

All queryable via `meta_query` — never require JSON parsing for admin filters.

---

## Security & validation

- Dual nonces: `wp_create_nonce('nsbc_submit')` + `wp_rest`, verified in `NSBC_Ajax::handle_rest/handle_ajax` (header `X-WP-Nonce` or `nonce` field)
- `sanitize_text_field/email/textarea`, `absint`, `sanitize_key`, regex phone `+\d{6,15}`, honeypot `website`/`nsbc_website`
- `validate_submission()` checks package exists + active, extras subset of assigned, date `Y-m-d` + `>= today+leadDays` + not blackout, name/email/phone required
- Rate limit via transient `nsbc_rate_{md5(IP)}` `10/min`

---

## Emails

- Uses `wp_mail()` with `Content-Type: text/html` — install **WP Mail SMTP** for deliverability
- Templates: `templates/emails/admin-notification.php` (with View Booking button) + `templates/emails/customer-confirmation.php`
- Resend via booking metabox → `Resend emails` (AJAX `nsbc_recalc` with `resend=1`)

---

## Styling & TheGem

- Standalone CSS (`assets/css/frontend.css`) — no theme functions, inherits `font-family`/`colors` naturally
- Dark mode via `@media (prefers-color-scheme: dark)` + `color-scheme: light dark`
- Transparent cards (`background:transparent`) so site/page background shows through
- Sticky summary `top: calc(var(--nsbc-header-height,0px) + 24px)` — JS auto-detects sticky header (`header.is-sticky`, `.thegem-header-sticky`, `header.site-header`, etc.) + `admin-bar` 32px, sets `--nsbc-header-height` on resize
- Larger type: base `16px`, card title `22px`, total `~30px clamp`, summary `16px`
- Responsive: `grid 1.15fr 420px → 1fr` at `980px`, `package grid minmax(240px)`, `extra icon 54px`

---

## Development

```powershell
Set-Location E:\Projects\PackageForm
php -l includes/class-nsbc-*.php templates/*.php ns-booking.php
# Git via Junction — changes are live in gem6 after refresh
git status
git log --oneline -5
```

**Release process:** tag the commit → GitHub Actions builds `ns-booking-<ver>.zip` (excludes `.git`, `.github`, zips) and attaches it to a GitHub Release. No local zipping needed.

**File map:**
```
ns-booking.php                    # header + constants + loader wiring + plugin_action_links → Settings
includes/class-nsbc-loader.php    # add_action/add_filter (supports 2-arg fn)
includes/class-nsbc-cpt.php       # 3 CPTs + 4 statuses
includes/class-nsbc-pricing.php   # calculate() + format()
includes/class-nsbc-validation.php# sanitize_settings + validate_submission()
includes/class-nsbc-emails.php    # send_admin/send_customer
includes/class-nsbc-ajax.php      # REST + AJAX + handle_recalc
includes/class-nsbc-shortcode.php # render + flag map + imageUrl + phoneOptions
includes/class-nsbc-settings.php  # submenu + register_setting
includes/class-nsbc-admin.php     # columns/filters/sortable
includes/class-nsbc-metabox-*.php # package (twin prices + extras checklist + thumbnail), extra (price+media), booking (edit + recalc)
assets/css/frontend.css            # vars, dark mode, packages/extras/summary
assets/js/frontend.js              # state, live total, emoji flags, package images, sticky header detection
templates/configurator.php         # 3-step HTML (config + customer + sticky summary)
templates/emails/*.php
```

---

## FAQ

**Why is my extra not added to total in admin?** The extra must be assigned to the package: edit **Package → Available Extras** and tick it. `Pricing::calculate()` ignores extras not in `package._package_extra_ids` to prevent spoofing.

**Can I change Solo/Couple prices per package?** Yes — each package has its own Solo and Couple fields. Keep them equal if no surcharge.

**Flags not showing?** Set **Settings → Phone countries** to `+90,+33,+49,+1` — shortcode maps them to `🇹🇷 🇫🇷 🇩🇪 🇺🇸`. If a code lacks a mapping, it falls back to `🌐`.

**How to add package images?** Edit package → **Featured Image** → Set. No code needed; shortcode serves `medium_large` + frontend renders `16:10` cover.

**Packages show "No packages available"?** They must be **Published** and their **Active (visible in configurator)** box ticked (package edit screen). If you upgraded from ≤1.0.0, update to 1.1.0+ — 1.0.0 had a broken `meta_query` that always returned an empty list.

**How to change the card shadow?** Bookings → Settings → **Card shadow** — `Auto` keeps the built-in light/dark shadows, or pick `None / Subtle / Medium / Strong`. Applies to package cards, step cards and the summary panel.

---

## Changelog

### 1.1.8
- **Fix:** Settings toggles (Email field, Proxy IP headers, plus the older Message field and images) could be turned on but never off — unchecked checkboxes weren't saved. Value is now sent explicitly, so they toggle both ways.
- **Fix:** "Trust proxy IP headers" now defaults to ON when the option is missing — existing installs no longer fall back to the proxy's IP (shared rate-limit bucket) until an admin re-saves settings.

### 1.1.7
- **Feat:** new "Email field" setting — hide the email input in the form (and skip customer email notifications) when you'd rather contact customers by phone
- **Feat:** bulk actions on the Bookings list — mark selected bookings Pending / Confirmed / Cancelled / Completed in one click (status + title stay in sync)
- **Fix:** admin list now shows the **Booking Date** column instead of a redundant "created" date — see the session date at a glance
- **Fix:** success message scroll — the form collapses first, then scrolls the confirmation into view
- **Fix:** email failures are no longer silent — send results are stored per booking and the booking screen warns when notifications failed; the resend button now reports per-recipient results
- **Fix:** visitor IP detection no longer trusts `X-Forwarded-For` blindly — header spoofing can't bypass rate limits; forwarding headers are only honored behind a real proxy (configurable under Settings → Proxy IP headers)
- **Fix:** plugin uninstall now genuinely deletes all bookings/packages/extras, settings, and transient caches (including customer PII snapshots) on single and multisite installs

### 1.1.6
- **Fix:** success message never appeared after a booking — it sat inside the form layout that gets hidden on submit; moved out so it stays visible (now also scrolled into view)
- **Fix:** bookings hidden from the admin "All" list — custom statuses now use `protected => true` (like WP's draft/pending) so `show_in_admin_all_list` is honored

### 1.1.5
- **Fix:** submissions that succeed showed "Submission failed" — frontend now recognizes the REST success response (`bookingId`); error responses now show their real server message
- **Fix:** phone country dropdown options invisible on hover — `color-scheme` now follows the form's theme mode (light/dark) instead of the OS, and options use explicit card/text colors
- **Fix:** Solo/Couple pills had no hover state — inactive pills now tint with the main color, active pill uses the configured primary-button hover color
- **Feat:** duplicate-booking guard — a per-submit `client_ref` idempotency key makes REST/AJAX retries return the existing booking instead of inserting twice
- **Feat:** flag map extended to full ITU E.164 coverage (~200 dial codes) — any code added under Settings → Phone countries gets a real flag

### 1.1.4
- **Feat:** color settings with Light/Dark pairs: **Main color** (buttons, active states, focus), **Primary button hover**, **Text color**, **Muted color** — defaults match the previous hardcoded values, nothing changes until you edit
- Button hover now uses a real background color (`--nsbc-btn-hover`, auto-derived from main color when empty) instead of an opacity fade
- All color values are hex-validated server-side before reaching CSS

### 1.1.3
- **Fix:** uniform card spacing — removed `.nsbc-card` margin that double-counted with layout gaps (50px vs 28px between sections); cards now use a single flex gap so top/bottom/internal spacing is identical
- **Fix:** package cards align — excerpt slot always reserved, price pinned to card bottom, so all cards in a row match height and title/price positions
- **Feat:** summary package image fades out/in when selecting a different package (respects `prefers-reduced-motion`)

### 1.1.2
- **Revert:** removed default/automatic extras images from 1.1.1 — extras stay imageless and render as plain text checkboxes in the form; the manual icon fields in the extra edit screen still exist if ever needed

### 1.1.1
- **Feat:** every extra gets an image — seeded & new extras receive a title-matched Dashicon (Flying Dress → format-image, Classic Car → car, …); one-time backfill assigns icons to existing imageless extras; saving an extra with no image falls back to a default Dashicon
- **Fix:** release zip no longer contains an empty `dist/` folder (CI built inside the checkout)

### 1.1.0
- **Fix:** packages & extras never appeared on the frontend ("No packages available.") — `meta_query` nested `relation` incorrectly, producing an impossible AND condition; always returned 0 results
- **Feat:** new setting **Card shadow** (`auto/none/subtle/medium/strong`) controlling `--nsbc-shadow` on all configurator cards

### 1.0.0
- Initial release

---

## License

GPLv2 or later — see `https://www.gnu.org/licenses/gpl-2.0.html`
