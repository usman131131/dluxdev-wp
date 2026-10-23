# Dlux Booking 4.2 — Setup Guide

Car wash and detailing bookings, paid by card through **Stripe Checkout**, stored in **WordPress**. Everything is built with **Secure Custom Fields (SCF)**.

| What | Where |
|---|---|
| Stripe keys, opening hours, pages, emails | **Theme Settings › Booking & Payments** box |
| Wash packages, detailing packages, extras | **Theme Settings › Booking Prices** (its own page) |
| Bookings | **Bookings** in the admin menu (post type `dlux_booking`, SCF fields) |

The homepage **Pricing** section is separate: it's only what visitors see on the homepage. The booking page reads **Booking Prices** only.

Requires **Secure Custom Fields** (or ACF Pro) and PHP 7.4+.

---

## 1. Install / update

1. **Plugins › Add New › Upload Plugin**, choose `dlux-booking.zip`, then **Replace current with uploaded**.
2. If it was not active, activate it.
3. You'll now see a **Bookings** menu, and a new **Booking & Payments** box at the bottom of **Theme Settings**.

**Updating from 4.1:** on the first page load after updating, your wash prices are converted to the new layout automatically: sizes go into the Vehicle sizes list, and each wash package gets a price per size. Prices, extras, detailing packages and bookings are unchanged. Open **Booking Prices** to check.

**Updating from 4.0:** on the first page load after updating, the plugin copies the prices from your homepage Pricing section and your old Add-ons into **Booking Prices**, so booking keeps working straight away. This only happens once, and only if Booking Prices is empty. After that, the homepage and Booking Prices are completely separate. Check **Theme Settings › Booking Prices**, then add your Detailing packages.

**Updating from 3.x:** the old Supabase settings are deleted automatically. Old bookings in Supabase are **not** imported, so export them from the Wash Desk app first if you need them.

## 2. Create the two pages

| Page | Content |
|---|---|
| **Book** (e.g. `/book/`) | `[dlux_booking]` |
| **Booking confirmed** (e.g. `/booking-confirmed/`) | `[dlux_confirmation]` |

`[dlux_menu]` (a price list) still works if you want it anywhere. `[dlux_memberships]` now outputs nothing; remove it from any page.

## 3. Stripe

### 3a. API keys
1. Stripe Dashboard › **Developers › API keys**.
2. Copy the **Secret key** (`sk_test_…`) into **Theme Settings › Booking & Payments › Stripe › Test secret key**.
3. Later, do the same with the live key (`sk_live_…`) in **Live secret key**.

You don't need the publishable key; Stripe Checkout is a hosted page.
You can use a **restricted key** instead (`rk_…`) with **Checkout Sessions: Write** permission.

### 3b. Webhook (strongly recommended)
The webhook confirms bookings even if a customer closes the tab right after paying, and marks refunds.

1. Stripe › **Developers › Webhooks › Add endpoint**.
2. Endpoint URL (also shown in the settings box):
   `https://YOUR-SITE/wp-json/dlux/v1/stripe-webhook`
3. Events:
   - `checkout.session.completed`
   - `checkout.session.async_payment_succeeded`
   - `checkout.session.async_payment_failed`
   - `checkout.session.expired`
   - `charge.refunded`
4. Copy the **Signing secret** (`whsec_…`) into **Test webhook signing secret**.
5. When going live, create the same endpoint in Live mode and paste its secret into **Live webhook signing secret**.

### 3c. Test, then go live
1. Leave **Mode = Test mode**. Book a wash using card `4242 4242 4242 4242`, any future expiry, any CVC.
2. Check the booking shows **Confirmed / Paid online** under **Bookings**, and that both emails arrived.
3. Switch **Mode** to **Live mode**.

## 4. Booking settings (Booking tab)

| Setting | Notes |
|---|---|
| Booking page / Confirmation page | Pick the pages from step 2. |
| Time slot length | e.g. 30 → 8:00, 8:30, 9:00… |
| Cars per time slot | 1 = one booking per time. 2 = two cars at once, etc. |
| Book up to (days ahead) | Default 30. |
| Minimum notice | Hides same-day times sooner than this (minutes). |
| Online booking hours | Rows of *days + open + close*. E.g. Mon–Fri 8:00–17:00, Sat 8:00–15:00. Days not listed are closed. Empty = 8am–5pm daily. The last time offered is one slot before closing. |
| Closed dates | Public holidays etc. |

