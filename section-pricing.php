<?php
$sovenco_pricing_id       = get_theme_mod( 'sovenco_pricing_id', esc_html__('pricing', 'sovenco-plus') );
$sovenco_pricing_title    = get_theme_mod( 'sovenco_pricing_title', esc_html__('Pricing Table', 'sovenco-plus' ));
$sovenco_pricing_subtitle = get_theme_mod( 'sovenco_pricing_subtitle', esc_html__('Responsive pricing section', 'sovenco-plus' ));
$desc                      = get_theme_mod( 'sovenco_pricing_desc' )
?>
<?php if ( ! sovenco_is_selective_refresh() ){ ?>
<section <?php if ( $sovenco_pricing_id ) { ?>id="<?php echo esc_attr( $sovenco_pricing_id ); ?>" <?php } ?> class="<?php echo esc_attr( apply_filters( 'sovenco_section_class', 'section-pricing section-padding onepage-section', 'pricing' ) ); ?>">
<?php } ?>
    <?php do_action( 'sovenco_section_before_inner', 'pricing' ); ?>
    <div class="container">
        <?php if ( $sovenco_pricing_title || $sovenco_pricing_subtitle || $desc ){ ?>
        <div class="section-title-area">
            <?php if ( $sovenco_pricing_subtitle != '' ) {  echo '<h5 class="section-subtitle">' . esc_html( $sovenco_pricing_subtitle ) . '</h5>'; } ?>
            <?php if ( $sovenco_pricing_title != '' ) { echo '<h2 class="section-title">' . esc_html( $sovenco_pricing_title ) . '</h2>';  } ?>
            <?php if ( $desc ) {
                echo '<div class="section-desc">' . apply_filters( 'sovenco_the_content', wp_kses_post( $desc ) ) . '</div>';
            } ?>
        </div>
        <?php } ?>
        <div class="pricing-table ">
            <?php

            $plans = get_theme_mod( 'sovenco_pricing_plans' );

            if ( is_string( $plans ) ) {
                $plans = json_decode( $plans , true );
            }

            if ( empty( $plans ) || ! is_array( $plans ) ) {
                $plans = array(
                    array(
                        'title' => esc_html__( 'Freelancer', 'sovenco-plus' ),
                        'code'  => esc_html__( '$', 'sovenco-plus' ),
                        'price'  => '9.90',
                        'subtitle' => esc_html__( 'Perfect for single freelancers who work by themselves', 'sovenco-plus' ),
                        'content' => esc_html__( "Support Forum \nFree hosting\n 1 hour of support\n 40MB of storage space", 'sovenco-plus' ),
                        'label' => esc_attr__( 'Choose Plan', 'sovenco-plus' ),
                        'link' => '#',
                        'button' => 'btn-theme-primary',
                    ),
                    array(
                        'title' => esc_html__( 'Small Business', 'sovenco-plus' ),
                        'code'  => esc_html__( '$', 'sovenco-plus' ),
                        'price'  => '29.9',
                        'subtitle' => esc_html__( 'Suitable for small businesses with up to 5 employees', 'sovenco-plus' ),
                        'content' => esc_html__( "Support Forum \nFree hosting\n 10 hour of support\n 1GB of storage space", 'sovenco-plus' ),
                        'label' => esc_attr__( 'Choose Plan', 'sovenco-plus' ),
                        'link' => '#',
                        'button' => 'btn-success',
                    ),
                    array(
                        'title' => esc_html__( 'Larger Business', 'sovenco-plus' ),
                        'code'  => esc_html__( '$', 'sovenco-plus' ),
                        'price'  => '59.90',
                        'subtitle' => esc_html__( 'Great for large businesses with more than 5 employees', 'sovenco-plus' ),
                        'content' => esc_html__( "Support Forum \nFree hosting\n Unlimited hours of support\n Unlimited storage space", 'sovenco-plus' ),
                        'label' => esc_attr__( 'Choose Plan', 'sovenco-plus' ),
                        'link' => '#',
                        'button' => 'btn-theme-primary',
                    ),

                );
            }

            $class = 'col-md-6 col-lg-4';
            $n = count( $plans );
            if ( $n == 4  ){
                $class = 'col-md-6 col-lg-3';
            } else if ( $n == 3  ){
                $class = 'col-md-6 col-lg-4';
            } else if ( $n == 2  ){
                $class = 'col-md-6 col-lg-6';
            } else if ( $n == 1 ){
                $class = 'col-md-12 col-lg-12';
            }

            ?>
            <div class="pricing row">

                <?php
                foreach ( $plans as $plan ){

                    $plan = wp_parse_args( $plan,array(
                        'title' => '',
                        'code'  => '',
                        'price'  => '',
                        'subtitle' => '',
                        'content' => '',
                        'label' => esc_attr__( 'Choose Plan', 'sovenco-plus' ),
                        'link' => '#',
                        'button' => 'btn-theme-primary'
                    ) );

                    ?>
                    <div class="<?php echo esc_attr( $class ); ?> wow slideInUp">
                        <div class="pricing__item">
                            <h3 class="pricing__title"><?php echo esc_html( $plan['title'] ); ?></h3>
                            <div class="pricing__price"><span class="pricing__currency"><?php echo esc_html( $plan['code'] ); ?></span><?php echo esc_html( $plan['price'] ); ?></div>
                            <div class="pricing__sentense"><?php echo esc_html( $plan['subtitle'] ); ?></div>
                            <ul class="pricing__feature-list">
                                <?php
                                $list =  explode("\n", $plan['content'] );
                                $list = array_filter( $list );
                                foreach ( $list as $l ) {
                                    $l = trim( $l );
                                    if ( $l ){
                                        echo '<li>'.esc_html( $l ).'</li>';
                                    }
                                }
                                ?>
                            </ul>
                            <div class="pricing__button">
                                <a href="<?php echo esc_url( $plan['link'] ); ?>" class="btn <?php echo esc_attr( $plan['button'] ); ?> btn-lg btn-block"><?php echo esc_html( $plan['label'] ); ?></a>
                            </div>
                        </div>
                    </div>
                <?php } ?>


            </div>
        </div>

    </div>
    <?php do_action( 'sovenco_section_after_inner', 'pricing' ); ?>
<?php if ( ! sovenco_is_selective_refresh() ){ ?>
</section>
<?php } ?>
