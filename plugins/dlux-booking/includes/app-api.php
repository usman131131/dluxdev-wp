<?php
/**
 * REST API for the Dlux Wash Desk app (namespace dlux/v1, every route under /app/).
 *
 *   POST   /app/login            administrator username/email + password  ->  access token
 *   POST   /app/logout           revoke the token used for this request
 *   GET    /app/me               who the token belongs to
 *   GET    /app/bookings         bookings in a date range   (?from=Y-m-d&to=Y-m-d&page=1&per_page=200)
 *   GET    /app/bookings/{id}    one booking
 *   PUT    /app/bookings/{id}    create or update a booking (id = the app's own id, or wp-123)
 *   DELETE /app/bookings/{id}    move a booking to the Trash
 *
 * Authentication
 *   Send the token as  "Authorization: Bearer <token>"  (or "X-Dlux-Token: <token>" on hosts that
 *   strip the Authorization header). Tokens:
 *   - are 256-bit random values; only an HMAC of them is stored (user meta), so a database leak
 *     does not leak usable tokens;
 *   - only work on the /app/ routes above (they are NOT a general WordPress login);
 *   - expire after DLUX_APP_IDLE_HOURS of no use (default 12) and DLUX_APP_MAX_DAYS after sign-in
 *     (default 7), and are revoked on sign-out and whenever the user's password changes;
 *   - only work for users with the login capability (default "manage_options", i.e. administrators)
 *     who can also manage bookings. Capabilities are re-checked on every request.
 *   Cookies are ignored on these routes, so they cannot be used for CSRF.
 *   Sign-in is refused over plain HTTP (define DLUX_APP_ALLOW_HTTP true for local testing only) and
 *   failed sign-ins are rate limited per IP address and per username.
 *
 * App on another domain (e.g. Netlify)? Allow its origin in wp-config.php:
 *   define( 'DLUX_APP_ORIGINS', 'https://dlux-wash.netlify.app' );   // comma-separate several
 * Only listed origins get CORS headers; credentials (cookies) are never allowed cross-origin.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ---------------- Settings ---------------- */

/** Capability a user needs to sign in to the app. */
function dlux_app_login_cap() {
	return (string) apply_filters( 'dlux_app_login_capability', 'manage_options' );
}

function dlux_app_idle_seconds() {
	$h = defined( 'DLUX_APP_IDLE_HOURS' ) ? (float) DLUX_APP_IDLE_HOURS : 12;
	return (int) max( 600, min( 30 * DAY_IN_SECONDS, $h * HOUR_IN_SECONDS ) );
}

function dlux_app_max_seconds() {
	$d = defined( 'DLUX_APP_MAX_DAYS' ) ? (float) DLUX_APP_MAX_DAYS : 7;
	return (int) max( HOUR_IN_SECONDS, min( 90 * DAY_IN_SECONDS, $d * DAY_IN_SECONDS ) );
}

/** Most app sign-ins (devices) kept per user; the least recently used is dropped. */
define( 'DLUX_APP_MAX_SESSIONS', 10 );

/** Statuses used by the app. */
function dlux_app_statuses() {
	return array( 'booked', 'arrived', 'in_progress', 'ready', 'done', 'paid', 'cancelled', 'no_show' );
}

/* ---------------- Routes ---------------- */

add_action(
	'rest_api_init',
	function () {
		$ns  = 'dlux/v1';
		$id  = '(?P<id>wp-\d{1,20}|[a-z0-9]{6,40})';
		$any = array( 'id' => array( 'type' => 'string' ) );

		register_rest_route(
			$ns,
			'/app/login',
			array(
				'methods'             => 'POST',
				'callback'            => 'dlux_app_rest_login',
				'permission_callback' => '__return_true', // Public by design: this is where the credentials are checked.
			)
		);
		register_rest_route(
			$ns,
			'/app/logout',
			array(
				'methods'             => 'POST',
				'callback'            => 'dlux_app_rest_logout',
				'permission_callback' => dlux_app_auth( 'edit_dlux_bookings' ),
			)
		);
		register_rest_route(
			$ns,
			'/app/me',
			array(
				'methods'             => 'GET',
				'callback'            => 'dlux_app_rest_me',
				'permission_callback' => dlux_app_auth( 'edit_dlux_bookings' ),
			)
		);
		register_rest_route(
			$ns,
			'/app/bookings',
			array(
				'methods'             => 'GET',
				'callback'            => 'dlux_app_rest_list',
				'permission_callback' => dlux_app_auth( 'edit_others_dlux_bookings' ),
			)
		);
		register_rest_route(
			$ns,
			'/app/bookings/' . $id,
			array(
				array(
					'methods'             => 'GET',
					'callback'            => 'dlux_app_rest_get',
					'permission_callback' => dlux_app_auth( 'edit_others_dlux_bookings' ),
					'args'                => $any,
				),
				array(
					'methods'             => 'PUT',
					'callback'            => 'dlux_app_rest_save',
					'permission_callback' => dlux_app_auth( array( 'edit_others_dlux_bookings', 'publish_dlux_bookings' ) ),
					'args'                => $any,
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => 'dlux_app_rest_delete',
					'permission_callback' => dlux_app_auth( array( 'delete_others_dlux_bookings', 'delete_published_dlux_bookings' ) ),
					'args'                => $any,
				),
			)
		);
	}
);

