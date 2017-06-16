<?php
$onepress_projects_id       = get_theme_mod( 'onepress_projects_id', 'projects' );
$onepress_projects_title    = get_theme_mod( 'onepress_projects_title', __('Highlight Projects', 'onepress-plus' ));
$onepress_projects_subtitle = get_theme_mod( 'onepress_projects_subtitle', __('Some of our works', 'onepress-plus' ));
$is_ajax = get_theme_mod( 'onepress_project_ajax', 1 );
$desc = get_theme_mod( 'onepress_projects_desc' );

?>
<?php if ( ! onepress_is_selective_refresh() ){ ?>
<section <?php if ( $onepress_projects_id ) { ?>id="<?php echo esc_attr( $onepress_projects_id ); ?>" <?php } ?> class="<?php echo esc_attr( apply_filters( 'onepress_section_class', 'section-padding section-projects onepage-section', 'projects' ) ); ?>">
<?php } ?>
    <div class="container">
        <?php if ( $onepress_projects_title || $onepress_projects_subtitle || $desc ) { ?>
        <div class="section-title-area">
            <?php if ( $onepress_projects_subtitle != '' ) echo '<h5 class="section-subtitle">' . esc_html( $onepress_projects_subtitle ) . '</h5>'; ?>
            <?php if ( $onepress_projects_title != '' ) echo '<h2 class="section-title">' . esc_html( $onepress_projects_title ) . '</h2>'; ?>
            <?php if ( $desc ) {
                echo '<div class="section-desc">' . apply_filters( 'onepress_the_content', wp_kses_post( $desc ) )  . '</div>';
            } ?>
        </div>
        <?php } ?>
        <div class="project-wrapper project-3-column wow slideInUp">
            <?php
            $args = array(
                'post_type' => 'portfolio',
                'post_status' => 'publish',
                'posts_per_page' => get_theme_mod( 'onepress_projects_number', 6 ),
                'order' =>  get_theme_mod( 'onepress_projects_order', 'DESC' ),
                'orderby' => get_theme_mod( 'onepress_projects_orderby', 'ID' ),
                'suppress_filters' => 0,
            );

            $the_query = new WP_Query( $args );

            $portfolios =  $the_query->get_posts();
            global $post;

            if ( ! empty( $portfolios ) ) {
                foreach ($portfolios as $k => $post ) {
                    setup_postdata( $post );
                    ?>
                    <div class="project-item <?php  echo ( $is_ajax ) ? 'is-ajax': 'no-ajax'; ?>" data-id="<?php echo get_the_ID(); ?>" >
                        <div class="project-content project-contents " data-id="<?php echo get_the_ID(); ?>">
                            <div class="project-thumb project-trigger">
                                <?php
                                if ( ! $is_ajax ) {
                                    echo '<a href="'.get_permalink( $post->ID ).'">';
                                }
                                if ( has_post_thumbnail() ) {
                                    the_post_thumbnail( 'onepress-medium' );
                                }
                                if ( ! $is_ajax ) {
                                    echo '</a>';
                                }
                                ?>
                            </div>
                            <div class="project-header project-trigger">
                                <h5 class="project-small-title"><?php
                                    if ( ! $is_ajax ) {
                                        echo '<a href="'.get_permalink( $post->ID ).'">';
                                    }

                                    the_title();

                                    if ( ! $is_ajax ) {
                                        echo '</a>';
                                    }
                                    ?></h5>
                                <div class="project-meta"><?php
                                    $terms = get_the_terms( $post->ID, 'portfolio_cat' );
                                    if ( $terms ) {
                                        $names = wp_list_pluck( $terms, 'name' );
                                        echo esc_html( join( ' / ', $names ) );
                                    }
                                    ?></div>
                            </div>
                        </div>
                    </div>
                <?php
                }
            }

            wp_reset_postdata();
            ?>
            <div class="clear"></div>
        </div>
    </div>
<?php if ( ! onepress_is_selective_refresh() ){ ?>
</section>
<?php } ?>