**Business & emails tab:** business name, phone and address appear on the Stripe page, the confirmation page and emails. "Send new booking emails to" accepts several addresses separated by commas.

Also check **Settings › General › Timezone** is your city (e.g. Sydney). Slots use the site's timezone.

## 5. Booking Prices

**Theme Settings › Booking Prices** has three tabs. Click **Save prices** after changes.

### Vehicle sizes tab
The sizes customers choose from, in display order (e.g. Sedan, SUV/SW, Large, Extra Large). Both the Wash and Detailing tabs pick sizes from this list.

- **Add a size:** add a row, click **Save prices**, then use it in the Size dropdowns on the other tabs.
- **Rename a size:** just change its name and save. Every wash and detailing price that used the old name moves to the new name automatically.
- **Reorder:** drag the rows. Only the display order changes.
- **Remove a size:** delete the row. Prices that used it stop being offered. They show as "(removed — choose another size)" in the dropdowns and are listed on **Bookings › Prices & setup**.

### Wash packages tab and Detailing tab
Both tabs use the same fields for each package:

```
Package
 ├ name, subtitle, duration, features, featured, badge
 ├ Price per vehicle size:  Size (dropdown) + Price     → one row per size you offer
 └ Extras for this package: name + price                → Detailing only
```

- A size with no row isn't offered for that package. For example, give detailing packages no Extra Large row and Extra Large won't appear under Detailing.
- **Wash extras** (Wash tab, below the packages) are offered with **every** wash package: Spray wax, Engine bay clean, Steam seats, Rubber mats wash…
- **Detailing extras** belong to **one** package and only appear when it's chosen. For example:
  - *Full Detail*: Leather conditioner $80, Headlights Option A $40, Headlights Option B $80
  - *Ceramic Coating – New car*: no extras
  - If an extra applies to several packages, add it to each one.
- **New car / used car** prices (Ceramic Coating): add two packages, "Ceramic Coating – New car" and "Ceramic Coating – Used car", each with its own size prices.
- Prices are charged as entered. If a price is a "from" price, say so in the subtitle (e.g. "From price, confirmed on inspection").

### Headings
Each tab has a "Heading on the booking page" (defaults: **Car wash** / **Detailing**). These label the switch customers use on the booking page. If only one tab has packages, the switch is hidden.

### Durations
Optional, in minutes. Shown on the package cards ("⏱ 240 mins") and used for the length of the "Add to calendar" event. They don't block extra time slots; use **Cars per time slot** to control capacity.

**Bookings › Prices & setup** shows exactly what the booking page will offer, with every package, size price and extra. Check it after changing prices.

### Linking to a package
`/booking/?package=superior&size=Large` opens the booking page with that package and size already chosen (the package name or its id both work). `?service=detailing` opens on the Detailing tab.
Homepage pricing-card buttons that link to the booking page get the package and size added automatically, matched by package name.

## 6. How a booking works

1. Customer picks Car wash or Detailing, vehicle size, package, that package's extras, day and time, then enters details.
2. WordPress creates a **Booking** (status *Awaiting payment*) and **holds the time slot for 35 minutes**.
3. Customer is sent to **Stripe Checkout**.
4. **Paid** → returned to the confirmation page; booking becomes **Confirmed / Paid online**; customer and shop emails are sent (once).
5. **Back / cancelled** on Stripe → booking is cancelled and the slot is freed immediately.
6. **Abandoned** → after 35 minutes the slot is free again; an hourly job tidies up and closes the Stripe page.

Refunds are made in Stripe. With the webhook set up, the booking changes to **Refunded / Cancelled** automatically.

## 7. Managing bookings (admin)

**Bookings › All bookings**
- Columns: when, customer (rego, name, mobile, vehicle), service (Car wash / Detailing) with package and extras, total, payment, status.
- Quick links: **Today · Tomorrow · Upcoming · Past**. Filters: status, payment, a specific date. Search by rego or name.
- Click a booking to edit: change status (Confirmed → Completed / No show / Cancelled), customer details, date/time. The **Payment** box links to the payment in Stripe.

**Add booking** — for phone or walk-in bookings. Default payment is *Pay at shop*. These also take up the time slot, so online customers can't double-book it.

Who can see bookings: Administrators and Editors.