/* ---------------- Small helpers ---------------- */

function dlux_app_err( $code, $msg, $status ) {
	return new WP_Error( $code, $msg, array( 'status' => $status ) );
}

function dlux_app_ok( $data, $status = 200 ) {
	$r = new WP_REST_Response( $data, $status );
	$r->header( 'Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, private' );
	$r->header( 'Pragma', 'no-cache' );
	return $r;
}

/** HTTPS only, unless explicitly allowed for local development. */
function dlux_app_secure_request() {
	return is_ssl() || ( defined( 'DLUX_APP_ALLOW_HTTP' ) && true === DLUX_APP_ALLOW_HTTP );
}

/** Client IP for rate limiting. Proxy headers are NOT trusted unless a site filter says so. */
function dlux_app_ip() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$ip = (string) apply_filters( 'dlux_app_client_ip', $ip );
	return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : 'unknown';
}

/** Plain text value from a JSON body; anything that isn't a scalar becomes ''. */
function dlux_app_text( $v, $max ) {
	if ( ! is_scalar( $v ) ) {
		return '';
	}
	return mb_substr( sanitize_text_field( (string) $v ), 0, $max );
}

function dlux_app_valid_day( $d ) {
	if ( ! is_string( $d ) || ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $d, $m ) ) {
		return false;
	}
	return checkdate( (int) $m[2], (int) $m[3], (int) $m[1] ) && (int) $m[1] >= 2000 && (int) $m[1] <= 2100;
}

function dlux_app_valid_time( $t ) {
	return is_string( $t ) && preg_match( '/^([01]\d|2[0-3]):[0-5]\d$/', $t );
}

function dlux_app_shift_day( $day, $days ) {
	$d = new DateTimeImmutable( $day . ' 12:00:00', wp_timezone() );
	return $d->modify( ( $days >= 0 ? '+' : '' ) . (int) $days . ' days' )->format( 'Y-m-d' );
}

/* ---------------- Rate limiting of sign-in ---------------- */

function dlux_app_rl_key( $kind, $value ) {
	return 'dlux_app_rl_' . $kind . '_' . substr( hash_hmac( 'sha256', strtolower( (string) $value ), wp_salt( 'nonce' ) ), 0, 32 );
}

function dlux_app_rl_limit( $kind ) {
	return 'ip' === $kind ? 20 : 8; // Failed sign-ins per 15 minutes.
}

function dlux_app_rl_blocked( $kind, $value ) {
	return (int) get_transient( dlux_app_rl_key( $kind, $value ) ) >= dlux_app_rl_limit( $kind );
}

function dlux_app_rl_fail( $kind, $value ) {
	$k = dlux_app_rl_key( $kind, $value );
	set_transient( $k, (int) get_transient( $k ) + 1, 15 * MINUTE_IN_SECONDS );
}

/* ---------------- Tokens (sessions) ---------------- */

function dlux_app_hash( $secret ) {
	return hash_hmac( 'sha256', $secret, wp_salt( 'auth' ) );
}

/** Sessions of a user, without expired ones. */
function dlux_app_sessions( $user_id ) {
	$all = get_user_meta( $user_id, '_dlux_app_sessions', true );
	$all = is_array( $all ) ? $all : array();
	$now = time();
	$out = array();
	foreach ( $all as $sid => $s ) {
		if ( is_array( $s ) && isset( $s['h'], $s['s'], $s['x'] ) && $s['x'] > $now && $s['s'] + dlux_app_idle_seconds() > $now ) {
			$out[ $sid ] = $s;
		}
	}
	return $out;
}

function dlux_app_save_sessions( $user_id, array $sessions ) {
	if ( $sessions ) {
		update_user_meta( $user_id, '_dlux_app_sessions', $sessions );
	} else {
		delete_user_meta( $user_id, '_dlux_app_sessions' );
	}
}

