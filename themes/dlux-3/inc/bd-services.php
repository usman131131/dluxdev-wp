<?php
/**
 * Extra content blocks inside a service panel (Our Services, Beyond Detailing).
 * Each block is a row of the service's "blocks" flexible content field.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

function dlux_bds_block( $b, $lines ) {
	if ( ! is_array( $b ) || empty( $b['acf_fc_layout'] ) ) return;
	$kses = [ 'br' => [], 'strong' => [], 'em' => [], 'span' => [ 'class' => [] ] ];
	$head = function ( $h ) { return '' !== trim( (string) $h ) ? '<h4 class="bds-block__title">' . dlux_heading( $h ) . '</h4>' : ''; };

	switch ( $b['acf_fc_layout'] ) {

		case 'highlight_cards': // "Gloss · Satin · Matte", "99%", "Cooler"…
			$cards = array_filter( (array) ( $b['cards'] ?? [] ), function ( $c ) { return '' !== trim( (string) ( $c['title'] ?? '' ) ); } );
			if ( ! $cards ) return;
			echo '<div class="bds-block">' . $head( $b['heading'] ?? '' ) . '<div class="bds-hl" style="--bds-hl:' . esc_attr( min( 4, count( $cards ) ) ) . '">'; // phpcs:ignore
			foreach ( $cards as $c ) {
				$style = in_array( $c['style'] ?? '', [ 'dark', 'light', 'accent' ], true ) ? $c['style'] : 'dark';
				echo '<div class="bds-hl__card bds-hl__card--' . esc_attr( $style ) . '"><p class="bds-hl__title">' . dlux_heading( $c['title'] ) . '</p>'; // phpcs:ignore
				if ( ! empty( $c['text'] ) ) echo '<p class="bds-hl__text">' . wp_kses( $c['text'], $kses ) . '</p>'; // phpcs:ignore
				echo '</div>';
			}
			echo '</div></div>';
			break;

		case 'package_card': // "Most popular · Front-end PPF · Sedan from $2,200"
			if ( empty( $b['title'] ) ) return;
			$price = trim( (string) ( $b['price'] ?? '' ) );
			echo '<div class="bds-block"><div class="bds-pkg">';
			if ( ! empty( $b['badge'] ) ) echo '<span class="bds-pkg__badge">' . esc_html( $b['badge'] ) . '</span>';
			echo '<p class="bds-pkg__title">' . dlux_heading( $b['title'] ) . '</p>'; // phpcs:ignore
			if ( ! empty( $b['text'] ) ) echo '<p class="bds-pkg__text">' . wp_kses( $b['text'], $kses ) . '</p>'; // phpcs:ignore
			if ( '' !== $price ) {
				if ( ! empty( $b['price_label'] ) ) echo '<span class="bds-pkg__label">' . esc_html( $b['price_label'] ) . '</span>';
				echo '<strong class="bds-pkg__price">' . esc_html( is_numeric( $price ) ? dlux_money( $price ) : $price ) . '</strong>';
			}
			if ( $btn = dlux_button( $b['button'] ?? null, 'btn--primary', false ) ) echo '<div class="bds-pkg__btn">' . $btn . '</div>'; // phpcs:ignore
			echo '</div></div>';
			break;

		case 'callout': // "Myth buster"
			$style = 'dark' === ( $b['style'] ?? '' ) ? 'dark' : 'accent';
			echo '<div class="bds-block"><div class="bds-callout bds-callout--' . esc_attr( $style ) . '">';
			if ( ! empty( $b['eyebrow'] ) ) echo '<p class="bds-callout__eyebrow">' . esc_html( $b['eyebrow'] ) . '</p>';
			if ( ! empty( $b['heading'] ) ) echo '<p class="bds-callout__title">' . dlux_heading( $b['heading'] ) . '</p>'; // phpcs:ignore
			if ( ! empty( $b['text'] ) ) echo '<div class="bds-callout__text">' . wp_kses_post( $b['text'] ) . '</div>';
			if ( $btn = dlux_button( $b['button'] ?? null, 'btn--dark btn--sm', false ) ) echo '<div class="bds-callout__btn">' . $btn . '</div>'; // phpcs:ignore
			echo '</div></div>';
			break;

		case 'steps': // "How your wheels are powder coated"
			$steps = array_values( array_filter( (array) ( $b['steps'] ?? [] ), function ( $s ) { return '' !== trim( (string) ( $s['title'] ?? '' ) ); } ) );
			if ( ! $steps ) return;
			echo '<div class="bds-block">' . $head( $b['heading'] ?? '' ) . '<ol class="bds-steps">'; // phpcs:ignore
			foreach ( $steps as $n => $s ) {
				echo '<li><span class="pstep__num" aria-hidden="true">' . esc_html( str_pad( (string) ( $n + 1 ), 2, '0', STR_PAD_LEFT ) ) . '</span><h5>' . esc_html( $s['title'] ) . '</h5>';
				if ( ! empty( $s['text'] ) ) echo '<p>' . wp_kses( $s['text'], $kses ) . '</p>'; // phpcs:ignore
				echo '</li>';
			}
			echo '</ol></div>';
			break;

		case 'comparison': // "Powder coating vs normal paint"
			$rows = array_filter( (array) ( $b['rows'] ?? [] ), function ( $r ) { return '' !== trim( (string) ( $r['label'] ?? '' ) ); } );
			if ( ! $rows ) return;
			echo '<div class="bds-block">' . $head( $b['heading'] ?? '' ) . '<div class="bds-compare"><table><thead><tr><th scope="col"><span class="screen-reader-text">' . esc_html__( 'Feature', 'dlux' ) . '</span></th>'; // phpcs:ignore
			echo '<th scope="col" class="is-ours">' . esc_html( $b['ours_label'] ?? '' ) . '</th><th scope="col">' . esc_html( $b['theirs_label'] ?? '' ) . '</th></tr></thead><tbody>';
			foreach ( $rows as $r ) {
				echo '<tr><th scope="row">' . esc_html( $r['label'] ) . '</th><td class="is-ours">' . esc_html( $r['ours'] ?? '' ) . '</td><td>' . esc_html( $r['theirs'] ?? '' ) . '</td></tr>';
			}
			echo '</tbody></table></div></div>';
			break;

		case 'text_card': // "Edges done properly, not cut short" + optional side card
			$aside = ! empty( $b['card_heading'] ) || ! empty( $b['card_text'] );
			$astyle = 'dark' === ( $b['card_style'] ?? '' ) ? 'dark' : 'pink';
			echo '<div class="bds-block"><div class="bds-textcard' . ( $aside ? ' has-aside' : '' ) . '"><div class="bds-textcard__main">' . $head( $b['heading'] ?? '' ); // phpcs:ignore
			if ( ! empty( $b['text'] ) ) echo '<div class="bds-textcard__text">' . wp_kses_post( $b['text'] ) . '</div>';
			echo '</div>';
			if ( $aside ) {
				echo '<div class="bds-textcard__aside bds-textcard__aside--' . esc_attr( $astyle ) . '">';
				if ( ! empty( $b['card_heading'] ) ) echo '<p class="bds-textcard__aside-title">' . dlux_heading( $b['card_heading'] ) . '</p>'; // phpcs:ignore
				if ( ! empty( $b['card_text'] ) ) echo '<p>' . wp_kses( $b['card_text'], $kses ) . '</p>'; // phpcs:ignore
				if ( $btn = dlux_button( $b['card_button'] ?? null, 'dark' === $astyle ? 'btn--white btn--sm' : 'btn--primary btn--sm', false ) ) echo $btn; // phpcs:ignore
				echo '</div>';
			}
			echo '</div></div>';
			break;

		case 'list_columns': // "How your PPF warranty works"
			$cols = array_filter( (array) ( $b['columns'] ?? [] ), function ( $c ) { return '' !== trim( (string) ( $c['title'] ?? '' ) ) || '' !== trim( (string) ( $c['list'] ?? '' ) ); } );
			echo '<div class="bds-block bds-cols">' . $head( $b['heading'] ?? '' ); // phpcs:ignore
			if ( ! empty( $b['text'] ) ) echo '<p class="bds-cols__intro">' . wp_kses( $b['text'], $kses ) . '</p>'; // phpcs:ignore
			if ( $cols ) {
				echo '<div class="bds-cols__grid">';
				foreach ( $cols as $c ) {
					echo '<div class="bds-cols__col">';
					if ( ! empty( $c['title'] ) ) echo '<h5>' . esc_html( $c['title'] ) . '</h5>';
					$items = $lines( $c['list'] ?? '' );
					if ( $items ) {
						echo '<ul class="tick-list tick-list--sm">';
						foreach ( $items as $it ) echo '<li>' . esc_html( $it ) . '</li>';
						echo '</ul>';
					}
					echo '</div>';
				}
				echo '</div>';
			}
			if ( ! empty( $b['footnote'] ) ) echo '<p class="bds-cols__foot">' . esc_html( $b['footnote'] ) . '</p>';
			echo '</div>';
			break;
	}
}
