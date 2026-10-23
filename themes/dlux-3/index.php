<?php
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();
?>
<section class="section page-content">
	<div class="container container--narrow">
		<?php if ( is_home() || is_archive() || is_search() ) : ?>
			<h1 class="section-title"><?php echo is_search() ? esc_html( sprintf( __( 'Results for “%s”', 'dlux' ), get_search_query() ) ) : wp_kses_post( is_home() ? ( single_post_title( '', false ) ?: __( 'Latest news', 'dlux' ) ) : get_the_archive_title() ); ?></h1>
		<?php endif; ?>

		<?php if ( have_posts() ) : ?>
			<?php while ( have_posts() ) : the_post(); ?>
				<?php if ( is_singular() ) : ?>
					<article <?php post_class( 'entry' ); ?>>
						<h1 class="section-title"><?php the_title(); ?></h1>
						<?php if ( has_post_thumbnail() ) the_post_thumbnail( 'large', [ 'class' => 'entry__image' ] ); ?>
						<div class="entry__content"><?php the_content(); ?></div>
					</article>
				<?php else : ?>
					<article <?php post_class( 'entry entry--teaser' ); ?>>
						<h2 class="entry__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
						<p class="entry__meta"><?php echo esc_html( get_the_date() ); ?></p>
						<div class="entry__excerpt"><?php the_excerpt(); ?></div>
					</article>
				<?php endif; ?>
			<?php endwhile; ?>
			<?php the_posts_pagination(); ?>
		<?php else : ?>
			<p><?php esc_html_e( 'Nothing found. Try a different search or head back to the homepage.', 'dlux' ); ?></p>
		<?php endif; ?>
	</div>
</section>
<?php
get_footer();