/** New token for a user. Returns the token string (shown once, never stored). */
function dlux_app_issue_token( $user_id ) {
	$sid      = bin2hex( random_bytes( 8 ) );
	$secret   = bin2hex( random_bytes( 32 ) );
	$now      = time();
	$sessions = dlux_app_sessions( $user_id );

	while ( count( $sessions ) >= DLUX_APP_MAX_SESSIONS ) {
		$oldest = null;
		foreach ( $sessions as $k => $s ) {
			if ( null === $oldest || $s['s'] < $sessions[ $oldest ]['s'] ) {
				$oldest = $k;
			}
		}
		unset( $sessions[ $oldest ] );
	}

	$ua               = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
	$sessions[ $sid ] = array(
		'h'  => dlux_app_hash( $secret ),
		'c'  => $now,
		's'  => $now,
		'x'  => $now + dlux_app_max_seconds(),
		'ua' => mb_substr( $ua, 0, 160 ),
	);
	dlux_app_save_sessions( $user_id, $sessions );

	return $user_id . '.' . $sid . '.' . $secret;
}

/** Token sent with the request, or ''. Never read from the URL (it would end up in logs). */
function dlux_app_request_token( WP_REST_Request $r ) {
	$auth = (string) $r->get_header( 'authorization' );
	if ( preg_match( '/^Bearer\s+(\S+)$/i', trim( $auth ), $m ) ) {
		return $m[1];
	}
	return trim( (string) $r->get_header( 'x_dlux_token' ) );
}

/**
 * Check a token. Returns [ user_id, session_id ] or WP_Error.
 */
function dlux_app_verify_token( $token ) {
	$bad = dlux_app_err( 'dlux_app_unauthorized', 'Please sign in again.', 401 );
	if ( ! is_string( $token ) || ! preg_match( '/^(\d{1,20})\.([a-f0-9]{16})\.([a-f0-9]{64})$/', $token, $m ) ) {
		return $bad;
	}
	$user_id  = (int) $m[1];
	$sid      = $m[2];
	$sessions = $user_id ? dlux_app_sessions( $user_id ) : array();
	if ( ! isset( $sessions[ $sid ] ) || ! hash_equals( (string) $sessions[ $sid ]['h'], dlux_app_hash( $m[3] ) ) ) {
		return $bad;
	}
	if ( ! get_userdata( $user_id ) ) {
		return $bad;
	}
	// Sliding idle expiry. Written at most every 5 minutes to keep requests cheap.
	if ( time() - (int) $sessions[ $sid ]['s'] > 5 * MINUTE_IN_SECONDS ) {
		$sessions[ $sid ]['s'] = time();
		dlux_app_save_sessions( $user_id, $sessions );
	}
	return array( $user_id, $sid );
}

/** The session behind the current request (set by the permission check). */
function dlux_app_current( $set = null ) {
	static $cur = null;
	if ( null !== $set ) {
		$cur = $set;
	}
	return $cur;
}

/**
 * Permission check for the app routes: valid token, HTTPS, login capability + route capabilities.
 *
 * @param string|string[] $caps Extra capabilities the route needs.
 */
function dlux_app_auth( $caps ) {
	$caps = (array) $caps;
	return function ( WP_REST_Request $r ) use ( $caps ) {
		dlux_app_current( false );
		if ( ! dlux_app_secure_request() ) {
			return dlux_app_err( 'dlux_app_https', 'The app must connect over HTTPS.', 403 );
		}
		$who = dlux_app_verify_token( dlux_app_request_token( $r ) );
		if ( is_wp_error( $who ) ) {
			wp_set_current_user( 0 ); // Ignore any cookie login on these routes.
			return $who;
		}
		wp_set_current_user( $who[0] );
		foreach ( array_merge( array( dlux_app_login_cap() ), $caps ) as $cap ) {
			if ( ! current_user_can( $cap ) ) {
				return dlux_app_err( 'dlux_app_forbidden', 'Your account is not allowed to do this.', 403 );
			}
		}
		dlux_app_current( $who );
		return true;
	};
}

/* Revoke every app sign-in when the password changes. */
add_action(
	'after_password_reset',
	function ( $user ) {
		if ( $user instanceof WP_User ) {
			delete_user_meta( $user->ID, '_dlux_app_sessions' );
		}
	}
);
add_action(
	'wp_set_password',
	function ( $password, $user_id ) {
		delete_user_meta( (int) $user_id, '_dlux_app_sessions' );
	},
	10,
	2
);
add_action(
	'profile_update',
	function ( $user_id, $old ) {
		$new = get_userdata( $user_id );
		if ( $old instanceof WP_User && $new && ! hash_equals( (string) $old->user_pass, (string) $new->user_pass ) ) {
			delete_user_meta( $user_id, '_dlux_app_sessions' );
		}
	},
	10,
	2
);

