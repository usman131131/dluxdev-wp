<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$editor      = get_sub_field( 'editor' );
?>

<section>
    <div class="container">
        <?php if($editor): ?>
            <?php echo $editor; ?>
        <?php endif; ?>
    <div>
</section>