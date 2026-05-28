<?php
defined('ABSPATH') || exit;

get_header();
?>
<main class="pa-main">
    <div class="pa-container">
        <?php while (have_posts()): the_post(); ?>
            <h1 class="entry-title"><?php the_title(); ?></h1>
            <?php the_content(); ?>
        <?php endwhile; ?>
    </div>
</main>
<?php
get_footer();