/* ---------------- CORS for the app routes ---------------- */

function dlux_app_norm_origin( $o ) {
	$p = wp_parse_url( trim( (string) $o ) );
	if ( empty( $p['scheme'] ) || empty( $p['host'] ) || ! in_array( strtolower( $p['scheme'] ), array( 'https', 'http' ), true ) ) {
		return '';
	}
	return strtolower( $p['scheme'] ) . '://' . strtolower( $p['host'] ) . ( isset( $p['port'] ) ? ':' . (int) $p['port'] : '' );
}

function dlux_app_allowed_origins() {
	$list = defined( 'DLUX_APP_ORIGINS' ) ? explode( ',', (string) DLUX_APP_ORIGINS ) : array();
	$list = (array) apply_filters( 'dlux_app_allowed_origins', $list );
	return array_values( array_filter( array_map( 'dlux_app_norm_origin', $list ) ) );
}

/*
 * WordPress core reflects any Origin and allows credentials on every REST route. For the app
 * routes we replace that with an allow-list and never allow credentials (the app uses a bearer
 * token, not cookies).
 */
add_filter(
	'rest_pre_serve_request',
	function ( $served, $result, $request ) {
		if ( ! $request instanceof WP_REST_Request || 0 !== strpos( (string) $request->get_route(), '/dlux/v1/app/' ) || headers_sent() ) {
			return $served;
		}
		header_remove( 'Access-Control-Allow-Credentials' );
		header_remove( 'Access-Control-Allow-Origin' );
		header_remove( 'Access-Control-Allow-Methods' );
		header_remove( 'Access-Control-Allow-Headers' );
		$origin = dlux_app_norm_origin( get_http_origin() );
		if ( $origin && in_array( $origin, dlux_app_allowed_origins(), true ) ) {
			header( 'Access-Control-Allow-Origin: ' . $origin );
			header( 'Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS' );
			header( 'Access-Control-Allow-Headers: Authorization, Content-Type, X-Dlux-Token' );
			header( 'Access-Control-Max-Age: 600' );
		}
		header( 'Vary: Origin', false );
		header( 'X-Content-Type-Options: nosniff' );
		return $served;
	},
	20,
	3
);

/* ---------------- Auth endpoints ---------------- */

function dlux_app_user_out( WP_User $u ) {
	return array(
		'id'        => (int) $u->ID,
		'name'      => $u->display_name,
		'canDelete' => user_can( $u, 'delete_others_dlux_bookings' ),
	);
}

function dlux_app_rest_login( WP_REST_Request $r ) {
	if ( ! dlux_app_secure_request() ) {
		return dlux_app_err( 'dlux_app_https', 'The app must connect over HTTPS.', 403 );
	}
	$p        = $r->get_json_params();
	$p        = is_array( $p ) ? $p : array();
	$username = is_string( $p['username'] ?? null ) ? trim( $p['username'] ) : '';
	$password = is_string( $p['password'] ?? null ) ? $p['password'] : '';
	if ( '' === $username || '' === $password || strlen( $username ) > 200 || strlen( $password ) > 4096 ) {
		return dlux_app_err( 'dlux_app_login', 'Enter your username and password.', 400 );
	}

	$ip = dlux_app_ip();
	if ( dlux_app_rl_blocked( 'ip', $ip ) || dlux_app_rl_blocked( 'user', $username ) ) {
		$res = dlux_app_ok( array( 'code' => 'dlux_app_throttled', 'message' => 'Too many sign-in attempts. Please wait 15 minutes and try again.' ), 429 );
		$res->header( 'Retry-After', (string) ( 15 * MINUTE_IN_SECONDS ) );
		return $res;
	}

	// Runs the normal "authenticate" filters, so security / 2FA plugins and the wp_login_failed hook still apply.
	$user = wp_authenticate( $username, $password );
	if ( is_wp_error( $user ) || ! $user instanceof WP_User ) {
		dlux_app_rl_fail( 'ip', $ip );
		dlux_app_rl_fail( 'user', $username );
		return dlux_app_err( 'dlux_app_login', 'Incorrect username or password.', 401 );
	}
	if ( ! user_can( $user, dlux_app_login_cap() ) || ! user_can( $user, 'edit_others_dlux_bookings' ) ) {
		dlux_app_rl_fail( 'ip', $ip );
		return dlux_app_err( 'dlux_app_forbidden', 'This account can\'t use the app. Sign in with an administrator account.', 403 );
	}

	$token = dlux_app_issue_token( $user->ID );
	return dlux_app_ok(
		array(
			'token'       => $token,
			'idleSeconds' => dlux_app_idle_seconds(),
			'maxSeconds'  => dlux_app_max_seconds(),
			'user'        => dlux_app_user_out( $user ),
		)
	);
}