## 8. Caching & security notes

- The booking and confirmation pages send no-cache headers. If you use a caching plugin or a CDN, also exclude `/wp-json/dlux/` and those two pages.
- Each booking has a secret link token; the confirmation page only shows a booking with the right token.
- Webhooks are verified with the Stripe signing secret; unsigned requests are rejected.
- A database lock prevents two customers taking the last space in a slot at the same moment.
- Basic spam protection: hidden honeypot field and max 8 booking attempts per visitor per 15 minutes.

## 9. Troubleshooting

| Problem | Fix |
|---|---|
| "Online booking isn't available right now" | No packages with prices. Fill in **Theme Settings › Booking Prices** and save. |
| A package doesn't appear | It needs at least one row in "Price per vehicle size". It's also hidden for sizes it has no price for. **Prices & setup** lists packages with no prices. |
| A size is missing from the Size dropdown | Add it on the **Vehicle sizes** tab, click **Save prices**, then use it. |
| A dropdown shows "(removed — choose another size)" | That size was deleted from the Vehicle sizes list. Pick a current size for that row, or delete the row. |
| "Online booking is not set up yet" | No Stripe secret key for the current mode. |
| "We couldn't start the payment" | Stripe rejected the request. Wrong key for the mode, or currency not enabled on your Stripe account. The reason is written to the PHP error log. |
| No times shown | Check Online booking hours, Closed dates, Minimum notice and the site timezone. |
| Bookings stay "Awaiting payment" after paying | Webhook missing or wrong secret. Stripe › Webhooks shows failed deliveries. (The confirmation page and hourly job will still catch up.) |
| No emails | WordPress can't send mail on many hosts. Install an SMTP plugin (e.g. WP Mail SMTP). |
| Hourly clean-up not running | WP-Cron needs site visits. On quiet sites, set up a real server cron for `wp-cron.php`. |

## 10. For developers

```
dlux-booking.php            bootstrap, install/upgrade, cron
includes/settings.php       Booking & Payments fields on Theme Settings (options_dlux_bk_*), dlux_bk_opt()
includes/cpt.php            dlux_booking post type, SCF fields (meta bk_*), admin list
includes/prices.php         Booking Prices options page + fields (post_id dlux_prices → options dlux_prices_*), size renames, dlux_bk_menu(), upgrades from 4.0/4.1
includes/availability.php   hours, slots, holds
includes/stripe.php         Stripe API (wp_remote_request), Checkout, webhook, sync, clean-up
includes/emails.php         confirmation emails
includes/rest.php           REST API (dlux/v1)
includes/admin.php          Prices & setup page
includes/frontend.php       shortcodes, assets, pricing-card link helper
assets/                     widget JS/CSS
```

REST (`/wp-json/dlux/v1/`): `GET menu` (groups › packages › extras), `GET availability?day=Y-m-d`, `POST booking`, `GET booking-status?id=&t=`, `POST booking-cancel`, `POST stripe-webhook`.

Booking Prices fields (`get_field( $name, 'dlux_prices' )`): `sizes` › `label`; `wash_title`; `wash_packages` › (`name`, `subtitle`, `duration`, `features`, `is_featured`, `badge`, `prices` › (`size`, `price`)); `wash_extras` › (`name`, `price`); `detail_title`; `detail_packages` › (same fields as wash packages, plus `extras` › (`name`, `price`)).

Package ids: wash `w-<name>`, detailing `d-<name>`. Extra ids: `wx-<name>`, `dx-<package>-<name>`. The server only accepts extras that belong to the chosen package and recalculates every price.

Booking meta keys: `bk_status`, `bk_payment`, `bk_date` (Ymd), `bk_time` (H:i:s), `bk_service`, `bk_vehicle_size`, `bk_package`, `bk_addons`, `bk_total`, `bk_name`, `bk_phone`, `bk_email`, `bk_rego`, `bk_vehicle`, `bk_notes`, `bk_channel`, `bk_consent`, `bk_stripe_session`, `bk_stripe_payment_intent`, `bk_amount_paid`, `bk_paid_at`.

Filters: `dlux_booking_menu`, `dlux_booking_hours`, `dlux_booking_checkout_params`, `dlux_booking_customer_email`, `dlux_booking_admin_email`.
Actions: `dlux_booking_created`, `dlux_booking_paid`, `dlux_booking_cancelled`.
