# Dlux Booking: Wash Desk app API

Added in this version: `includes/app-api.php` (plus one `require` line in `dlux-booking.php`).
Nothing else in the plugin changed. Website booking, Stripe, emails and SMS work exactly as before.

## Setup (wp-config.php)

If the Wash Desk app is hosted on a **different domain** from WordPress (e.g. Netlify), allow it:

```php
define( 'DLUX_APP_ORIGINS', 'https://dlux-wash.netlify.app' ); // comma-separate several
```

Optional:

```php
define( 'DLUX_APP_IDLE_HOURS', 12 ); // sign out after this long unused (default 12)
define( 'DLUX_APP_MAX_DAYS', 7 );    // sign in again at least this often (default 7)
```

The site must use **HTTPS**. Behind a proxy/CDN, make sure WordPress knows it's HTTPS
(`is_ssl()` must be true), or sign-in is refused. `define( 'DLUX_APP_ALLOW_HTTP', true );`
exists for local testing only; never use it on a live site.

Permalinks must not be "Plain" (the app uses `/wp-json/`).

## Endpoints (namespace `dlux/v1`)

| Method | Route | Needs |
|---|---|---|
| POST | `/app/login` | `{ "username", "password" }` → `{ token, user }` |
| POST | `/app/logout` | token |
| GET | `/app/me` | token |
| GET | `/app/bookings?from=Y-m-d&to=Y-m-d&page=1&per_page=200` | token (max 400 days, 500 per page) |
| GET | `/app/bookings/{id}` | token |
| PUT | `/app/bookings/{id}` | token. Creates or updates. `id` is the app's own id or `wp-123` |
| DELETE | `/app/bookings/{id}` | token. Moves the booking to the Trash |

Send the token as `Authorization: Bearer <token>`. If your host strips that header, the app's
`X-Dlux-Token: <token>` header is accepted instead.

## Security

- Only users with `manage_options` (administrators) who can manage bookings can sign in
  (filter `dlux_app_login_capability` to change). Capabilities are re-checked on every request.
- Sign-in goes through `wp_authenticate()`, so security/2FA plugins and `wp_login_failed` still apply.
- Failed sign-ins are limited to 8 per username and 20 per IP address per 15 minutes.
  (The IP is `REMOTE_ADDR`; behind a proxy use the `dlux_app_client_ip` filter.)
- Tokens are 256-bit random values. Only an HMAC of each token is stored (user meta
  `_dlux_app_sessions`), max 10 devices per user. They expire (idle and absolute), are revoked on
  sign-out, and **all are revoked when the user's password changes**. Changing the WordPress
  `AUTH_SALT` also signs every device out.
- Tokens only work on the `/app/` routes, never as a WordPress login. Cookies are ignored on
  these routes (no CSRF), and CORS only answers the origins in `DLUX_APP_ORIGINS`, never with credentials.
- All input is validated, length-limited and sanitised. The app's own extra data (job steps,
  condition checks, texts sent) is kept as JSON in `_dlux_app`, max 200 KB, plain values only.
- The app can't change payment fields. An online booking still in Stripe Checkout stays
  "Awaiting payment", so abandoned-checkout clean-up still works.

## How app statuses map to WordPress

| App | WordPress status |
|---|---|
| Booked, Checked in, In progress, Ready | Confirmed (or stays Awaiting payment while unpaid online) |
| Collected, Paid | Completed |
| Cancelled / No-show | Cancelled / No show |

If you cancel or complete a booking in WordPress admin, the app follows. Bookings the app creates
are "Pay at shop", count towards website availability, and don't send the website's emails/SMS
(the app sends its own texts).
