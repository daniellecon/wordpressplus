<?php
$sovenco_cta_id       = get_theme_mod( 'sovenco_cta_id', 'section-cta' );
$sovenco_cta_title    = get_theme_mod( 'sovenco_cta_title', __('Use these ribbons to display calls to action mid-page.', 'sovenco-plus' ));
$button_label = get_theme_mod( 'sovenco_cta_btn_label', __('Button text', 'sovenco-plus' ));
$button_link = get_theme_mod( 'sovenco_cta_btn_link', '#' );

?>
<?php if ( ! sovenco_is_selective_refresh() ){ ?>
<section <?php if ( $sovenco_cta_id ) { ?>id="<?php echo esc_attr( $sovenco_cta_id ); ?>" <?php } ?> class="<?php echo esc_attr( apply_filters( 'sovenco_section_class', 'section-cta section-padding section-inverse onepage-section', 'cta' ) ); ?>">
<?php } ?>
    <?php do_action( 'sovenco_section_before_inner', 'cta' ); ?>

    <div class="container">
        <div class="row">
            <div class="col-md-12 col-lg-9 cta-heading">
                <h2><?php echo wp_kses_post( $sovenco_cta_title ); ?></h2>
            </div>
            <div class="col-md-12 col-lg-3 cta-button-area">
                <?php if ( $button_label ) {
                    $style = sanitize_text_field( get_theme_mod( 'sovenco_cta_btn_link_style', 'theme-primary' ) );
                    $btn_class = '';
                    if ( !$style || $style =='theme-primary' || strpos( $style, 'btn-' ) !== 0 ) {
                        $btn_class = 'btn-theme-primary-outline';
                    } else {
                        $btn_class = $style;
                    }
                    ?>
                    <a href="<?php echo esc_url( $button_link ); ?>" class="btn <?php echo esc_attr( $btn_class ); ?>"><?php echo esc_html( $button_label ); ?></a>
                <?php } ?>

            </div>
        </div>
    </div>

    <?php do_action( 'sovenco_section_after_inner', 'cta' ); ?>
<?php if ( ! sovenco_is_selective_refresh() ){ ?>
</section>
<?php } ?>
