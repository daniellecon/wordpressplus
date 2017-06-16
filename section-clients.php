<?php
$id       = get_theme_mod( 'sovenco_clients_id', 'clients' );
$title    = get_theme_mod( 'sovenco_clients_title');
$subtitle = get_theme_mod( 'sovenco_clients_subtitle', __('Have been featured on', 'sovenco-plus' ));
$desc     = get_theme_mod( 'sovenco_clients_desc' );

?>
<?php if ( ! sovenco_is_selective_refresh() ){ ?>
<section <?php if ( $id ) { ?>id="<?php echo esc_attr( $id ); ?>" <?php } ?> class="<?php echo esc_attr( apply_filters( 'sovenco_section_class', 'section-padding section-clients onepage-section', 'clients' ) ); ?>">
<?php } ?>
    <div class="container">
        <?php if ( $title || $subtitle || $desc ) { ?>
            <div class="section-title-area">
                <?php if ($subtitle != '') echo '<h5 class="section-subtitle">' . esc_html($subtitle) . '</h5>'; ?>
                <?php if ($title != '') echo '<h2 class="section-title">' . esc_html($title) . '</h2>'; ?>
                <?php if ($desc ) {
                    echo '<div class="section-desc">' . apply_filters( 'sovenco_the_content', wp_kses_post( $desc ) ) . '</div>';
                } ?>
            </div>
            <?php
        }

        $columns = get_theme_mod( 'sovenco_clients_layout', 5 );
        $clients = get_theme_mod( 'sovenco_clients' );
        if ( is_string( $clients ) ) {
            $clients = json_decode( $clients , true );
        }

        if ( empty( $clients ) ){
            $clients = array(
                array(
                    'title' => esc_html__( 'Hostingco', 'sovenco-plus' ),
                    'image'  => array(
                        'id'=> '',
                        'url'=> sovenco_PLUS_URL.'assets/images/client_logo_1.png',
                    ),
                    'link' => ''
                ),
                array(
                    'title' => esc_html__( 'Religion', 'sovenco-plus' ),
                    'image'  => array(
                        'id'=> '',
                        'url'=> sovenco_PLUS_URL.'assets/images/client_logo_2.png',
                    ),
                    'link' => ''
                ),
                array(
                    'title' => esc_html__( 'Viento', 'sovenco-plus' ),
                    'image'  => array(
                        'id'=> '',
                        'url'=> sovenco_PLUS_URL.'assets/images/client_logo_3.png',
                    ),
                    'link' => ''
                ),
                array(
                    'title' => esc_html__( 'Naturefirst', 'sovenco-plus' ),
                    'image'  => array(
                        'id'=> '',
                        'url'=> sovenco_PLUS_URL.'assets/images/client_logo_4.png',
                    ),
                    'link' => ''
                ),
                array(
                    'title' => esc_html__( 'Imagine', 'sovenco-plus' ),
                    'image'  => array(
                        'id'=> '',
                        'url'=> sovenco_PLUS_URL.'assets/images/client_logo_5.png',
                    ),
                    'link' => ''
                ),
            );
        }
        if ( $clients ) {
        ?>
        <div class="clients-wrapper slideInUp client-<?php echo esc_attr( $columns ); ?>-cols">
            <?php
            $j = 0;
            foreach ( $clients as $client ) {
                $url =  sovenco_PLus::get_media_url( $client['image'] );
                $classes = '';
                if ( $url ) {
                    if ($j >= $columns) {
                        $j = 1;
                        $classes .= ' clearleft';
                    } else {
                        $j++;
                    }
                    ?>
                    <div class="client-col<?php echo esc_attr( $classes ); ?>">
                        <?php if ( isset(  $client['link'] ) && $client['link'] != '' ){
                            echo '<a href="'.esc_url( $client['link'] ).'">';
                        } ?>
                        <img src="<?php echo esc_url( $url ); ?>">
                        <?php if ( isset(  $client['link'] ) && $client['link'] != '' ){
                            echo '</a>';
                        } ?>
                    </div>
                    <?php
                }
            }
            ?>
        </div>
        <?php } ?>
    </div>
<?php if ( ! sovenco_is_selective_refresh() ){ ?>
</section>
<?php } ?>
