<?php
$sovenco_testimonial_id       = get_theme_mod( 'sovenco_testimonial_id', esc_html__('testimonials', 'sovenco-plus') );
$sovenco_testimonials_disable = get_theme_mod( 'sovenco_testimonials_disable' ) ==  1 ? true : false;
$sovenco_testimonial_title    = get_theme_mod( 'sovenco_testimonial_title', esc_html__('Testimonials', 'sovenco-plus' ));
$sovenco_testimonial_subtitle = get_theme_mod( 'sovenco_testimonial_subtitle', esc_html__('Section subtitle', 'sovenco-plus' ));
$desc = get_theme_mod( 'sovenco_testimonial_desc' );
?>
<?php if ( ! $sovenco_testimonials_disable  ) : ?>
<?php if ( ! sovenco_is_selective_refresh() ){ ?>
    <section id="<?php if ( $sovenco_testimonial_id != '' ) echo $sovenco_testimonial_id; ?>" <?php do_action( 'sovenco_section_atts', 'testimonials' ); ?> class="<?php echo esc_attr( apply_filters( 'sovenco_section_class', 'section-testimonials onepage-section section-meta section-padding section-padding-lg', 'testimonials' ) ); ?>">
<?php } ?>
        <?php do_action( 'sovenco_section_before_inner', 'testimonials' ); ?>
        <div class="container">
            <?php if ( $sovenco_testimonial_title || $sovenco_testimonial_subtitle || $desc ) { ?>
                <div class="section-title-area">
                    <?php if ($sovenco_testimonial_subtitle != '') echo '<h5 class="section-subtitle">' . esc_html($sovenco_testimonial_subtitle) . '</h5>'; ?>
                    <?php if ($sovenco_testimonial_title != '') echo '<h2 class="section-title">' . esc_html($sovenco_testimonial_title) . '</h2>'; ?>
                    <?php if ($desc) {
                        echo '<div class="section-desc">' . apply_filters( 'sovenco_the_content', wp_kses_post( $desc ) ) . '</div>';
                    } ?>
                </div>
                <?php
            }
            $testimonials = get_theme_mod( 'sovenco_testimonial_boxes', '' );

            if ( is_string( $testimonials ) ) {
                $testimonials = json_decode( $testimonials, true );
            }

            if ( ! is_array( $testimonials ) || empty( $testimonials ) ) {
                $testimonials = array(
                    array(
                        'title' 		=> esc_html__( 'Praesent placerat', 'sovenco-plus' ),
                        'name' 			=> esc_html__( 'Alexander Rios', 'sovenco-plus' ),
                        'subtitle' 		=> esc_html__( 'Founder & CEO', 'sovenco-plus' ),
                        'style'         => 'warning',
                        'image' 		=> array(
                            'url' => get_template_directory_uri() . '/assets/images/testimonial_1.jpg',
                            'id'  => ''
                        ),
                        'content' 		=> esc_html__( 'Praesent dapibus, neque id cursus faucibus, tortor neque egestas augue, eu vulputate magna eros eu erat. Aliquam erat volutpat.', 'sovenco-plus' ),

                    ),
                    array(
                        'title' 		=> esc_html__( 'Cras iaculis', 'sovenco-plus' ),
                        'name' 			=> esc_html__( 'Alexander Max', 'sovenco-plus' ),
                        'subtitle' 		=> esc_html__( 'Founder & CEO', 'sovenco-plus' ),
                        'style'         => 'success',
                        'image' 		=> array(
                            'url' => get_template_directory_uri() . '/assets/images/testimonial_2.jpg',
                            'id'  => ''
                        ),
                        'content' 		=> esc_html__( 'Praesent dapibus, neque id cursus faucibus, tortor neque egestas augue eu vulputate.', 'sovenco-plus' ),

                    ),
                    array(
                        'title' 		=> esc_html__( 'Fusce lobortis', 'sovenco-plus' ),
                        'name' 			=> esc_html__( 'Peter Mendez', 'sovenco-plus' ),
                        'subtitle' 		=> esc_html__( 'Example Company', 'sovenco-plus' ),
                        'style'         => 'theme-primary',
                        'image' 		=> array(
                            'url' => get_template_directory_uri() . '/assets/images/testimonial_3.jpg',
                            'id'  => ''
                        ),
                        'content' 		=> esc_html__( 'Sed adipiscing ornare risus. Morbi est est, blandit sit amet, sagittis vel, euismod vel, velit. Pellentesque egestas sem. Suspendisse commodo ullamcorper magna egestas sem.', 'sovenco-plus' ),

                    ),
                );
            }

            $rows  = array();
            $col = 3;

            ?>
            <div class="card-deck-wrapper">
                <?php
                $row_index = 0 ;
                foreach ( $testimonials as $testimonial ) {
                    if ( ! isset( $rows[ $row_index ] ) ) {
                        $rows[ $row_index ] = array();
                    }

                    if ( count( $rows[ $row_index ] ) >=  $col ) {
                        $row_index ++ ;
                        $rows[ $row_index ] = array();
                    }

                    /// echo '<div class="card-deck">';

                    $testimonial = wp_parse_args( $testimonial, array(
                        'title' 		=> '',
                        'name' 			=> '',
                        'subtitle' 		=> '',
                        'style'         => 'theme-primary',
                        'image' 		=> array(
                            'url' => '',
                            'id'  => ''
                        ),
                        'content' 		=> '',
                    ) );

                    $testimonial['image'] = wp_parse_args( $testimonial['image'], array( 'url' => '', 'id' => '' ) );
                    $image = '';
                    if ( $testimonial['image']['id'] != '' ){
                        $image =  wp_get_attachment_url( $testimonial['image']['id'] );
                    }
                    if ( $image == '' && $testimonial['image']['url'] != '' ) {
                        $image = $testimonial['image']['url'];
                    }
                    
                    $classes = array('card');
                    if ( 'light' != $testimonial['style'] ){
                        $classes[] = 'card-inverse';
                    }
                    $classes[] =  'card-'.$testimonial['style'];

                    $t = '';
                    $t .= '<div class="'.esc_attr( join( ' ', $classes ) ).'">';
                    $t .= '<div class="card-block">';
                    $t .= '<div class="tes_author">';

                    if ( $image != '' ) {
                        $t .= '<img src="'.esc_url( $image ).'" alt="" />';
                    }
                    if ( $image != '' ) {
                        $t .= '<cite class="tes__name">'.esc_html( $testimonial['name'] ).'<div>'.wp_kses_post( $testimonial['subtitle'] ) .'</div></cite>';
                    }

                    $t .= ' </div>';

                    $t .='<h4 class="card-title">'.esc_html( $testimonial['title'] ).'</h4>';
                    $t .='<p class="card-text">'.wp_kses_post( $testimonial['content'] ) .'</p>';

                    $t .= ' </div>';
                    $t .= ' </div>';

                    $rows[ $row_index ][ ] =  $t;

                }

                foreach ( $rows as $r ) {
                    echo '<div class="card-deck wow slideInUp">';
                    echo join( "\n\t", $r );
                    echo '</div>';
                }

                ?>
            </div>
        </div>
        <?php do_action( 'sovenco_section_after_inner', 'testimonials' ); ?>
<?php if ( ! sovenco_is_selective_refresh() ){ ?>
    </section>
<?php } ?>
<?php endif; ?>
