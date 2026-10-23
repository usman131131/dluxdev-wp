=== DLUX Car Care ===

Requires: Secure Custom Fields (SCF) or ACF Pro, with the three DLUX JSON files imported.

SETUP
1. Appearance > Themes > Add New > Upload Theme > dlux-theme.zip > Activate.
2. Theme Settings (admin menu): add logo, colours, booking URL, Google rating, locations, header buttons, socials, copyright.
3. Appearance > Menus: assign menus to "Primary Menu" and "Footer Menu".
   Use custom links like /#prices, /#detailing, /#memberships, /#gift-vouchers, /#visit.
   (Without menus, these anchor links are shown automatically.)
4. Create a page, add sections in "Page Sections", then Settings > Reading > static homepage.

SECTION IDS (for menu anchors)
- Pricing: prices    - Services: detailing    - Locations: visit
- Promo cards get ids from their Label (e.g. "Memberships" -> #memberships, "Gift vouchers" -> #gift-vouchers),
  so leave the Promo Cards section ID empty.

TEXT TOKENS
{year}, {rating} (Google rating) and {since} (established year) work in headings, eyebrows, stats and copyright.
Wrap heading words in <span>…</span> to show them in the accent colour.

The header becomes transparent over the hero when the first section is a Hero.
Map embed accepts a Google Maps <iframe> or an embed URL.