function dlux_app_rest_logout() {
	$cur = dlux_app_current();
	if ( is_array( $cur ) ) {
		$sessions = dlux_app_sessions( $cur[0] );
		unset( $sessions[ $cur[1] ] );
		dlux_app_save_sessions( $cur[0], $sessions );
	}
	return dlux_app_ok( array( 'ok' => true ) );
}

function dlux_app_rest_me() {
	return dlux_app_ok(
		array(
			'user'  => dlux_app_user_out( wp_get_current_user() ),
			'today' => dlux_bk_today(),
		)
	);
}

/* ---------------- Bookings: WordPress <-> app ---------------- */

/** Keys the app may not set through its free-form extra data (they are real booking fields). */
function dlux_app_reserved_keys() {
	return array( 'id', 'wpId', 'day', 'time', 'name', 'phone', 'email', 'rego', 'vehicle', 'cls', 'notes', 'channel', 'total', 'mins', 'items', 'status', 'payment', 'wpStatus', 'option', 'service', 'prepaid', 'appStatus' );
}

/** App-only data of a booking (job steps, condition check, texts sent, staff, ...). */
function dlux_app_extra( $post_id ) {
	$raw = get_post_meta( $post_id, '_dlux_app', true );
	$a   = is_string( $raw ) && '' !== $raw ? json_decode( $raw, true ) : null;
	return is_array( $a ) ? $a : array();
}

/** WordPress status -> app status (app's finer status is kept while it still agrees). */
function dlux_app_status_out( $wp_status, $app_status ) {
	switch ( $wp_status ) {
		case 'cancelled':
		case 'no_show':
			return $wp_status;
		case 'completed':
			return in_array( $app_status, array( 'done', 'paid' ), true ) ? $app_status : 'done';
		default: // pending, confirmed
			return in_array( $app_status, array( 'booked', 'arrived', 'in_progress', 'ready' ), true ) ? $app_status : 'booked';
	}
}

/** App status -> WordPress status. An online booking still in Stripe checkout stays "pending". */
function dlux_app_status_in( $app_status, $current ) {
	switch ( $app_status ) {
		case 'cancelled':
		case 'no_show':
			return $app_status;
		case 'done':
		case 'paid':
			return 'completed';
		default:
			return ( 'pending' === $current && 'booked' === $app_status ) ? 'pending' : 'confirmed';
	}
}

function dlux_app_items_out( $post_id, array $b ) {
	$raw = get_post_meta( $post_id, '_dlux_items', true );
	if ( is_string( $raw ) && '' !== $raw ) {
		$raw = json_decode( $raw, true );
	}
	$out = array();
	if ( is_array( $raw ) && $raw ) {
		foreach ( $raw as $it ) {
			if ( is_array( $it ) ) {
				$out[] = array(
					'sid'   => (string) ( $it['id'] ?? '' ),
					'name'  => (string) ( $it['name'] ?? '' ),
					'price' => (float) ( $it['price'] ?? 0 ),
					'mins'  => (int) ( $it['mins'] ?? 0 ),
				);
			}
		}
	} else {
		foreach ( $b['items'] as $it ) {
			$out[] = array( 'sid' => '', 'name' => $it['name'], 'price' => (float) $it['price'], 'mins' => 0 );
		}
	}
	return $out;
}

/** One booking in the shape the app uses. */
function dlux_app_out( $post_id ) {
	$b = dlux_bk_booking( $post_id );
	if ( ! $b ) {
		return null;
	}
	$extra = dlux_app_extra( $post_id );
	$cid   = (string) get_post_meta( $post_id, '_dlux_app_id', true );
	$post  = get_post( $post_id );
	$paid  = 'paid' === $b['payment'];

	$out = array_diff_key( $extra, array_flip( dlux_app_reserved_keys() ) );
	return array_merge(
		$out,
		array(
			'id'       => '' !== $cid ? $cid : 'wp-' . (int) $post_id,
			'wpId'     => (int) $post_id,
			'day'      => $b['day'],
			'time'     => $b['time'],
			'status'   => dlux_app_status_out( $b['status'], (string) ( $extra['appStatus'] ?? '' ) ),
			'wpStatus' => $b['status'],
			'payment'  => $b['payment'],
			'source'   => isset( $extra['source'] ) && is_string( $extra['source'] ) ? $extra['source'] : 'online',
			'name'     => $b['name'],
			'phone'    => $b['phone'],
			'email'    => $b['email'],
			'rego'     => $b['rego'],
			'vehicle'  => $b['vehicle'],
			'cls'      => $b['cls'],
			'notes'    => $b['notes'],
			'channel'  => $b['channel'],
			'service'  => $b['service'],
			'option'   => $b['option'],
			'items'    => dlux_app_items_out( $post_id, $b ),
			'total'    => round( (float) $b['total'], 2 ),
			'mins'     => $b['mins'] ? (int) $b['mins'] : (int) dlux_bk_config()['slot'],
			'prepaid'  => $paid ? round( (float) ( dlux_bk_get( $post_id, 'amount_paid' ) ?: $b['total'] ), 2 ) : null,
			'created'  => isset( $extra['created'] ) && is_numeric( $extra['created'] ) ? (int) $extra['created'] : (int) get_post_time( 'U', true, $post ) * 1000,
			'updated'  => isset( $extra['updated'] ) && is_numeric( $extra['updated'] ) ? (int) $extra['updated'] : (int) get_post_modified_time( 'U', true, $post ) * 1000,
		)
	);
}

