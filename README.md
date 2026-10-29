# DLux Wash — WordPress `wp-content`

Source for the **DLux Hand Car Wash & Detailing** website (dluxwash.com.au): the custom theme, the custom booking plugin and the must-use plugin. WordPress core, the database and uploaded media are **not** in this repository.

| | |
|---|---|
| **Theme** | `themes/dlux-3` — DLUX Car Care, v1.9.1 |
| **Plugin** | `plugins/dlux-booking` — Dlux Booking, v5.0.1 |
| **Requires** | WordPress 6.0+, PHP 7.4+, Secure Custom Fields (SCF) or ACF Pro |
| **Payments** | Stripe Checkout (card) or pay at the shop |
| **Notifications** | Branded HTML emails, optional ClickSend SMS |

---

## Contents

1. [Repository layout](#1-repository-layout)
2. [What is tracked in Git](#2-what-is-tracked-in-git)
3. [Local development](#3-local-development)
4. [Theme: DLUX Car Care (`dlux-3`)](#4-theme-dlux-car-care-dlux-3)
5. [Plugin: Dlux Booking (`dlux-booking`)](#5-plugin-dlux-booking-dlux-booking)
6. [Booking flow](#6-booking-flow)
7. [Emails](#7-emails)
8. [REST API](#8-rest-api)
9. [Hooks for developers](#9-hooks-for-developers)
10. [Deployment](#10-deployment)
11. [Troubleshooting](#11-troubleshooting)
12. [Further documentation](#12-further-documentation)

---

## 1. Repository layout

```
wp-content/
├── README.md                       this file
├── .gitignore                      what is and isn't tracked
├── index.php
├── mu-plugins/
│   └── hostinger-auto-updates.php  host's must-use plugin
├── plugins/
│   ├── .gitignore                  tracks dlux-booking only
│   └── dlux-booking/               custom booking plugin (see section 5)
└── themes/
    └── dlux-3/                     custom theme (see section 4)
```

Third-party plugins on the site (installed from WordPress.org, **not** tracked):

| Plugin | Purpose |
|---|---|
| Secure Custom Fields | Field groups, options pages and page sections used by the theme and plugin |
| Widget Google Reviews | Google reviews on the site |
| WP Mail Logging | Log of every email the site sends (WP Mail Logging › Email Log) |

---

## 2. What is tracked in Git

Tracked:

- `themes/` (the DLUX theme)
- `plugins/dlux-booking/` only — every other plugin is ignored by `plugins/.gitignore`
- `mu-plugins/`

Ignored (see `.gitignore`):

- `uploads/` (media library)
- `logs/`, `wflogs/`, `debug.log`, `*.log`
- `upgrade/`, `cache/`, `upgrade-temp-backup/`
- `.env*` secrets, database exports (`*.sql`, `*.sql.gz`, `*.sqlite`), backups (`*.bak`, `*.backup`)
- OS / editor files (`.DS_Store`, `Thumbs.db`, `.vscode/`, `.idea/`)

> Never commit Stripe keys or database dumps. These live in the WordPress database (Theme Settings), not in files.

---

## 3. Local development

The site runs locally with **Local** (by WP Engine):

```
C:\Users\Administrator\Local Sites\dluxwash\app\public\wp-content   ← this repository
```

1. Create or import the site in Local and point it at this `wp-content` folder.
2. Install and activate **Secure Custom Fields** (or ACF Pro).
3. Activate the **DLUX Car Care** theme and the **Dlux Booking** plugin.
4. Install **WP Mail Logging** to inspect outgoing emails locally.
5. Leave Stripe in **Test mode** and use card `4242 4242 4242 4242`, any future expiry, any CVC.

Git remote:

```bash
git remote -v
# origin  https://github.com/usman131131/dluxdev-wp.git
git pull origin main
git push origin main
```

---

## 4. Theme: DLUX Car Care (`dlux-3`)

A responsive theme built around an SCF **flexible-content page builder** and a **Theme Settings** options page.

### 4.1 Files

```
themes/dlux-3/
├── style.css                   theme header (styles are in assets/css/main.css)
├── functions.php               setup, menus, image sizes, asset loading, colour variables
├── header.php / footer.php
├── index.php / page.php / 404.php
├── template-coming-soon.php    "Coming Soon" page template (no menus)
├── inc/
│   ├── helpers.php             dlux_option() and shared helpers
│   ├── icons.php               inline SVG social icons
│   ├── booking.php             bridge to the plugin's price list (dlux_bk_menu())
│   ├── contact.php             Contact Us form (AJAX, no form plugin)
│   └── voucher-popup.php       gift voucher popup (content comes from the plugin)
├── template-parts/
│   ├── page-builder.php        loops "Page Sections" and loads sections/<layout>.php
│   ├── package-card.php        one pricing / package card
│   ├── content-page.php
│   └── sections/               one file per page-builder layout
├── assets/
│   ├── css/main.css
│   ├── js/main.js, carousel.js, booking-select.js, contact.js, voucher.js
│   ├── fonts/                  Nokia Expanded, Sequel Sans, Poppins (self-hosted)
│   └── vendor/slick/           Slick carousel 1.8.1
├── readme.txt                  theme setup notes
└── dlux-3.zip                  packaged copy of the theme for upload
```

### 4.2 Page sections

Pages are built in **Page Sections**. Each layout has a matching file in `template-parts/sections/`:

| Layout | File | Anchor id |
|---|---|---|
| Hero | `hero.php` | — (header turns transparent over it) |
| Pricing | `pricing.php` | `#prices` |
| Services ("Beyond the wash") | `services.php` | `#detailing` |
| How it works | `how_it_works.php` | |
| Promo cards | `promo_cards.php` | from each card label, e.g. `#gift-vouchers` |
| Reviews | `reviews.php` | |
| Locations | `locations.php` | `#visit` |
| CTA banner | `cta_banner.php` | |
| Content body | `content_body.php` | |
| Contact Us | `contact_us.php` | |

Text tokens `{year}`, `{rating}` and `{since}` work in headings, eyebrows, stats and copyright. Wrap words in `<span>…</span>` to show them in the accent colour.

### 4.3 Assets

Scripts load only where they are needed:

| Script | Loaded when the page has |
|---|---|
| `main.js`, `main.css` | every page |
| Slick + `carousel.js` | a Services section |
| `booking-select.js` | a Pricing or Services section |
| `contact.js` | a Contact Us section |

CSS/JS versions use the file's modified time, so browsers pick up changes without a version bump. Accent, background and surface colours from Theme Settings are written as CSS variables (`--accent`, `--bg`, `--surface`).

### 4.4 Admin setup

1. **Theme Settings**: logo, colours, booking URL, Google rating, locations, header buttons, socials, copyright.
2. **Appearance › Menus**: assign **Primary Menu** and **Footer Menu**.
3. Build the homepage in **Page Sections**, then set it under **Settings › Reading**.

---

## 5. Plugin: Dlux Booking (`dlux-booking`)

Online car wash and detailing bookings, stored in WordPress, paid by card through Stripe Checkout or at the shop.

### 5.1 Files

```
plugins/dlux-booking/
├── dlux-booking.php            bootstrap, install/upgrade, hourly clean-up cron
├── README.md                   full setup guide (Stripe, prices, settings)
├── APP-API.md                  Wash Desk app API
├── assets/
│   ├── dlux-booking.js         booking widget
│   └── dlux-booking.css
└── includes/
    ├── settings.php            Booking & Payments settings (options_dlux_bk_*), dlux_bk_opt(), dlux_bk_business()
    ├── cpt.php                 "Bookings" post type (dlux_booking), fields (meta bk_*), admin list, dlux_bk_booking()
    ├── prices.php              Booking Prices page, vehicle sizes, packages, extras, dlux_bk_menu()
    ├── availability.php        opening hours, time slots, slot holds
    ├── stripe.php              Stripe Checkout, webhook, sync, clean-up
    ├── email-template.php      branded email templates + senders
    ├── sms.php                 booking SMS via ClickSend + SMS log
    ├── emails.php              booking confirmation emails (customer + shop)
    ├── rest.php                booking widget REST API
    ├── app-api.php             Wash Desk app REST API
    ├── admin.php               Bookings › Prices & setup page
    ├── frontend.php            shortcodes and front-end assets
    └── voucher.php             gift voucher popup content and sign-ups
```

### 5.2 Shortcodes

| Shortcode | Use |
|---|---|
| `[dlux_booking]` | Booking page |
| `[dlux_confirmation]` | "Booking confirmed" page (Stripe returns customers here) |
| `[dlux_menu]` | Price list |
| `[dlux_memberships]` | Retired — outputs nothing |

### 5.3 Where things are set in the admin

| What | Where |
|---|---|
| Stripe keys, booking hours, slot length, pages, business details, notification email | **Theme Settings › Booking & Payments** |
| Vehicle sizes, wash packages, detailing packages, extras | **Theme Settings › Booking Prices** |
| Gift voucher popup and sign-ups | **Theme Settings › Gift Voucher Popup** |
| Bookings | **Bookings** menu |
| Check what the booking page offers | **Bookings › Prices & setup** |

### 5.4 Booking data

Each booking is a `dlux_booking` post. Fields are stored as post meta with the `bk_` prefix:

`bk_status`, `bk_payment`, `bk_date`, `bk_time`, `bk_service`, `bk_vehicle_size`, `bk_package`, `bk_option`, `bk_addons`, `bk_total`, `bk_name`, `bk_phone`, `bk_email`, `bk_rego`, `bk_vehicle`, `bk_notes`, `bk_channel`, `bk_consent`, `bk_stripe_session`, `bk_stripe_payment_intent`, `bk_amount_paid`, `bk_paid_at`.

Internal meta: `_dlux_items` (package + extras with prices), `_dlux_mins` (duration), `_dlux_token` (confirmation link), `_dlux_mailed_customer` / `_dlux_mailed_admin` (email sent flags), `_dlux_late_payment`.

`dlux_bk_booking( $id )` returns everything about a booking in one array.

---

## 6. Booking flow

1. Customer chooses Car wash or Detailing, vehicle size, package, extras, day and time, then enters their details.
2. A booking is created and the time slot is **held for 35 minutes**.
3. **Card**: customer goes to Stripe Checkout. When paid, the booking becomes *Confirmed / Paid online*.
   **Pay at the shop**: the booking is confirmed straight away.
4. Confirmation emails are sent **once** to the customer and the shop.
5. Cancelled checkout frees the slot immediately; abandoned checkout frees it after 35 minutes (hourly clean-up).
6. Refunds are made in Stripe; with the webhook set up the booking becomes *Refunded / Cancelled*.

Stripe webhook URL: `https://YOUR-SITE/wp-json/dlux/v1/stripe-webhook`

---

## 7. Emails

All booking emails are HTML with a plain-text alternative.

| Email | Recipient | Template | Subject |
|---|---|---|---|
| Booking confirmed | Customer | `dlux_bk_admin_booking_email_html()` with `for => customer` | `Your booking is confirmed: <day>, <time>` |
| New booking | Shop (Notify email, or the site admin email) | `dlux_bk_admin_booking_email_html()` | `New booking: <rego>, <day>, <time>` |
| Gift voucher sign-up | Subscriber + shop | `dlux_bk_email_html()` (dark template) | set on the Gift Voucher Popup page |
| Contact Us | Shop + sender | `dlux_bk_email_html()` (dark template) | set in the theme |

### 7.1 Booking confirmation design

The customer and shop booking emails share one design (white/black with DLux pink `#D81E4A`, Barlow fonts):

- Header with booking reference `#DL-<booking id>`
- Day, start time and arrival window (start + slot length)
- Number plate (rego)
- Service, package, duration and any option / extras / vehicle / notes
- Vehicle size scale from **Booking Prices › Vehicle sizes**, booked size highlighted
- Customer name, mobile and email
- Payment box: *Pay at the shop* or *Paid by card*, with the total
- "Good to know" notes, terms link, shop details, socials and Google review link

Differences between the two:

| | Shop | Customer |
|---|---|---|
| Call / Text / Email customer buttons | Yes | No |
| "Found us via" | Yes | No |
| Late-payment warning | Yes, when paid after checkout timed out | No |
| Main button | **Open booking** (wp-admin) | **Get directions** (Google Maps, when a business address is set) |

The terms link defaults to `/terms-and-conditions/`; change it with the `dlux_booking_terms_url` filter. Each email is sent once per booking — make a new booking to test changes.

### 7.2 Sending

`dlux_bk_mail()` sends the dark template; `dlux_bk_mail_raw()` sends ready-made HTML. Both add the plain-text alternative. Many hosts can't send mail without SMTP — use an SMTP plugin on the live site. Check **WP Mail Logging › Email Log** to see what was sent.

---

### 7.3 Booking SMS (ClickSend)

Optional texts to the customer and the shop, sent once per booking together with the confirmation emails. Set up in **Theme Settings › Booking & Payments › SMS (ClickSend)**: switch on, ClickSend username + API key (ClickSend › Developers › API Credentials), optional sender, shop numbers and message templates (tags such as `{first_name}`, `{when}`, `{package}`, `{total}`).

Every attempt (sent, failed or skipped, with ClickSend's reply) is listed in **Bookings › SMS log**, which also has a **Send test SMS** button.

## 8. REST API

Namespace `dlux/v1` (`/wp-json/dlux/v1/…`).

**Booking widget**

| Method | Route | Purpose |
|---|---|---|
| GET | `/menu` | Packages, sizes and extras |
| GET | `/availability?day=Y-m-d` | Free time slots |
| POST | `/booking` | Create a booking (+ Stripe Checkout session) |
| GET | `/booking-status?id=&t=` | Booking for the confirmation page |
| POST | `/booking-cancel` | Cancel an unpaid checkout |
| POST | `/stripe-webhook` | Stripe events (signature verified) |

**Wash Desk app** (token auth, administrators only) — see `plugins/dlux-booking/APP-API.md`:

`POST /app/login`, `POST /app/logout`, `GET /app/me`, `GET /app/bookings`, `GET|PUT|DELETE /app/bookings/{id}`.

---

## 9. Hooks for developers

**Filters**

| Filter | Changes |
|---|---|
| `dlux_booking_hours` | Opening hours for a day |
| `dlux_booking_checkout_params` | Stripe Checkout parameters |
| `dlux_booking_customer_email_args` | Customer email data (subject line, plain text) |
| `dlux_booking_admin_email_args` | Shop email data (plain text) |
| `dlux_booking_admin_email` | Shop notification address |
| `dlux_booking_terms_url` | Terms link in booking emails |
| `dlux_email_logo_url` | Logo in the dark email template |
| `dlux_app_login_capability`, `dlux_app_client_ip`, `dlux_app_allowed_origins` | Wash Desk app API |

**Actions**

| Action | Fires when |
|---|---|
| `dlux_booking_created` | A booking is created |
| `dlux_booking_paid` | A card payment is confirmed |
| `dlux_booking_cancelled` | A booking is cancelled |

---

## 10. Deployment

1. Commit and push changes to `main`.
2. On the live site, update the theme and/or plugin:
   - **Theme**: upload `themes/dlux-3` (or a fresh zip of it) via **Appearance › Themes › Add New › Upload**, replacing the current version.
   - **Plugin**: zip `plugins/dlux-booking` and upload via **Plugins › Add New › Upload Plugin › Replace current with uploaded**.
3. The plugin runs its upgrade routine on the first page load after a version change.
4. Clear any page cache / CDN. Exclude `/wp-json/dlux/`, the booking page and the confirmation page from caching.
5. Make a test booking and check both emails in the email log.

---

## 11. Troubleshooting

| Problem | Check |
|---|---|
| Booking page says booking isn't available | Packages need prices in **Theme Settings › Booking Prices** |
| No times shown | Online booking hours, closed dates, minimum notice and **Settings › General › Timezone** |
| Bookings stay "Awaiting payment" | Stripe webhook URL and signing secret |
| No emails | SMTP on the host; **WP Mail Logging** shows what was sent |
| Email still shows the old design | Emails are sent once per booking — make a new booking |
| CSS/JS changes not showing | Clear page cache / CDN |

---

## 12. Further documentation

- `plugins/dlux-booking/README.md` — full plugin setup guide (Stripe, prices, settings, admin)
- `plugins/dlux-booking/APP-API.md` — Wash Desk app API and security
- `themes/dlux-3/readme.txt` — theme setup notes
