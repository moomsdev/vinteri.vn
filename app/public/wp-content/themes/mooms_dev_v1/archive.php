<?php get_header() ?>
<?php theBreadcrumb() ?>
<div class="content">
    <div class="container">
        <div class="row">
            <div class="col-xs-12">
                <div class="gm-content-wrapper">
                    <?php if($currentPage->have_posts()) : ?>
                        <?php while($currentPage->have_posts()) : $currentPage->the_post(); ?>
                            <?php the_content(); ?>
                        <?php endwhile; ?>
                    <?php endif; ?>
                    <?php wp_reset_postdata(); ?>
                    <?php wp_reset_query(); ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php get_footer() ?>