/**
 * Booking post id for an app id ("wp-123", or the id the app gave a booking it created).
 * Returns 0 when there is none. Trashed bookings are returned too (the caller decides).
 */
function dlux_app_find( $app_id ) {
	$app_id = (string) $app_id;
	if ( preg_match( '/^wp-(\d{1,20})$/', $app_id, $m ) ) {
		$pid = (int) $m[1];
		return 'dlux_booking' === get_post_type( $pid ) ? $pid : 0;
	}
	if ( ! preg_match( '/^[a-z0-9]{6,40}$/', $app_id ) ) {
		return 0;
	}
	$ids = get_posts(
		array(
			'post_type'        => 'dlux_booking',
			'post_status'      => array( 'publish', 'private', 'draft', 'pending', 'future', 'trash' ),
			'posts_per_page'   => 1,
			'fields'           => 'ids',
			'no_found_rows'    => true,
			'suppress_filters' => true,
			'meta_key'         => '_dlux_app_id', // phpcs:ignore
			'meta_value'       => $app_id, // phpcs:ignore
		)
	);
	return $ids ? (int) $ids[0] : 0;
}

/**
 * Free-form app data: plain JSON values only, limited in depth, length and size.
 *
 * @return array|WP_Error
 */
function dlux_app_clean_value( $v, $depth ) {
	if ( $depth > 6 ) {
		return null;
	}
	if ( is_bool( $v ) || null === $v ) {
		return $v;
	}
	if ( is_int( $v ) || is_float( $v ) ) {
		return is_finite( (float) $v ) ? $v : null;
	}
	if ( is_string( $v ) ) {
		return mb_substr( wp_check_invalid_utf8( $v, true ), 0, 4000 );
	}
	if ( is_array( $v ) ) {
		$out = array();
		$n   = 0;
		foreach ( $v as $k => $x ) {
			if ( ++$n > 200 ) {
				break;
			}
			$k = is_int( $k ) ? $k : mb_substr( preg_replace( '/[^A-Za-z0-9_|:.\- ]/', '', (string) $k ), 0, 80 );
			if ( '' === $k ) {
				continue;
			}
			$out[ $k ] = dlux_app_clean_value( $x, $depth + 1 );
		}
		return $out;
	}
	return null;
}

function dlux_app_extra_in( array $p ) {
	$reserved = array_flip( dlux_app_reserved_keys() );
	$out      = array();
	foreach ( $p as $k => $v ) {
		if ( ! is_string( $k ) || isset( $reserved[ $k ] ) || ! preg_match( '/^[A-Za-z][A-Za-z0-9_]{0,39}$/', $k ) ) {
			continue;
		}
		if ( count( $out ) >= 60 ) {
			break;
		}
		$out[ $k ] = dlux_app_clean_value( $v, 1 );
	}
	$json = wp_json_encode( $out );
	if ( false === $json || strlen( $json ) > 200 * 1024 ) {
		return dlux_app_err( 'dlux_app_too_big', 'This booking has too much extra data to save.', 413 );
	}
	return $out;
}

/**
 * Validate a booking sent by the app.
 *
 * @return array|WP_Error
 */
