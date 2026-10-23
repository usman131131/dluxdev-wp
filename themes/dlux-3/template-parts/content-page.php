<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<section class="section page-content">
	<div class="container container--narrow">
		<article <?php post_class( 'entry' ); ?>>
			<h1 class="section-title"><?php the_title(); ?></h1>
			<div class="entry__content"><?php the_content(); ?></div>
		</article>
	</div>
</section>
