<?php
$id       = get_theme_mod( 'sovenco_videolightbox_id', 'videolightbox' );
$disable  = get_theme_mod( 'sovenco_videolightbox_disable' ) == 1 ? true : false;
$heading  = get_theme_mod( 'sovenco_videolightbox_title' );
$video    = get_theme_mod( 'sovenco_videolightbox_url' );

if ( ! $disable && ( $video || $heading ) ) {
    $image    = get_theme_mod( 'sovenco_videolightbox_image' );
    ?>
    <?php if ( ! sovenco_is_selective_refresh() ){ ?>
    <section id="<?php if ($id != '') echo esc_attr( $id ); ?>" <?php do_action('sovenco_section_atts', 'videolightbox'); ?> class="<?php echo esc_attr(apply_filters('sovenco_section_class', 'section-videolightbox section-padding section-padding-larger section-inverse onepage-section', 'videolightbox')); ?>">
    <?php } ?>
        <?php do_action('sovenco_section_before_inner', 'videolightbox'); ?>
        <div class="container">
            <?php if ( $video ) { ?>
                <div class="videolightbox__icon videolightbox-popup">
                    <a href="<?php echo esc_attr( $video ); ?>" data-scr="<?php echo esc_attr( $video ); ?>" class="popup-video">
                        <span class="video_icon"><i class="fa fa-play"></i></span>
                    </a>
                </div>
            <?php } ?>
            <?php if ( $heading ) { ?>
                <h2 class="videolightbox__heading"><?php echo do_shortcode( wp_kses_post( $heading ) ); ?></h2>
            <?php } ?>
        </div>
        <?php do_action('sovenco_section_after_inner', 'videolightbox'); ?>
    <?php if ( ! sovenco_is_selective_refresh() ){ ?>
    </section>
    <?php } ?>
<?php  }