function dlux_app_booking_in( $p ) {
	if ( ! is_array( $p ) ) {
		return dlux_app_err( 'dlux_app_invalid', 'Booking data is missing.', 400 );
	}
	$day  = $p['day'] ?? '';
	$time = $p['time'] ?? '';
	if ( ! dlux_app_valid_day( $day ) ) {
		return dlux_app_err( 'dlux_app_invalid', 'Choose a valid date.', 400 );
	}
	if ( ! dlux_app_valid_time( $time ) ) {
		return dlux_app_err( 'dlux_app_invalid', 'Choose a valid time.', 400 );
	}
	$status = is_string( $p['status'] ?? null ) && in_array( $p['status'], dlux_app_statuses(), true ) ? $p['status'] : 'booked';

	$email = dlux_app_text( $p['email'] ?? '', 120 );
	if ( '' !== $email && ! is_email( $email ) ) {
		return dlux_app_err( 'dlux_app_invalid', 'That email address doesn\'t look right.', 400 );
	}
	$email = sanitize_email( $email );

	$items = array();
	foreach ( is_array( $p['items'] ?? null ) ? array_slice( $p['items'], 0, 40 ) : array() as $it ) {
		if ( ! is_array( $it ) ) {
			continue;
		}
		$name = dlux_app_text( $it['name'] ?? '', 120 );
		if ( '' === $name ) {
			continue;
		}
		$sid     = is_scalar( $it['sid'] ?? null ) ? preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $it['sid'] ) : '';
		$items[] = array(
			'id'    => substr( $sid, 0, 40 ),
			'name'  => $name,
			'price' => round( min( 100000, max( 0, (float) ( is_numeric( $it['price'] ?? null ) ? $it['price'] : 0 ) ) ), 2 ),
			'mins'  => (int) min( 1440, max( 0, (int) ( is_numeric( $it['mins'] ?? null ) ? $it['mins'] : 0 ) ) ),
		);
	}

	return array(
		'day'     => $day,
		'time'    => $time,
		'status'  => $status,
		'name'    => dlux_app_text( $p['name'] ?? '', 120 ),
		'phone'   => dlux_app_text( $p['phone'] ?? '', 40 ),
		'email'   => $email,
		'rego'    => substr( strtoupper( preg_replace( '/[^a-z0-9]/i', '', is_scalar( $p['rego'] ?? null ) ? (string) $p['rego'] : '' ) ), 0, 12 ),
		'vehicle' => dlux_app_text( $p['vehicle'] ?? '', 120 ),
		'cls'     => dlux_app_text( $p['cls'] ?? '', 40 ),
		'notes'   => mb_substr( sanitize_textarea_field( is_scalar( $p['notes'] ?? null ) ? (string) $p['notes'] : '' ), 0, 1000 ),
		'channel' => dlux_app_text( $p['channel'] ?? '', 60 ),
		'total'   => round( min( 100000, max( 0, is_numeric( $p['total'] ?? null ) ? (float) $p['total'] : 0 ) ), 2 ),
		'mins'    => (int) min( 1440, max( 0, is_numeric( $p['mins'] ?? null ) ? (int) $p['mins'] : 0 ) ),
		'items'   => $items,
	);
}

function dlux_app_rest_list( WP_REST_Request $r ) {
	$today = dlux_bk_today();
	$from  = dlux_bk_str( $r->get_param( 'from' ) );
	$to    = dlux_bk_str( $r->get_param( 'to' ) );
	if ( '' === $to ) {
		$to = '' !== $from && dlux_app_valid_day( $from ) ? dlux_app_shift_day( $from, 366 ) : dlux_app_shift_day( $today, 180 );
	}
	if ( '' === $from ) {
		$from = dlux_app_valid_day( $to ) ? dlux_app_shift_day( $to, -366 ) : dlux_app_shift_day( $today, -62 );
	}
	if ( ! dlux_app_valid_day( $from ) || ! dlux_app_valid_day( $to ) || $to < $from ) {
		return dlux_app_err( 'dlux_app_invalid', 'Invalid date range.', 400 );
	}
	if ( $to > dlux_app_shift_day( $from, 400 ) ) {
		return dlux_app_err( 'dlux_app_invalid', 'Ask for at most 400 days at a time.', 400 );
	}
	$per  = (int) $r->get_param( 'per_page' );
	$per  = $per > 0 ? min( 500, $per ) : 200;
	$page = max( 1, min( 1000, (int) $r->get_param( 'page' ) ) );

	$q = new WP_Query(
		array(
			'post_type'              => 'dlux_booking',
			'post_status'            => array( 'publish', 'private', 'draft', 'pending', 'future' ),
			'posts_per_page'         => $per,
			'paged'                  => $page,
			'orderby'                => 'ID',
			'order'                  => 'ASC',
			'fields'                 => 'ids',
			'ignore_sticky_posts'    => true,
			'update_post_term_cache' => false,
			'meta_query'             => array( // phpcs:ignore
				array(
					'key'     => 'bk_date',
					'value'   => array( str_replace( '-', '', $from ), str_replace( '-', '', $to ) ),
					'compare' => 'BETWEEN',
				),
			),
		)
	);
	_prime_post_caches( $q->posts, false, true );

	$out = array();
	foreach ( $q->posts as $pid ) {
		$b = dlux_app_out( $pid );
		if ( $b ) {
			$out[] = $b;
		}
	}
	return dlux_app_ok(
		array(
			'from'     => $from,
			'to'       => $to,
			'page'     => $page,
			'pages'    => (int) $q->max_num_pages,
			'total'    => (int) $q->found_posts,
			'bookings' => $out,
		)
	);
}

function dlux_app_rest_get( WP_REST_Request $r ) {
	$pid = dlux_app_find( $r['id'] );
	if ( ! $pid || 'trash' === get_post_status( $pid ) ) {
		return dlux_app_err( 'dlux_app_not_found', 'Booking not found.', 404 );
	}
	return dlux_app_ok( array( 'booking' => dlux_app_out( $pid ) ) );
}

function dlux_app_rest_save( WP_REST_Request $r ) {
	global $wpdb;
	$app_id = (string) $r['id'];
	$p      = $r->get_json_params();
	$in     = dlux_app_booking_in( $p );
	if ( is_wp_error( $in ) ) {
		return $in;
	}
	$extra = dlux_app_extra_in( $p );
	if ( is_wp_error( $extra ) ) {
		return $extra;
	}
	$extra['appStatus'] = $in['status'];

	// One writer per booking id at a time, so a retried create can't make a duplicate.
	$lock = 'dlux_app_' . md5( $app_id );
	$wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 10)', $lock ) );
	$release = function () use ( $wpdb, $lock ) {
		$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock ) );
	};

	$pid     = dlux_app_find( $app_id );
	$created = false;
	if ( $pid && 'trash' === get_post_status( $pid ) ) {
		$release();
		return dlux_app_err( 'dlux_app_gone', 'This booking was deleted.', 410 );
	}
	if ( ! $pid ) {
		if ( 0 === strpos( $app_id, 'wp-' ) ) {
			$release();
			return dlux_app_err( 'dlux_app_not_found', 'Booking not found.', 404 );
		}
		$pid = wp_insert_post(
			array(
				'post_type'   => 'dlux_booking',
				'post_status' => 'publish',
				'post_title'  => trim( $in['rego'] . ' · ' . $in['name'], ' ·' ),
				'post_author' => get_current_user_id(),
			),
			true
		);
		if ( is_wp_error( $pid ) ) {
			$release();
			return dlux_app_err( 'dlux_app_save', 'The booking could not be saved. Please try again.', 500 );
		}
		$created = true;
		update_post_meta( $pid, '_dlux_app_id', $app_id );
		update_post_meta( $pid, '_dlux_token', wp_generate_password( 32, false ) );
		dlux_bk_set( $pid, 'payment', 'unpaid' ); // Pay at the shop.
	}

	$current = (string) dlux_bk_get( $pid, 'status' );
	$fields  = array(
		'status'       => dlux_app_status_in( $in['status'], $current ),
		'date'         => str_replace( '-', '', $in['day'] ),
		'time'         => $in['time'] . ':00',
		'vehicle_size' => $in['cls'],
		'total'        => $in['total'],
		'name'         => $in['name'],
		'phone'        => $in['phone'],
		'email'        => $in['email'],
		'rego'         => $in['rego'],
		'vehicle'      => $in['vehicle'],
		'notes'        => $in['notes'],
		'channel'      => $in['channel'],
	);
	if ( $in['items'] ) {
		$fields['package'] = $in['items'][0]['name'];
		$fields['addons']  = implode( "\n", wp_list_pluck( array_slice( $in['items'], 1 ), 'name' ) );
		update_post_meta( $pid, '_dlux_items', wp_slash( $in['items'] ) );
	}
	foreach ( $fields as $k => $v ) {
		dlux_bk_set( $pid, $k, $v );
	}
	if ( $in['mins'] ) {
		update_post_meta( $pid, '_dlux_mins', $in['mins'] );
	}
	update_post_meta( $pid, '_dlux_app', wp_slash( wp_json_encode( $extra ) ) );
	dlux_bk_refresh_title( $pid );
	$release();

	return dlux_app_ok( array( 'booking' => dlux_app_out( $pid ) ), $created ? 201 : 200 );
}

function dlux_app_rest_delete( WP_REST_Request $r ) {
	$pid = dlux_app_find( $r['id'] );
	if ( ! $pid || 'trash' === get_post_status( $pid ) ) {
		return dlux_app_err( 'dlux_app_not_found', 'Booking not found.', 404 );
	}
	if ( ! wp_trash_post( $pid ) ) {
		return dlux_app_err( 'dlux_app_save', 'The booking could not be deleted.', 500 );
	}
	return dlux_app_ok( array( 'deleted' => true, 'id' => (string) $r['id'] ) );
}
