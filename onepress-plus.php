<?php
/*
Plugin Name: sovenco Plus
Plugin URI: http://www.sovenco.com/
Description: The sovenco Plus plugin adds powerful premium features to sovenco theme.
Author: sovenco
Author URI:  http://www.sovenco.com/
Version: 1.2.5
Text Domain: sovenco-plus
License: GPL version 2 or later - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/

define( 'sovenco_PLUS_URL',  trailingslashit( plugins_url('', __FILE__) ));
define( 'sovenco_PLUS_PATH', trailingslashit( plugin_dir_path( __FILE__) ) );


/**
 * Class sovenco_PLus
 */
class sovenco_PLus {


    /**
     * Cache section settings
     *
     * @var array
     */
    public $section_settings = array();

    /**
     * Custom CSS code
     *
     * @var string
     */
    public $custom_css = '';


    function __construct(){

        load_plugin_textdomain( 'sovenco-plus', false, sovenco_PLUS_PATH . 'languages' );

        if ( ! function_exists( 'get_plugin_data' ) ) {
            require_once ABSPATH .'wp-admin/includes/plugin.php';
        }
        $plugin_data = get_plugin_data( __FILE__ );
        define( 'sovenco_PLUS_VERSION', $plugin_data['Version'] );

        add_action( 'sovenco_frontpage_section_parts', array( $this, 'load_section_parts' ) );
        add_filter( 'sovenco_reepeatable_max_item', array( $this, 'unlimited_repeatable_items' ) );
        add_action( 'sovenco_customize_after_register', array( $this, 'plugin_customize' ), 40 );
        add_action( 'wp', array( $this, 'int_setup' ) );

        add_action( 'wp_enqueue_scripts',  array( $this, 'custom_css' ) , 150 );
        add_action( 'wp_enqueue_scripts', array( $this, 'frontend_scripts' ), 60 );

        require_once sovenco_PLUS_PATH.'inc/post-type.php';
        require_once sovenco_PLUS_PATH.'inc/template-tags.php';
        require_once sovenco_PLUS_PATH.'inc/typography/helper.php';
        require_once sovenco_PLUS_PATH.'inc/typography/auto-apply.php';
        require_once sovenco_PLUS_PATH.'inc/auto-update/auto-update.php';
        require_once sovenco_PLUS_PATH.'inc/ajax.php';
        /**
         * @todo Include custom template file
         */
        add_filter( 'template_include', array( $this, 'template_include' ) );

        /**
         * @todo add selective refresh
         */
        add_filter( 'sovenco_customizer_partials_selective_refresh_keys', array( $this, 'selective_refresh' ) );

        // hook to import data
        add_action( 'ft_demo_import_current_item', array( $this, 'auto_import_id' ), 45 );

        add_action( 'wp', array( $this, 'wp' ) );
    }

    function wp(){
        $gallery_mod_name = 'sovenco_gallery_disable';
        add_filter( 'theme_mod_'.$gallery_mod_name, array( $this, 'filter_gallery_disable' ) );
    }

    function filter_gallery_disable( $val ){
        $sections = $this->get_sections_settings();
        if ( isset( $sections['gallery'] ) ) {
            if ( isset( $sections['gallery']['show_section'] ) && $sections['gallery']['show_section'] == 1 ) {
                $val = false;
            }
        }
        return $val;
    }

    function auto_import_id(){
        return 'sovenco-plus';
    }

    /**
     * Add selective refresh settings
     * @param $settings
     */
    function selective_refresh( $settings ) {

        $plus_settings = array(
            // + section clients
            array(
                'id' => 'clients',
                'selector' => '.section-clients',
                'settings' => array(
                    'sovenco_clients',
                    'sovenco_clients_title',
                    'sovenco_clients_subtitle',
                    'sovenco_clients_layout',
                    'sovenco_clients_desc',
                ),
            ),

            // + section cta
            array(
                'id' => 'cta',
                'selector' => '.section-cta',
                'settings' => array(
                    'sovenco_cta_title',
                    'sovenco_cta_btn_label',
                    'sovenco_cta_btn_link',
                    'sovenco_cta_btn_link_style',
                ),
            ),

            // + section pricing
            array(
                'id' => 'pricing',
                'selector' => '.section-pricing',
                'settings' => array(
                    'sovenco_pricing_plans',
                    'sovenco_pricing_title',
                    'sovenco_pricing_subtitle',
                    'sovenco_pricing_desc',
                ),
            ),
            // + section projects
            array(
                'id' => 'projects',
                'selector' => '.section-projects',
                'settings' => array(
                    'sovenco_projects_title',
                    'sovenco_projects_subtitle',
                    'sovenco_projects_desc',
                    'sovenco_projects_number',
                    'sovenco_projects_orderby',
                    'sovenco_projects_order',
                ),
            ),

            // + section testimonials
            array(
                'id' => 'testimonials',
                'selector' => '.section-testimonials',
                'settings' => array(
                    'sovenco_testimonial_boxes',
                    'sovenco_testimonial_title',
                    'sovenco_testimonial_subtitle',
                    'sovenco_testimonial_desc',
                ),
            ),
        );

        $settings = array_merge( $settings, $plus_settings );
        if ( isset( $settings['gallery'] ) ) {
            $settings['gallery']['settings'] = array(
                'sovenco_gallery_source',
                'sovenco_gallery_title',
                'sovenco_gallery_subtitle',
                'sovenco_gallery_desc',

                'sovenco_gallery_source_page',
                'sovenco_gallery_source_flickr',
                'sovenco_gallery_api_flickr',
                'sovenco_gallery_source_facebook',
                'sovenco_gallery_api_facebook',
                'sovenco_gallery_layout',
                'sovenco_gallery_display',
                'sovenco_g_number',
                'sovenco_g_row_height',
                'sovenco_g_col',

                'sovenco_g_readmore_link',
                'sovenco_g_readmore_text',
            );
        }

        return $settings;
    }

    /**
     * Load plugin template
     *
     * @param $template
     * @return bool|string
     */
    function template_include( $template ){
        global $post;
        if ( is_singular( 'portfolio' ) ){

            $is_child =  STYLESHEETPATH != TEMPLATEPATH ;
            $template_names = array();
            $template_names[] = 'single-portfolio.php';
            $template_names[] = 'portfolio.php';
            $located = false;

            foreach ( $template_names as $template_name ) {
                if (  !$template_name )
                    continue;

                if ( $is_child && file_exists( STYLESHEETPATH . '/' . $template_name ) ) {  // Child them
                    $located = STYLESHEETPATH . '/' . $template_name;
                    break;
                } elseif ( file_exists( sovenco_PLUS_PATH .'templates/' . $template_name ) ) { // Check part in the plugin
                    $located = sovenco_PLUS_PATH .'templates/'. $template_name;
                    break;
                } elseif ( file_exists(TEMPLATEPATH . '/' . $template_name) ) { // current_theme
                    $located = TEMPLATEPATH . '/' . $template_name;
                    break;
                }
            }

            if ( $located ) {
                return $located;
            }
        }
        return $template;
    }


    /**
     * Remove disable setting section when this plugin active
     *
     * @param $wp_customize
     */
    function remove_hide_control_sections( $wp_customize ){

        //$wp_customize->remove_setting( 'sovenco_hero_disable' );
        //$wp_customize->remove_control( 'sovenco_hero_disable' );

        $wp_customize->remove_setting( 'sovenco_features_disable' );
        $wp_customize->remove_control( 'sovenco_features_disable' );

        $wp_customize->remove_setting( 'sovenco_about_disable' );
        $wp_customize->remove_control( 'sovenco_about_disable' );

        $wp_customize->remove_setting( 'sovenco_services_disable' );
        $wp_customize->remove_control( 'sovenco_services_disable' );

        $wp_customize->remove_setting( 'sovenco_counter_disable' );
        $wp_customize->remove_control( 'sovenco_counter_disable' );

        $wp_customize->remove_setting( 'sovenco_testimonials_disable' );
        $wp_customize->remove_control( 'sovenco_testimonials_disable' );

        $wp_customize->remove_setting( 'sovenco_team_disable' );
        $wp_customize->remove_control( 'sovenco_team_disable' );

        $wp_customize->remove_setting( 'sovenco_news_disable' );
        $wp_customize->remove_control( 'sovenco_news_disable' );

        $wp_customize->remove_setting( 'sovenco_contact_disable' );
        $wp_customize->remove_control( 'sovenco_contact_disable' );

        // Remove upsell panel/section
        $wp_customize->remove_setting( 'sovenco_order_styling_message' );
        $wp_customize->remove_control( 'sovenco_order_styling_message' );

        $wp_customize->remove_setting( 'sovenco_videolightbox_image' );
        $wp_customize->remove_control( 'sovenco_videolightbox_image' );
        $wp_customize->remove_control( 'sovenco_videolightbox_disable' );
        $wp_customize->remove_control( 'sovenco_videolightbox_disable' );

        $wp_customize->remove_setting( 'sovenco_gallery_disable' );
        $wp_customize->remove_control( 'sovenco_gallery_disable' );
        remove_theme_mod( 'sovenco_gallery_disable' );

        // Remove hero background media upsell
        $wp_customize->remove_control( 'sovenco_hero_videobackground_upsell' );

    }

    /**
     *  Get default sections settings
     *
     * @return array
     */
    function get_default_sections_settings(){
        return apply_filters( 'sovenco_get_default_sections_settings', array(

                array(
                    'title' => esc_html__( 'Clients', 'sovenco-plus' ),
                    'section_id' => 'clients',
                    'show_section' => '1',
                    'bg_color' => '',
                    'bg_opacity' => '',
                    'bg_opacity_color' => '',
                    'bg_image' => '',
                    'bg_video' => '',
                    'section_inverse' => '',
                    'enable_parallax' => '',
                    'padding_top' => '',
                    'padding_bottom' => '',
                ),

                array(
                    'title' => esc_html__( 'Features', 'sovenco-plus' ),
                    'section_id' => 'features',
                    'show_section' => get_theme_mod( 'sovenco_features_disable', '' ) == 1 ?  '': 1,
                    'bg_color' => '',
                    'bg_opacity' => '',
                    'bg_opacity_color' => '',
                    'bg_image' => '',
                    'bg_video' => '',
                    'section_inverse' => '',
                    'enable_parallax' => '',
                    'padding_top' => '',
                    'padding_bottom' => '',
                ),
                array(
                    'title' => esc_html__( 'About', 'sovenco-plus' ),
                    'section_id' => 'about',
                    'show_section' => get_theme_mod( 'sovenco_about_disable', '' ) == 1 ?  '': 1,
                    'bg_color' => '',
                    'bg_opacity' => '',
                    'bg_opacity_color' => '',
                    'bg_image' => '',
                    'bg_video' => '',
                    'section_inverse' => '',
                    'enable_parallax' => '',
                    'padding_top' => '',
                    'padding_bottom' => '',
                ),
                array(
                    'title' => esc_html__( 'Services', 'sovenco-plus' ),
                    'section_id' => 'services',
                    'show_section' => get_theme_mod( 'sovenco_services_id', '' ) == 1 ?  '': 1,
                    'bg_color' => '',
                    'bg_opacity' => '',
                    'bg_opacity_color' => '',
                    'bg_image' => '',
                    'bg_video' => '',
                    'section_inverse' => '',
                    'enable_parallax' => '',
                    'padding_top' => '',
                    'padding_bottom' => '',
                ),

                array(
                    'title' => esc_html__( 'Videolightbox', 'sovenco-plus' ),
                    'section_id' => 'videolightbox',
                    'show_section' => get_theme_mod( 'sovenco_videolightbox_disable', '' ) == 1 ?  '': 1,
                    'bg_color' => '',
                    'bg_opacity' => '',
                    'bg_opacity_color' => '',
                    'bg_image' => array(
                        'id' => '',
                        'url' => get_template_directory_uri().'/assets/images/hero5.jpg'
                    ),
                    'bg_video' => '',
                    'section_inverse' => '1',
                    'enable_parallax' => '1',
                    'padding_top' => '',
                    'padding_bottom' => '',
                ),

                array(
                    'title' => esc_html__( 'Gallery', 'sovenco-plus' ),
                    'section_id' => 'gallery',
                    'show_section' => get_theme_mod( 'sovenco_gallery_disable', 1 ) == 1 ?  '': 1,
                    'bg_color' => '',
                    'bg_opacity' => '',
                    'bg_opacity_color' => '',
                    'bg_image' => '',
                    'bg_video' => '',
                    'section_inverse' => '',
                    'enable_parallax' => '',
                    'padding_top' => '',
                    'padding_bottom' => '',
                ),

                array(
                    'title' => esc_html__( 'Projects', 'sovenco-plus' ),
                    'section_id' => 'projects',
                    'show_section' => 1,
                    'bg_color' => '',
                    'bg_opacity' => '',
                    'bg_opacity_color' => '',
                    'bg_image' => '',
                    'bg_video' => '',
                    'section_inverse' => '',
                    'enable_parallax' => '',
                    'padding_top' => '',
                    'padding_bottom' => '',
                ),

                array(
                    'title' => esc_html__( 'Counter', 'sovenco-plus' ),
                    'section_id' => 'counter',
                    'show_section' => get_theme_mod( 'sovenco_counter_disable', '' ) == 1 ?  '': 1,
                    'bg_color' => '',
                    'bg_opacity' => '',
                    'bg_opacity_color' => '',
                    'bg_image' => '',
                    'bg_video' => '',
                    'section_inverse' => '',
                    'enable_parallax' => '',
                    'padding_top' => '',
                    'padding_bottom' => '',
                ),

                array(
                    'title' => esc_html__( 'Testimonials', 'sovenco-plus' ),
                    'section_id' => 'testimonials',
                    'show_section' => get_theme_mod( 'sovenco_testimonials_disable', '' ) == 1 ?  '': 1,
                    'bg_color' => '',
                    'bg_opacity' => '',
                    'bg_opacity_color' => '',
                    'bg_image' => '',
                    'bg_video' => '',
                    'section_inverse' => '',
                    'enable_parallax' => '',
                    'padding_top' => '',
                    'padding_bottom' => '',
                ),

                array(
                    'title' => esc_html__( 'Pricing', 'sovenco-plus' ),
                    'section_id' => 'pricing',
                    'show_section' => get_theme_mod( 'sovenco_pricing_disable', '' ) == 1 ?  '': 1,
                    'bg_color' => '',
                    'bg_opacity' => '',
                    'bg_opacity_color' => '',
                    'bg_image' => '',
                    'bg_video' => '',
                    'section_inverse' => '',
                    'enable_parallax' => '',
                    'padding_top' => '',
                    'padding_bottom' => '',
                ),

                array(
                    'title' => esc_html__( 'Call to Action', 'sovenco-plus' ),
                    'section_id' => 'cta',
                    'show_section' => 1,
                    'bg_color' => '',
                    'bg_opacity' => '',
                    'bg_opacity_color' => '',
                    'bg_image' => '',
                    'bg_video' => '',
                    'section_inverse' => '1',
                    'enable_parallax' => '',
                    'padding_top' => '',
                    'padding_bottom' => '',
                ),

                array(
                    'title' => esc_html__( 'Team', 'sovenco-plus' ),
                    'section_id' => 'team',
                    'show_section' => get_theme_mod( 'sovenco_team_disable', '' ) == 1 ?  '': 1,
                    'bg_color' => '',
                    'bg_opacity' => '',
                    'bg_opacity_color' => '',
                    'bg_image' => '',
                    'bg_video' => '',
                    'section_inverse' => '',
                    'enable_parallax' => '',
                    'padding_top' => '',
                    'padding_bottom' => '',
                ),

                array(
                    'title' => esc_html__( 'News', 'sovenco-plus' ),
                    'section_id' => 'news',
                    'show_section' => get_theme_mod( 'sovenco_news_disable', '' ) == 1 ?  '': 1,
                    'bg_color' => '',
                    'bg_opacity' => '',
                    'bg_opacity_color' => '',
                    'bg_image' => '',
                    'bg_video' => '',
                    'section_inverse' => '',
                    'enable_parallax' => '',
                    'padding_top' => '',
                    'padding_bottom' => '',
                ),

                array(
                    'title' => esc_html__( 'Contact', 'sovenco-plus' ),
                    'section_id' => 'contact',
                    'show_section' => get_theme_mod( 'sovenco_contact_disable', '' ) == 1 ?  '': 1,
                    'bg_color' => '',
                    'bg_opacity' => '',
                    'bg_opacity_color' => '',
                    'bg_image' => '',
                    'bg_video' => '',
                    'section_inverse' => '',
                    'enable_parallax' => '',
                    'padding_top' => '',
                    'padding_bottom' => '',
                ),


                array(
                    'title' => esc_html__( 'Map', 'sovenco-plus' ),
                    'section_id' => 'map',
                    'show_section' => '1',
                    'bg_color' => '',
                    'bg_opacity' => '',
                    'bg_opacity_color' => '',
                    'bg_image' => '',
                    'bg_video' => '',
                    'section_inverse' => '',
                    'enable_parallax' => '',
                    'padding_top' => '',
                    'padding_bottom' => '',
                ),
            )
        );
    }


    /**
     * Add more customize
     *
     * @param $wp_customize
     */
    function plugin_customize( $wp_customize ){

        $this->remove_hide_control_sections( $wp_customize );

        include_once sovenco_PLUS_PATH.'inc/typography/typography.php';

        // Theme Global
        // Copyright text option
        $wp_customize->add_setting( 'sovenco_footer_copyright_text',
            array(
                'sanitize_callback' => 'sovenco_sanitize_text',
                'default'           => sprintf( esc_html__( 'Copyright %1$s %2$s %3$s', 'sovenco-plus' ), '&copy;', esc_attr( date( 'Y' ) ), esc_attr( get_bloginfo() ) ),
            )
        );

        $wp_customize->add_control( new sovenco_Editor_Custom_Control(
            $wp_customize,
            'sovenco_footer_copyright_text',
            array(
                'label'       => esc_html__('Footer Copyright', 'sovenco-plus'),
                'section'     => 'sovenco_global_settings',
                'description' => esc_html__('Arbitrary text or HTML.', 'sovenco-plus')
            )
        ));

        // Disable theme author link
        $wp_customize->add_setting( 'sovenco_hide_author_link',
            array(
                'sanitize_callback' => 'sovenco_sanitize_checkbox',
                'default'           => '',
            )
        );
        $wp_customize->add_control( 'sovenco_hide_author_link',
            array(
                'type'        => 'checkbox',
                'label'       => esc_html__('Hide theme author link?', 'sovenco-plus'),
                'section'     => 'sovenco_global_settings',
                'description' => esc_html__('Check this box to hide theme author link.', 'sovenco-plus')
            )
        );

        // Typography
        // Register typography control JS template.
        $wp_customize->register_control_type( 'sovenco_Customize_Typography_Control' );

       $wp_customize->add_panel( 'sovenco_typo', array( 'priority' => 25, 'title' => esc_html__( 'Typography', 'sovenco-plus' ) ) );

        // For P tag
        $wp_customize->add_section(
            'sovenco_typography_section',
            array( 'panel'=> 'sovenco_typo',
                'title' => esc_html__( 'Paragraphs', 'sovenco-plus' ), 'priority' => 5, )
        );

        // Add the `<p>` typography settings.
        // @todo Better sanitize_callback functions.
        $wp_customize->add_setting(
            'sovenco_typo_p',
            array(
                'sanitize_callback' => 'sovenco_sanitize_typography_field',
                'transport' => 'postMessage'
            )
        );

        $wp_customize->add_control(
            new sovenco_Customize_Typography_Control(
                $wp_customize,
                'sovenco_typo_p',
                array(
                    'label'       => esc_html__( 'Paragraph Typography', 'sovenco-plus' ),
                    'description' => esc_html__( 'Select how you want your paragraphs to appear.', 'sovenco-plus' ),
                    'section'       => 'sovenco_typography_section',
                    'css_selector'       => 'body p, body', // css selector for live view
                    'fields' => array(
                        'font-family'     => '',
                        'color'           => '',
                        'font-style'      => '', // italic
                        'font-weight'     => '',
                        'font-size'       => '',
                        'line-height'     => '',
                        'letter-spacing'  => '',
                        'text-transform'  => '',
                        'text-decoration' => '',
                    )
                )
            )
        );

        // For Menu
        $wp_customize->add_section(
            'sovenco_typo_menu_section',
            array(
                'panel'=> 'sovenco_typo',
                'title' => esc_html__( 'Menu', 'sovenco-plus' ), 'priority' => 5, )
        );

        // Add the menu typography settings.

       // Site title font
        $wp_customize->add_setting(
            'sovenco_typo_site_title',
            array(
                'sanitize_callback' => 'sovenco_sanitize_typography_field',
                'transport' => 'postMessage',
                'priority' => 100,
            )
        );

        $wp_customize->add_control(
            new sovenco_Customize_Typography_Control(
                $wp_customize,
                'sovenco_typo_site_title',
                array(
                    'label'       => esc_html__( 'Site title Typography', 'sovenco-plus' ),
                    'description' => esc_html__( 'Select how you want your site to appear.', 'sovenco-plus' ),
                    'section'       => 'title_tagline',
                    'css_selector'       => '#page .site-branding .site-title, #page .site-branding .site-text-logo', // css selector for live view
                    'fields' => array(
                        'font-family'     => '',
                        'font-style'      => '', // italic
                        'font-weight'     => '',
                        'color'           => '',
                    )
                )
            )
        );

        // Site tagline font
        $wp_customize->add_setting(
            'sovenco_typo_site_tagline',
            array(
                'sanitize_callback' => 'sovenco_sanitize_typography_field',
                'transport' => 'postMessage',
                'priority' => 120,
            )

        );

        $wp_customize->add_control(
            new sovenco_Customize_Typography_Control(
                $wp_customize,
                'sovenco_typo_site_tagline',
                array(
                    'label'       => esc_html__( 'Site Tagline Typography', 'sovenco-plus' ),
                    'description' => esc_html__( 'Select how you want your site to appear.', 'sovenco-plus' ),
                    'section'       => 'title_tagline',
                    'css_selector'       => '#page .site-branding .site-description', // css selector for live view
                    'fields' => array(
                        'font-family'     => '',
                        'color'           => '',
                        'font-style'      => '', // italic
                        'font-weight'     => '',
                        'font-size'     => '',
                    )
                )
            )
        );


        // @todo Better sanitize_callback functions.
        $wp_customize->add_setting(
            'sovenco_typo_menu',
            array(
                'sanitize_callback' => 'sovenco_sanitize_typography_field',
                'transport' => 'postMessage'
            )
        );

        $wp_customize->add_control(
            new sovenco_Customize_Typography_Control(
                $wp_customize,
                'sovenco_typo_menu',
                array(
                    'label'       => esc_html__( 'Menu Typography', 'sovenco-plus' ),
                    'description' => esc_html__( 'Select how you want your Menu to appear.', 'sovenco-plus' ),
                    'section'       => 'sovenco_typo_menu_section',
                    'css_selector'       => '.sovenco-menu a', // css selector for live view
                    'fields' => array(
                        'font-family'     => '',
                        //'color'           => '',
                        'font-style'      => '', // italic
                        'font-weight'     => '',
                        'font-size'       => '',
                        //'line-height'     => '',
                        'letter-spacing'  => '',
                        'text-transform'  => '',
                        'text-decoration' => '',
                    )
                )
            )
        );

        // For Heading
        $wp_customize->add_section(
            'sovenco_typo_heading_section',
            array(
                'panel'=> 'sovenco_typo',
                'title' => esc_html__( 'Heading', 'sovenco-plus' ), 'priority' => 5, )
        );

        // Add the menu typography settings.
        // @todo Better sanitize_callback functions.
        $wp_customize->add_setting(
            'sovenco_typo_heading',
            array(
                'sanitize_callback' => 'sovenco_sanitize_typography_field',
                'transport' => 'postMessage'
            )
        );

        $wp_customize->add_control(
            new sovenco_Customize_Typography_Control(
                $wp_customize,
                'sovenco_typo_heading',
                array(
                    'label'       => esc_html__( 'Heading Typography', 'sovenco-plus' ),
                    'description' => esc_html__( 'Select how you want your Heading to appear.', 'sovenco-plus' ),
                    'section'       => 'sovenco_typo_heading_section',
                    'css_selector'       => 'body h1, body h2, body h3, body h4, body h5, body h6', // css selector for live view
                    'fields' => array(
                        'font-family'     => '',
                        //'color'           => '',
                        //'font-size'       => false, // italic
                        'font-style'      => '', // italic
                        'font-weight'     => '',
                        'line-height'     => '',
                        'letter-spacing'  => '',
                        'text-transform'  => '',
                        'text-decoration' => '',
                    )
                )
            )
        );
        // end typo

        // Team member settings
        // Remove theme team
        $wp_customize->remove_setting( 'sovenco_team_members' );
        $wp_customize->remove_control( 'sovenco_team_members' );


        $wp_customize->add_setting(
            'sovenco_team_members',
            array(
                'sanitize_callback' => 'sovenco_sanitize_repeatable_data_field',
                'transport' => 'refresh', // refresh or postMessage
            ) );


        $wp_customize->add_control(
            new sovenco_Customize_Repeatable_Control(
                $wp_customize,
                'sovenco_team_members',
                array(
                    'label'     => esc_html__('Team members', 'sovenco-plus'),
                    'description'   => '',
                    'section'       => 'sovenco_team_content',
                    //'live_title_id' => 'user_id', // apply for unput text and textarea only
                    'title_format'  => esc_html__( '[live_title]', 'sovenco-plus'), // [live_title]
                    'max_item'      => 4, // Maximum item can add
                    'fields'    => array(
                        'user_id' => array(
                            'title' => esc_html__('User media', 'sovenco-plus'),
                            'type'  =>'media',
                            'desc'  => '',
                        ),
                        'link' => array(
                            'title' => esc_html__('Custom Link', 'sovenco-plus'),
                            'type'  =>'text',
                            'desc'  => '',
                        ),

                        'url' => array(
                            'title' => esc_html__('Website', 'sovenco-plus'),
                            'type'  =>'text',
                            'desc'  => '',
                        ),
                        'facebook' => array(
                            'title' => esc_html__('Facebook', 'sovenco-plus'),
                            'type'  =>'text',
                            'desc'  => '',
                        ),
                        'twitter' => array(
                            'title' => esc_html__('Twitter', 'sovenco-plus'),
                            'type'  =>'text',
                            'desc'  => '',
                        ),
                        'google_plus' => array(
                            'title' => esc_html__('Google+', 'sovenco-plus'),
                            'type'  =>'text',
                            'desc'  => '',
                        ),
                        'linkedin' => array(
                            'title' => esc_html__('linkedin', 'sovenco-plus'),
                            'type'  =>'text',
                            'desc'  => '',
                        ),
                        'email' => array(
                            'title' => esc_html__('Email', 'sovenco-plus'),
                            'type'  =>'text',
                            'desc'  => '',
                        ),
                    ),

                )
            )
        );
        // End section team



        // Order and styling
        $wp_customize->add_section( 'sovenco_section_order' ,
            array(
                'priority'    => 125,
                'title'       => esc_html__( 'Section Order & Styling', 'sovenco-plus' ),
                'description' => '',
                'active_callback' => ( function_exists( 'sovenco_showon_frontpage' ) ) ? 'sovenco_showon_frontpage' : false
            )
        );
       // remove_theme_mod( 'sovenco_section_order_styling' );

        // Hero section
        // Video MP4
        $wp_customize->add_setting( 'sovenco_hero_video_mp4',
            array(
                'sanitize_callback' => 'sovenco_sanitize_text',
                'default'           => '',
                'transport' => 'refresh', // refresh or postMessage
            )
        );
        $wp_customize->add_control( new WP_Customize_Media_Control(
                $wp_customize,
                'sovenco_hero_video_mp4',
                array(
                    'label' 		=> esc_html__('Background Video (.MP4)', 'sovenco-plus'),
                    'section' 		=> 'sovenco_hero_images',
                    'priority'      => 100,
                )
            )
        );
        // Video webm
        $wp_customize->add_setting( 'sovenco_hero_video_webm',
            array(
                'sanitize_callback' => 'sovenco_sanitize_text',
                'default'           => '',
                'transport' => 'refresh', // refresh or postMessage
            )
        );
        $wp_customize->add_control( new WP_Customize_Media_Control(
                $wp_customize,
                'sovenco_hero_video_webm',
                array(
                    'label' 		=> esc_html__('Background Video(.WEBM)', 'sovenco-plus'),
                    'section' 		=> 'sovenco_hero_images',
                    'priority'      => 105,
                )
            )
        );
        // Video OGV
        $wp_customize->add_setting( 'sovenco_hero_video_ogv',
            array(
                'sanitize_callback' => 'sovenco_sanitize_text',
                'default'           => '',
                'transport' => 'refresh', // refresh or postMessage
            )
        );
        $wp_customize->add_control( new WP_Customize_Media_Control(
                $wp_customize,
                'sovenco_hero_video_ogv',
                array(
                    'label' 		=> esc_html__('Background Video(.OGV)', 'sovenco-plus'),
                    'section' 		=> 'sovenco_hero_images',
                    'priority'      => 110,
                )
            )
        );
        // Hero mobile video fallback
        $wp_customize->add_setting( 'sovenco_hero_mobile_img',
            array(
                'sanitize_callback' => 'sovenco_sanitize_checkbox',
                'default'           => '',
            )
        );
        $wp_customize->add_control( 'sovenco_hero_mobile_img',
            array(
                'type'        => 'checkbox',
                'priority'      => 115,
                'label'       => esc_html__('On mobile replace video with first background image.', 'sovenco-plus'),
                'section'     => 'sovenco_hero_images',
            )
        );

        // END Hero section

        $wp_customize->add_setting(
            'sovenco_section_order_styling',
            array(
                //'default' => json_encode( $this->get_default_sections_settings() ),
                'sanitize_callback' => 'sovenco_sanitize_repeatable_data_field',
                'transport' => 'refresh', // refresh or postMessage
            ) );

        $wp_customize->add_control(
            new sovenco_Customize_Repeatable_Control(
                $wp_customize,
                'sovenco_section_order_styling',
                array(
                    'label' 		=> esc_html__('Section Order & Styling', 'sovenco-plus'),
                    'description'   => '',
                    'section'       => 'sovenco_section_order',
                    'live_title_id' => 'title', // apply for unput text and textarea only
                    'title_format'  => esc_html__('[Custom Section]: [live_title]', 'sovenco-plus'), // [live_title]
                    'changeable'    => 'no', // Can Remove, add new button  default yes
                    'defined_values'   => $this->get_default_sections_settings(),
                    'id_key'    => 'section_id',
                    'default_empty_title'  => esc_html__('Untitled', 'sovenco-plus'), // [live_title]
                    'fields'    => array(
                        'add_by' => array(
                            'type'  =>'add_by',
                        ),
                        'title' => array(
                            'title' => esc_html__('Title', 'sovenco-plus'),
                            'type'  =>'hidden',
                            'desc'  => ''
                        ),
                        'section_id' => array(
                            'title' => esc_html__('Section ID', 'sovenco-plus'),
                            'type'  =>'hidden',
                            'desc'  => ''
                        ),
                        'show_section' => array(
                            'title' => esc_html__('Show this section', 'sovenco-plus'),
                            'type'  =>'checkbox',
                            'default'  =>'1',
                        ),
                        'section_inverse' => array(
                            'title' => esc_html__('Inverted Style', 'sovenco-plus'),
                            'desc'  => esc_html__('Make this section darker', 'sovenco-plus'),
                            'type'  =>'checkbox',
                        ),
                        'hide_title' => array(
                            'title' => esc_html__('Hide section title', 'sovenco-plus'),
                            'type'  =>'checkbox',
                            'desc'  => '',
                            'required' => array( 'add_by', '=', 'click' ) ,
                        ),
                        'subtitle' => array(
                            'title' => esc_html__('Subtitle', 'sovenco-plus'),
                            'type'  =>'text',
                            'required' => array( 'add_by', '=', 'click' ) ,
                        ),
                        'desc' => array(
                            'title' => esc_html__('Section Description', 'sovenco-plus'),
                            'type'  =>'editor',
                            'required' => array( 'add_by', '=', 'click' ) ,
                        ),
                        'content' => array(
                            'title' => esc_html__('Section Content', 'sovenco-plus'),
                            'type'  =>'editor',
                            'required' => array( 'add_by', '=', 'click' ) ,
                        ),
                        'bg_type' => array(
                            'title' => esc_html__('Background Type', 'sovenco-plus'),
                            'type'  =>'select',
                            'options'  => array(
                                'color' => esc_html__('Color', 'sovenco-plus'),
                                'image' => esc_html__('Image', 'sovenco-plus'),
                                'video' => esc_html__('Video', 'sovenco-plus'),
                            ),
                        ),
                        'bg_color' => array(
                            'title' => esc_html__('Background Color', 'sovenco-plus'),
                            'type'  =>'coloralpha',
                            'required' => array( 'bg_type', '=', 'color' ) ,
                        ),
                        'bg_image' => array(
                            'title' => esc_html__('Background Image', 'sovenco-plus'),
                            'type'  =>'media',
                            'required' => array( 'bg_type', 'in', array( 'video', 'image' ) ) ,
                        ),
                        'enable_parallax' => array(
                            'title' => esc_html__('Enable Parallax', 'sovenco-plus'),
                            'desc'  => esc_html__('Required background image and Inverted Style is checked', 'sovenco-plus'),
                            'type'  =>'checkbox',
                            'required' => array( 'bg_type', '=', 'image' ) ,
                        ),
                        'bg_video' => array(
                            'title' => esc_html__('Background video(.MP4)', 'sovenco-plus'),
                            'type'  =>'media',
                            'media'  =>'video',
                            'required' => array( 'bg_type', '=', 'video' ) ,
                            //'desc' => esc_html__('Select your video background', 'sovenco-plus'),
                        ),
                        'bg_video_webm' => array(
                            'title' => esc_html__('Background video(.WEBM)', 'sovenco-plus'),
                            'type'  =>'media',
                            'media'  =>'video',
                            'required' => array( 'bg_type', '=', 'video' ) ,
                            //'desc' => esc_html__('Select your video background', 'sovenco-plus'),
                        ),
                        'bg_video_ogv' => array(
                            'title' => esc_html__('Background video(.OGV)', 'sovenco-plus'),
                            'type'  =>'media',
                            'media'  =>'video',
                            'required' => array( 'bg_type', '=', 'video' ) ,
                            //'desc' => esc_html__('Select your video background', 'sovenco-plus'),
                        ),

                        'bg_opacity_color' => array(
                            'title' => esc_html__('Overlay Color', 'sovenco-plus'),
                            'type'  =>'coloralpha',
                            'required' => array( 'bg_type', 'in', array( 'video', 'image' ) ) ,
                        ),

                        /*
                        'bg_opacity' => array(
                            'title' => esc_html__('Overlay Color Opacity', 'sovenco-plus'),
                            'type'  =>'text',
                            'desc' => esc_html__('Enter a float number between 0.1 to 0.9', 'sovenco-plus'),
                        ),
                        */

                        'padding_top' => array(
                            'title' => esc_html__('Section Padding Top', 'sovenco-plus'),
                            'type'  =>'text',
                            'desc' => esc_html__('Eg. 50px, 30%, leave empty for default value', 'sovenco-plus'),
                        ),
                        'padding_bottom' => array(
                            'title' => esc_html__('Section Padding Bottom', 'sovenco-plus'),
                            'type'  =>'text',
                            'desc' => esc_html__('Eg. 50px, 30%, leave empty for default value', 'sovenco-plus'),
                        ),

                    ),
                )
            )
        );

        /*------------------------------------------------------------------------*/
        /*  Section: Testimonials
        /*------------------------------------------------------------------------*/
        $wp_customize->add_panel( 'sovenco_testimonial' ,
            array(
                'priority'        => 220,
                'title'           => esc_html__( 'Section: Testimonial', 'sovenco-plus' ),
                'description'     => '',
                'active_callback' => 'sovenco_showon_frontpage'
            )
        );

        $wp_customize->add_section( 'sovenco_testimonial_settings' ,
            array(
                'priority'    => 3,
                'title'       => esc_html__( 'Section Settings', 'sovenco-plus' ),
                'description' => '',
                'panel'       => 'sovenco_testimonial',
            )
        );
        // Show Content
        /*
        $wp_customize->add_setting( 'sovenco_testimonials_disable',
            array(
                'sanitize_callback' => 'sovenco_sanitize_checkbox',
                'default'           => '',
            )
        );
        $wp_customize->add_control( 'sovenco_testimonials_disable',
            array(
                'type'        => 'checkbox',
                'label'       => esc_html__('Hide this section?', 'sovenco-plus'),
                'section'     => 'sovenco_testimonial_settings',
                'description' => esc_html__('Check this box to hide this section.', 'sovenco-plus'),
            )
        );
        */

        // Section ID
        $wp_customize->add_setting( 'sovenco_testimonial_id',
            array(
                'sanitize_callback' => 'sovenco_sanitize_text',
                'default'           => esc_html__('testimonials', 'sovenco-plus'),
            )
        );
        $wp_customize->add_control( 'sovenco_testimonial_id',
            array(
                'label'     => esc_html__('Section ID:', 'sovenco-plus'),
                'section' 		=> 'sovenco_testimonial_settings',
                'description'   => esc_html__( 'The section id, we will use this for link anchor.', 'sovenco-plus' ),
            )
        );

        // Title
        $wp_customize->add_setting( 'sovenco_testimonial_title',
            array(
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => esc_html__('Testimonials', 'sovenco-plus'),
            )
        );
        $wp_customize->add_control( 'sovenco_testimonial_title',
            array(
                'label'     => esc_html__('Section Title', 'sovenco-plus'),
                'section' 		=> 'sovenco_testimonial_settings',
                'description'   => '',
            )
        );

        // Sub Title
        $wp_customize->add_setting( 'sovenco_testimonial_subtitle',
            array(
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => esc_html__('Section subtitle', 'sovenco-plus'),
            )
        );
        $wp_customize->add_control( 'sovenco_testimonial_subtitle',
            array(
                'label'     => esc_html__('Section Subtitle', 'sovenco-plus'),
                'section' 		=> 'sovenco_testimonial_settings',
                'description'   => '',
            )
        );

        // Description
        $wp_customize->add_setting( 'sovenco_testimonial_desc',
            array(
                'sanitize_callback' => 'sovenco_sanitize_text',
                'default'           => '',
            )
        );
        $wp_customize->add_control( new sovenco_Editor_Custom_Control(
            $wp_customize,
            'sovenco_testimonial_desc',
            array(
                'label' 		=> esc_html__('Section Description', 'sovenco-plus'),
                'section' 		=> 'sovenco_testimonial_settings',
                'description'   => '',
            )
        ));

        // Testimonials content
        $wp_customize->add_section( 'sovenco_testimonials_content' ,
            array(
                'priority'    => 3,
                'title'       => esc_html__( 'Section Content', 'sovenco-plus' ),
                'description' => '',
                'panel'       => 'sovenco_testimonial',
            )
        );
        $wp_customize->add_setting(
            'sovenco_testimonial_boxes',
            array(
                'default' => json_encode(
                    array(
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

                    )
                ),
                'sanitize_callback' => 'sovenco_sanitize_repeatable_data_field',
                'transport' => 'refresh', // refresh or postMessage
            ) );

        $wp_customize->add_control(
            new sovenco_Customize_Repeatable_Control(
                $wp_customize,
                'sovenco_testimonial_boxes',
                array(
                    'label'     => esc_html__('Testimonial', 'sovenco-plus'),
                    'description'   => '',
                    'section'       => 'sovenco_testimonials_content',
                    'live_title_id' => 'title', // apply for unput text and textarea only
                    'title_format'  => esc_html__( '[live_title]', 'sovenco-plus'), // [live_title]
                    'max_item'      => 3, // Maximum item can add

                    'fields'    => array(
                        'title' => array(
                            'title' => esc_html__('Title', 'sovenco-plus'),
                            'type'  =>'text',
                            'desc'  => '',
                            'default'  => esc_html__('Testimonial title', 'sovenco-plus'),
                        ),
                        'name' => array(
                            'title' => esc_html__('Name', 'sovenco-plus'),
                            'type'  =>'text',
                            'desc'  => '',
                            'default'  => esc_html__('User name', 'sovenco-plus'),
                        ),
                        'image' => array(
                            'title' => esc_html__('Avatar', 'sovenco-plus'),
                            'type'  =>'media',
                            'desc'  => esc_html__( 'Suggestion: 100x100px square image.', 'sovenco-plus' ),
                            'default' => array(
                                'url' => get_template_directory_uri().'/assets/images/testimonial_1.jpg',
                                'id' => ''
                            )
                        ),
                        'subtitle' => array(
                            'title' => esc_html__('Subtitle', 'sovenco-plus'),
                            'type'  =>'textarea',
                            'default'  => esc_html__('Example Company', 'sovenco-plus'),
                        ),
                        'content' => array(
                            'title' => esc_html__('Content', 'sovenco-plus'),
                            'type'  =>'textarea',
                            'default'  => esc_html__('Whatever your user say', 'sovenco-plus'),
                        ),

                        'style' => array(
                            'title' => esc_html__('Style', 'sovenco-plus'),
                            'type'  =>'select',
                            'default'  => 'light',
                            'options' => array(
                                'theme-primary' => esc_html__( 'Theme default', 'sovenco-plus' ),
                                'light' => esc_html__( 'Light', 'sovenco-plus' ),
                                'primary' => esc_html__( 'Primary', 'sovenco-plus' ),
                                'success' => esc_html__( 'Success', 'sovenco-plus' ),
                                'info' => esc_html__( 'Info', 'sovenco-plus' ),
                                'warning' => esc_html__( 'Warning', 'sovenco-plus' ),
                                'danger' => esc_html__( 'Danger', 'sovenco-plus' ),
                            )
                        ),


                    ),

                )
            )
        );


        /*------------------------------------------------------------------------*/
        /*  Section: Map
        /*------------------------------------------------------------------------*/
        $wp_customize->add_panel( 'sovenco_map' ,
            array(
                'priority'        => 280,
                'title'           => __( 'Section: Map', 'sovenco-plus' ),
                'description'     => '',
                'active_callback' => 'sovenco_showon_frontpage'
            )
        );

        $wp_customize->add_section( 'sovenco_map_settings' ,
            array(
                'priority'    => 3,
                'title'       => __( 'Section Settings', 'sovenco-plus' ),
                'panel'       => 'sovenco_map',
            )
        );

        // Section ID
        $wp_customize->add_setting( 'sovenco_map_id',
            array(
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => 'map',
            )
        );

        $wp_customize->add_control( 'sovenco_map_id',
            array(
                'label' 		=> __('Section ID', 'sovenco-plus'),
                'section' 		=> 'sovenco_map_settings',
                'description'   => '',
            )
        );

        // Map api key code
        $wp_customize->add_setting( 'sovenco_map_api_key',
            array(
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => '',
            )
        );
        $wp_customize->add_control( 'sovenco_map_api_key',
            array(
                'label'       => __('Google map api key', 'sovenco-plus'),
                'section'     => 'sovenco_map_settings',
                'description' => __('In order to show the Google Maps section, you must enter a validate Google Maps API key, you can get one <a href="https://developers.google.com/maps/documentation/javascript/get-api-key" target="_blank">here</a>.', 'sovenco-plus'),
            )
        );

        // Latitude
        $wp_customize->add_setting( 'sovenco_map_lat',
            array(
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => '37.3317115',
            )
        );

        $wp_customize->add_control( 'sovenco_map_lat',
            array(
                'label' 		=> __('Latitude', 'sovenco-plus'),
                'section' 		=> 'sovenco_map_settings',
                'description'   => '',
            )
        );

        // Longitude
        $wp_customize->add_setting( 'sovenco_map_long',
            array(
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => '-122.0301835',
            )
        );
        $wp_customize->add_control( 'sovenco_map_long',
            array(
                'label' 		=> __('Longitude', 'sovenco-plus'),
                'section' 		=> 'sovenco_map_settings',
            )
        );

        // sovenco_Misc_Control

        $wp_customize->add_setting( 'sovenco_map_message',
            array(
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => '',
            )
        );
        $wp_customize->add_control(
            new sovenco_Misc_Control(
                $wp_customize,
                'sovenco_map_message',
                array(
                    'label' 		=> __('Longitude', 'sovenco-plus'),
                    'type'          => 'custom_message',
                    'section' 		=> 'sovenco_map_settings',
                    'description'   => sprintf( __( 'Find your Latitude, Longitude <a target="_blank" href="%1$s">Here</a>', 'sovenco-plus' ), 'http://www.mapcoordinates.net/en' ),
                )
            )
        );

        // Address
        $wp_customize->add_setting( 'sovenco_map_address',
            array(
                'sanitize_callback' => 'sovenco_sanitize_text',
                'default'           => __( '<strong>1 Infinite Loop Cupertino <br/> CA 95014  United States</strong>' , 'sovenco-plus' ),
            )
        );

        $wp_customize->add_control( new sovenco_Editor_Custom_Control(
            $wp_customize,
            'sovenco_map_address',
            array(
                'label' 		=> __('Address', 'sovenco-plus'),
                'section' 		=> 'sovenco_map_settings',
            )
        ));

        // Extra Info
        $wp_customize->add_setting( 'sovenco_map_html',
            array(
                'sanitize_callback' => 'sovenco_sanitize_text',
                'default'           => __('<p>Your address description goes here.</p>', 'sovenco-plus'),
            )
        );
        $wp_customize->add_control( new sovenco_Editor_Custom_Control(
            $wp_customize,
            'sovenco_map_html',
            array(
                'label' 		=> __('Extra Info', 'sovenco-plus'),
                'section' 		=> 'sovenco_map_settings',
                'description'   => __('The HTML code that display on info window when you click to marker', 'sovenco-plus'),
            )
        ));


        $wp_customize->add_setting(
            'sovenco_map_items_address',
            array(
                'default' => '',
                'sanitize_callback' => 'sovenco_sanitize_repeatable_data_field',
                'transport' => 'refresh', // refresh or postMessage
            ) );


        $wp_customize->add_control(
            new sovenco_Customize_Repeatable_Control(
                $wp_customize,
                'sovenco_map_items_address',
                array(
                    'label'     	=> esc_html__('Multiple Address', 'sovenco-plus'),
                    'description'   => '',
                    'section'       => 'sovenco_map_settings',
                    'live_title_id' => 'address', // apply for unput text and textarea only
                    'title_format'  => esc_html__('[live_title]', 'sovenco-plus'), // [live_title]
                    'max_item'      => 4, // Maximum item can add

                    'fields'    => array(
                        'address' => array(
                            'title' => esc_html__('Address', 'sovenco-plus'),
                            'type'  =>'text',
                            'desc'  => '',
                        ),
                        'lat' => array(
                            'title' => esc_html__('Latitude', 'sovenco-plus'),
                            'type'  =>'text',
                            'default' => '',
                        ),
                        'long' => array(
                            'title' => esc_html__('Longitude', 'sovenco-plus'),
                            'type'  =>'text',
                            'default' => '',
                        ),
                        'desc' => array(
                            'title' => esc_html__('Extra info', 'sovenco-plus'),
                            'type'  =>'textarea',
                            'default' => '',
                        ),

                        'maker' => array(
                            'title' => esc_html__('Marker', 'sovenco-plus'),
                            'type'  =>'media',
                            'default' => '',
                        ),

                    ),

                )
            )
        );


        //-------------------------




        // Color
        $wp_customize->add_setting( 'sovenco_map_color',
            array(
                'sanitize_callback' => 'sanitize_hex_color',
                'default'           => '',
            )
        );
        $wp_customize->add_control( new WP_Customize_Color_Control(
            $wp_customize,
            'sovenco_map_color',
            array(
                'label' 		=> __('Map Color', 'sovenco-plus'),
                'section' 		=> 'sovenco_map_settings',
                'description'   => '',
            )
        ));

        // Maker
        $wp_customize->add_setting( 'sovenco_map_maker',
            array(
                'sanitize_callback' => 'sovenco_sanitize_text',
                'default'           => sovenco_PLUS_URL.'assets/images/map-marker.png',
            )
        );
        $wp_customize->add_control( new WP_Customize_Image_Control(
            $wp_customize,
            'sovenco_map_maker',
            array(
                'label' 		=> __('Map Marker', 'sovenco-plus'),
                'section' 		=> 'sovenco_map_settings',
                'description'   => __('Size no larger than 80x80px', 'sovenco-plus'),
            )
        ));


        // Height
        $wp_customize->add_setting( 'sovenco_map_zoom',
            array(
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => '10',
            )
        );

        $wp_customize->add_control( 'sovenco_map_zoom',
            array(
                'label' 		=> __('Map Zoom', 'sovenco-plus'),
                'section' 		=> 'sovenco_map_settings',
                'description'   => __('Map Zoom, default 10', 'sovenco-plus'),
            )
        );

        // Height
        $wp_customize->add_setting( 'sovenco_map_height',
            array(
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => '',
            )
        );

        $wp_customize->add_control( 'sovenco_map_height',
            array(
                'label' 		=> __('Map Height', 'sovenco-plus'),
                'section' 		=> 'sovenco_map_settings',
                'description'   => '',
            )
        );

        // Scroll wheel
        $wp_customize->add_setting( 'sovenco_map_scrollwheel',
            array(
                'sanitize_callback' => 'sovenco_sanitize_checkbox',
                'default'           => '',
            )
        );
        $wp_customize->add_control( 'sovenco_map_scrollwheel',
            array(
                'type'        => 'checkbox',
                'label'       => __('Enable Scrollwheel', 'sovenco-plus'),
                'section'     => 'sovenco_map_settings',
                'description' => esc_html__('Check this box to enable enable mouse scroll wheel.', 'sovenco-plus'),
            )
        );



        // EN Add map


        /*------------------------------------------------------------------------*/
        /*  Section: Project
        /*------------------------------------------------------------------------*/
        $wp_customize->add_panel( 'sovenco_projects' ,
            array(
                'priority'        => 200,
                'title'           => __( 'Section: Projects', 'sovenco-plus' ),
                'description'     => '',
                'active_callback' => 'sovenco_showon_frontpage'
            )
        );

        $wp_customize->add_section( 'sovenco_projects_settings' ,
            array(
                'priority'    => 3,
                'title'       => __( 'Section Settings', 'sovenco-plus' ),
                'panel'       => 'sovenco_projects',
            )
        );

        // Project ID
        $wp_customize->add_setting( 'sovenco_projects_id',
            array(
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => 'projects',
            )
        );
        $wp_customize->add_control( 'sovenco_projects_id',
            array(
                'label' 		=> esc_html__('Section ID', 'sovenco-plus'),
                'section' 		=> 'sovenco_projects_settings',
                'description'   => '',
            )
        );

        // Project title
        $wp_customize->add_setting( 'sovenco_projects_title',
            array(
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => esc_html__( 'Highlight Projects', 'sovenco-plus' ),
            )
        );
        $wp_customize->add_control( 'sovenco_projects_title',
            array(
                'label' 		=> esc_html__('Section Title', 'sovenco-plus'),
                'section' 		=> 'sovenco_projects_settings',
                'description'   => '',
            )
        );

        // Project subtitle
        $wp_customize->add_setting( 'sovenco_projects_subtitle',
            array(
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => esc_html__( 'Some of our works', 'sovenco-plus' ),
            )
        );
        $wp_customize->add_control( 'sovenco_projects_subtitle',
            array(
                'label' 		=> esc_html__('Section subtitle', 'sovenco-plus'),
                'section' 		=> 'sovenco_projects_settings',
                'description'   => '',
            )
        );

        // Description
        $wp_customize->add_setting( 'sovenco_projects_desc',
            array(
                'sanitize_callback' => 'sovenco_sanitize_text',
                'default'           => '',
            )
        );
        $wp_customize->add_control( new sovenco_Editor_Custom_Control(
            $wp_customize,
            'sovenco_projects_desc',
            array(
                'label' 		=> esc_html__('Section Description', 'sovenco-plus'),
                'section' 		=> 'sovenco_projects_settings',
                'description'   => '',
            )
        ));

        // Number projects to show
        $wp_customize->add_setting( 'sovenco_projects_number',
            array(
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => '6',
            )
        );
        $wp_customize->add_control( 'sovenco_projects_number',
            array(
                'label' 		=> esc_html__('Number projects to show', 'sovenco-plus'),
                'section' 		=> 'sovenco_projects_settings',
                'description'   => '',
            )
        );

        // Project order by
        $wp_customize->add_setting( 'sovenco_projects_orderby',
            array(
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => 'ID',
            )
        );

        $wp_customize->add_control( 'sovenco_projects_orderby',
            array(
                'label' 		=> esc_html__('Order By', 'sovenco-plus'),
                'section' 		=> 'sovenco_projects_settings',
                'description'   => '',
                'type'          => 'select',
                'choices'       => array(
                    'ID' => __( 'ID', 'sovenco-plus' ),
                    'title' => __( 'Title', 'sovenco-plus' ),
                    'date' => __( 'Date', 'sovenco-plus' ),
                    'rand' => __( 'Random', 'sovenco-plus' ),
                ),
            )
        );

        // Project order
        $wp_customize->add_setting( 'sovenco_projects_order',
            array(
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => 'DESC',
            )
        );

        $wp_customize->add_control( 'sovenco_projects_order',
            array(
                'label' 		=> esc_html__('Order', 'sovenco-plus'),
                'section' 		=> 'sovenco_projects_settings',
                'description'   => '',
                'type'          => 'select',
                'choices'       => array(
                    'DESC' => __( 'Descending', 'sovenco-plus' ),
                    'ASC' => __( 'Ascending', 'sovenco-plus' ),
                ),
            )
        );

        // Project slug
        $wp_customize->add_setting( 'sovenco_project_slug',
            array(
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => 'portfolio',
            )
        );
        $wp_customize->add_control( 'sovenco_project_slug',
            array(
                'label' 		=> __('Project slug', 'sovenco-plus'),
                'section' 		=> 'sovenco_projects_settings',
                'description'   => __( 'If you change this option please go to Settings > Permalinks and refresh your permalink structure before your custom post type will show the correct structure.', 'sovenco-plus' ),
            )
        );

        // Ajax view projects
        $wp_customize->add_setting( 'sovenco_project_ajax',
            array(
                'sanitize_callback' => 'sovenco_sanitize_checkbox',
                'default'           => 0,
            )
        );
        $wp_customize->add_control( 'sovenco_project_ajax',
            array(
                'type'        => 'checkbox',
                'label'       => esc_html__('Use ajax for load project details', 'sovenco-plus'),
                'section'     => 'sovenco_projects_settings',
            )
        );


        // end project

        /*------------------------------------------------------------------------*/
        /*  Section: Pricing Table
        /*------------------------------------------------------------------------*/
        $wp_customize->add_panel( 'sovenco_pricing' ,
            array(
                'priority'        => 230,
                'title'           => __( 'Section: Pricing', 'sovenco-plus' ),
                'description'     => '',
                'active_callback' => 'sovenco_showon_frontpage'
            )
        );

        $wp_customize->add_section( 'sovenco_pricing_settings' ,
            array(
                'priority'    => 3,
                'title'       => __( 'Section Settings', 'sovenco-plus' ),
                'panel'       => 'sovenco_pricing',
            )
        );

        // Project ID
        $wp_customize->add_setting( 'sovenco_pricing_id',
            array(
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => 'projects',
            )
        );
        $wp_customize->add_control( 'sovenco_pricing_id',
            array(
                'label' 		=> __('Section ID', 'sovenco-plus'),
                'section' 		=> 'sovenco_pricing_settings',
                'description'   => '',
            )
        );

        // Project title
        $wp_customize->add_setting( 'sovenco_pricing_title',
            array(
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => __( 'Pricing Table', 'sovenco-plus' ),
            )
        );
        $wp_customize->add_control( 'sovenco_pricing_title',
            array(
                'label' 		=> __('Section Title', 'sovenco-plus'),
                'section' 		=> 'sovenco_pricing_settings',
                'description'   => '',
            )
        );

        // Project subtitle
        $wp_customize->add_setting( 'sovenco_pricing_subtitle',
            array(
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => __( 'Responsive pricing section', 'sovenco-plus' ),
            )
        );
        $wp_customize->add_control( 'sovenco_pricing_subtitle',
            array(
                'label' 		=> __('Some of our works', 'sovenco-plus'),
                'section' 		=> 'sovenco_pricing_settings',
                'description'   => '',
            )
        );

        // Description
        $wp_customize->add_setting( 'sovenco_pricing_desc',
            array(
                'sanitize_callback' => 'sovenco_sanitize_text',
                'default'           => '',
            )
        );
        $wp_customize->add_control( new sovenco_Editor_Custom_Control(
            $wp_customize,
            'sovenco_pricing_desc',
            array(
                'label' 		=> esc_html__('Section Description', 'sovenco-plus'),
                'section' 		=> 'sovenco_pricing_settings',
                'description'   => '',
            )
        ));



        // Section content
        $wp_customize->add_section( 'sovenco_pricing_content' ,
            array(
                'priority'    => 3,
                'title'       => __( 'Section Content', 'sovenco-plus' ),
                'panel'       => 'sovenco_pricing',
            )
        );
        $wp_customize->add_setting(
            'sovenco_pricing_plans',
            array(
                'default' => json_encode(
                    array(
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

                    )
                ),
                'sanitize_callback' => 'sovenco_sanitize_repeatable_data_field',
                'transport' => 'refresh', // refresh or postMessage
            ) );


            $wp_customize->add_control(
                new sovenco_Customize_Repeatable_Control(
                    $wp_customize,
                    'sovenco_pricing_plans',
                    array(
                        'label'     	=> esc_html__('Pricing Plans', 'sovenco-plus'),
                        'description'   => '',
                        'section'       => 'sovenco_pricing_content',
                        'live_title_id' => 'title', // apply for unput text and textarea only
                        'title_format'  => esc_html__('[live_title]', 'sovenco-plus'), // [live_title]
                        'max_item'      => 4, // Maximum item can add

                        'fields'    => array(
                            'title' => array(
                                'title' => esc_html__('Title', 'sovenco-plus'),
                                'type'  =>'text',
                                'desc'  => '',
                                'default' => esc_html__( 'Your service title', 'sovenco-plus' ),
                            ),
                            'price' => array(
                                'title' => esc_html__('Price', 'sovenco-plus'),
                                'type'  =>'text',
                                'default' => esc_html__( '99', 'sovenco-plus' ),
                            ),
                            'code' => array(
                                'title' => esc_html__('Currency code', 'sovenco-plus'),
                                'type'  =>'text',
                                'default' => esc_html__( '$', 'sovenco-plus' ),
                            ),
                            'subtitle' => array(
                                'title' => esc_html__('Subtitle', 'sovenco-plus'),
                                'type'  =>'text',
                                'desc'  => '',
                                'default' => esc_html__( 'Lorem ipsum dolor sit amet, consectetuer adipiscing elit', 'sovenco-plus' ),
                            ),
                            'content'  => array(
                                'title' => esc_html__('Option list', 'sovenco-plus'),
                                'desc'  => esc_html__('Earch option per line', 'sovenco-plus'),
                                'type'  =>'textarea',
                                'default' => esc_html__( "Option 1\n Option 2\n Option 3\n Option 4", 'sovenco-plus' ),
                            ),
                            'label' => array(
                                'title' => esc_html__('Button label', 'sovenco-plus'),
                                'type'  =>'text',
                                'desc'  => '',
                                'default' =>  esc_html__('Choose Plan', 'sovenco-plus'),
                            ),
                            'link' => array(
                                'title' => esc_html__('Button Link', 'sovenco-plus'),
                                'type'  =>'text',
                                'desc'  => '',
                                'default' => '#',
                            ),
                            'button'  => array(
                                'title' => esc_html__('Button style', 'sovenco-plus'),
                                'type'  =>'select',
                                'options' => array(
                                    'btn-theme-primary' => esc_html__('Theme default', 'sovenco-plus'),
                                    'btn-default' => esc_html__('Button', 'sovenco-plus'),
                                    'btn-primary' => esc_html__('Primary', 'sovenco-plus'),
                                    'btn-success' => esc_html__('Success', 'sovenco-plus'),
                                    'btn-info' => esc_html__('Info', 'sovenco-plus'),
                                    'btn-warning' => esc_html__('Warning', 'sovenco-plus'),
                                    'btn-danger' => esc_html__('Danger', 'sovenco-plus'),
                                )
                            ),
                        ),

                    )
                )
            );
            // end pricing

        /*------------------------------------------------------------------------*/
        /*  Section: cta
        /*------------------------------------------------------------------------*/

        $wp_customize->add_panel( 'sovenco_cta_panel' ,
            array(
                'priority'        => 240,
                'title'           => __( 'Section: Call to Action', 'sovenco-plus' ),
                'description'     => '',
                'active_callback' => 'sovenco_showon_frontpage'
            )
        );

        $wp_customize->add_section( 'sovenco_cta_settings' ,
            array(
                'priority'    => 3,
                'title'       => __( 'Section Settings', 'sovenco-plus' ),
                'panel'       => 'sovenco_cta_panel',
            )
        );


        // Section ID
        $wp_customize->add_setting( 'sovenco_cta_id',
            array(
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => 'section-cta',
            )
        );
        $wp_customize->add_control( 'sovenco_cta_id',
            array(
                'label' 		=> __('Section ID', 'sovenco-plus'),
                'section' 		=> 'sovenco_cta_settings',
            )
        );

       // Title
       $wp_customize->add_setting( 'sovenco_cta_title',
           array(
               'sanitize_callback' => 'sovenco_sanitize_text',
               'default'           => __( 'Use these ribbons to display calls to action mid-page.' , 'sovenco-plus' ),
           )
       );
       $wp_customize->add_control( 'sovenco_cta_title',
           array(
               'label' 		=> __('Title', 'sovenco-plus'),
               'section' 		=> 'sovenco_cta_settings',
           )
       );

       // Button label
       $wp_customize->add_setting( 'sovenco_cta_btn_label',
           array(
               'sanitize_callback' => 'sovenco_sanitize_text',
               'default'           => __( 'Button Text' , 'sovenco-plus' ),
           )
       );
       $wp_customize->add_control( 'sovenco_cta_btn_label',
           array(
               'label' 		=> __('Button Text', 'sovenco-plus'),
               'section' 		=> 'sovenco_cta_settings',
           )
       );

       // Button link
       $wp_customize->add_setting( 'sovenco_cta_btn_link',
           array(
               'sanitize_callback' => 'sovenco_sanitize_text',
               'default'           => '',
           )
       );
       $wp_customize->add_control( 'sovenco_cta_btn_link',
           array(
               'label' 		=> __('Button Link', 'sovenco-plus'),
               'section' 		=> 'sovenco_cta_settings',
           )
       );

        // Button link style
        $wp_customize->add_setting( 'sovenco_cta_btn_link_style',
            array(
                'sanitize_callback' => 'sovenco_sanitize_text',
                'default'           => 'theme-primary',
            )
        );
        $wp_customize->add_control( 'sovenco_cta_btn_link_style',
            array(
                'label' 		=> __('Button Link Style', 'sovenco-plus'),
                'section' 		=> 'sovenco_cta_settings',
                'type'          => 'select',
                'choices'       => array(

                    'theme-primary' => esc_html__( 'Theme default', 'sovenco-plus' ),
                    'btn-primary' => esc_html__( 'Primary', 'sovenco-plus' ),
                    'btn-secondary' => esc_html__( 'Secondary', 'sovenco-plus' ),
                    'btn-success' => esc_html__( 'Success', 'sovenco-plus' ),
                    'btn-info' => esc_html__( 'Info', 'sovenco-plus' ),
                    'btn-warning' => esc_html__( 'Warning', 'sovenco-plus' ),
                    'btn-danger' => esc_html__( 'Danger', 'sovenco-plus' ),

                    'btn-outline-primary' => esc_html__( 'Outline Primary', 'sovenco-plus' ),
                    'btn-outline-secondary' => esc_html__( 'Outline Secondary', 'sovenco-plus' ),
                    'btn-outline-success' => esc_html__( 'Outline Success', 'sovenco-plus' ),
                    'btn-outline-info' => esc_html__( 'Outline Info', 'sovenco-plus' ),
                    'btn-outline-warning' => esc_html__( 'Outline Warning', 'sovenco-plus' ),
                    'btn-outline-danger' => esc_html__( 'Outline Danger', 'sovenco-plus' ),

                )

            )
        );

       // EN Add cta


        /*------------------------------------------------------------------------*/
        /*  Section: Clients
        /*------------------------------------------------------------------------*/

        $wp_customize->add_panel( 'sovenco_clients_panel' ,
            array(
                'priority'        => 140,
                'title'           => __( 'Section: Clients', 'sovenco-plus' ),
                'description'     => '',
                'active_callback' => 'sovenco_showon_frontpage'
            )
        );

        $wp_customize->add_section( 'sovenco_clients_settings' ,
            array(
                'priority'    => 3,
                'title'       => __( 'Section Settings', 'sovenco-plus' ),
                'panel'       => 'sovenco_clients_panel',
            )
        );


        // Section ID
        $wp_customize->add_setting( 'sovenco_clients_id',
            array(
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => 'clients',
            )
        );
        $wp_customize->add_control( 'sovenco_clients_id',
            array(
                'label' 		=> __('Section ID', 'sovenco-plus'),
                'section' 		=> 'sovenco_clients_settings',
            )
        );

        // Title
        $wp_customize->add_setting( 'sovenco_clients_title',
            array(
                'sanitize_callback' => 'sovenco_sanitize_text',
                'default'           => '',
            )
        );
        $wp_customize->add_control( 'sovenco_clients_title',
            array(
                'label' 		=> __('Title', 'sovenco-plus'),
                'section' 		=> 'sovenco_clients_settings',
            )
        );


        // clients subtitle
        $wp_customize->add_setting( 'sovenco_clients_subtitle',
            array(
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => __( 'Have been featured on', 'sovenco-plus' ),
            )
        );
        $wp_customize->add_control( 'sovenco_clients_subtitle',
            array(
                'label' 		=> __('Some of our works', 'sovenco-plus'),
                'section' 		=> 'sovenco_clients_settings',
                'description'   => '',
            )
        );

        // Services layout
        $wp_customize->add_setting( 'sovenco_clients_layout',
            array(
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => 5,
            )
        );

        $wp_customize->add_control( 'sovenco_clients_layout',
            array(
                'label' 		=> esc_html__('Clients Layout Setting', 'sovenco-plus'),
                'section' 		=> 'sovenco_clients_settings',
                'description'   => '',
                'type'          => 'select',
                'choices'       => array(
                    '2' => esc_html__( '2 Columns', 'sovenco-plus' ),
                    '3' => esc_html__( '3 Columns', 'sovenco-plus' ),
                    '4' => esc_html__( '4 Columns', 'sovenco-plus' ),
                    '5' => esc_html__( '5 Columns', 'sovenco-plus' ),
                    '6' => esc_html__( '6 Columns', 'sovenco-plus' ),
                ),
            )
        );

        // Description
        $wp_customize->add_setting( 'sovenco_clients_desc',
            array(
                'sanitize_callback' => 'sovenco_sanitize_text',
                'default'           => '',
            )
        );
        $wp_customize->add_control( new sovenco_Editor_Custom_Control(
            $wp_customize,
            'sovenco_clients_desc',
            array(
                'label' 		=> esc_html__('Section Description', 'sovenco-plus'),
                'section' 		=> 'sovenco_clients_settings',
                'description'   => '',
            )
        ));


        // Section content
        $wp_customize->add_section( 'sovenco_clients_content' ,
            array(
                'priority'    => 3,
                'title'       => __( 'Section Content', 'sovenco-plus' ),
                'panel'       => 'sovenco_clients_panel',
            )
        );
        $wp_customize->add_setting(
            'sovenco_clients',
            array(
                'default' => json_encode(
                    array(
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

                    )
                ),
                'sanitize_callback' => 'sovenco_sanitize_repeatable_data_field',
                'transport' => 'refresh', // refresh or postMessage
            ) );


        $wp_customize->add_control(
            new sovenco_Customize_Repeatable_Control(
                $wp_customize,
                'sovenco_clients',
                array(
                    'label'     	=> esc_html__('Clients', 'sovenco-plus'),
                    'description'   => '',
                    'section'       => 'sovenco_clients_content',
                    'live_title_id' => 'title', // apply for unput text and textarea only
                    'title_format'  => esc_html__('[live_title]', 'sovenco-plus'), // [live_title]
                    'max_item'      => 4, // Maximum item can add

                    'fields'    => array(
                        'title' => array(
                            'title' => esc_html__('Client name', 'sovenco-plus'),
                            'type'  =>'text',
                            'desc'  => '',
                            'default' => esc_html__( 'My Client', 'sovenco-plus' ),
                        ),
                        'image' => array(
                            'title' => esc_html__('Image', 'sovenco-plus'),
                            'type'  =>'media',
                            'default' => array(
                                'id'=> '',
                                'url'=> sovenco_PLUS_URL.'assets/images/client_logo_1.png',
                            ),
                        ),
                        'link' => array(
                            'title' => esc_html__('link', 'sovenco-plus'),
                            'type'  =>'text',
                            'default' => '',
                        ),
                    ),

                )
            )
        );
        // End Clients

        // Gallery

        // Source facebook settings
        $wp_customize->add_setting( 'sovenco_gallery_source_facebook',
            array(
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => '',
            )
        );
        $wp_customize->add_control( 'sovenco_gallery_source_facebook',
            array(
                'label'     	=> esc_html__('Facebook Fan Page Album', 'sovenco'),
                'priority'      => 15,
                'section' 		=> 'sovenco_gallery_content',
                'description'   => esc_html__('Enter Facebook fan page album ID or album URL here. Your album should publish to load data.', 'sovenco'),
            )
        );

        // Source flickr API settings
        $wp_customize->add_setting( 'sovenco_gallery_api_facebook',
            array(
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => '',
            )
        );
        $wp_customize->add_control( 'sovenco_gallery_api_facebook',
            array(
                'label'     	=> esc_html__('Facebook API', 'sovenco'),
                'section' 		=> 'sovenco_gallery_content',
                'priority'      => 20,
                'description'   => sprintf( esc_html__('Paste your Facebook Token here, example: {App_ID}|{App_Secret}. Click %1$s to create an app.', 'sovenco'), '<a target="_blank" href="https://developers.facebook.com/apps/">'.esc_html( 'here', 'sovenco' ).'</a>' ),
            )
        );

        // Source flickr settings
        $wp_customize->add_setting( 'sovenco_gallery_source_flickr',
            array(
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => '',
            )
        );
        $wp_customize->add_control( 'sovenco_gallery_source_flickr',
            array(
                'label'     	=> esc_html__('Flickr Username or ID', 'sovenco'),
                'section' 		=> 'sovenco_gallery_content',
                'priority'      => 25,
                'description'   => esc_html__('Flickr Username or ID here, Required Flickr API.', 'sovenco'),
            )
        );

        // Source flickr API settings
        $wp_customize->add_setting( 'sovenco_gallery_api_flickr',
            array(
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => '',
            )
        );
        $wp_customize->add_control( 'sovenco_gallery_api_flickr',
            array(
                'label'     	=> esc_html__('Flickr API key', 'sovenco'),
                'section' 		=> 'sovenco_gallery_content',
                'priority'      => 30,
                'description'   => esc_html__('Paste your Flickr API key here.', 'sovenco'),
            )
        );


        // Source instagram settings
        $wp_customize->add_setting( 'sovenco_gallery_source_instagram',
            array(
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => '',
            )
        );
        $wp_customize->add_control( 'sovenco_gallery_source_instagram',
            array(
                'label'     	=> esc_html__('Instagram Username', 'sovenco'),
                'section' 		=> 'sovenco_gallery_content',
                'priority'      => 35,
                'description'   => esc_html__('Enter your Instagram username here.', 'sovenco'),
            )
        );

        // End Gallery



    }

    /**
     * Unlimited repeatable items
     *
     * @param $number
     * @return int
     */
    function unlimited_repeatable_items( $number ){
        return 99999;
    }

    /**
     * Get section settings data
     *
     * @return array
     */
    function get_sections_settings(){
        if ( ! empty( $this->section_settings ) ) {
            return $this->section_settings;
        }
        $sections = get_theme_mod( 'sovenco_section_order_styling', '');
        if ( is_string( $sections ) ) {
            $sections = json_decode( $sections, true );
        }

        if ( ! is_array( $sections ) ) {
            $sections = array();
        }

        if ( empty( $sections ) || ! is_array( $sections ) ) {
            $sections = $this->get_default_sections_settings();
        }

        $this->section_settings = array();

        foreach( $sections as $k => $v ) {
            if ( ! $v['section_id'] ) {
                $v['section_id'] = sanitize_title( $v['title'] );
            }

            if ( ! $v['section_id'] ) {
                $v['section_id'] = uniqid('section-');
            }

            if ( $v['section_id'] != '' && ( ! isset( $v['add_buy'] ) ||  $v['add_buy'] != 'click' ) ) {
                $this->section_settings[  $v['section_id'] ] =  $v;
            } else {
                $this->section_settings[  ] =  $v;
            }
        }

        return $this->section_settings ;
    }

    /**
     * Get media from a variable
     *
     * @param array $media
     * @return false|string
     */
    static function get_media_url( $media = array() ){
        $media = wp_parse_args( $media, array('url' => '', 'id' => '') );
        $url = '';
        if ( $media['id'] != '' ) {
            $url = wp_get_attachment_url($media['id']);
        }
        if ( $url == '' && $media['url'] != '') {
            $url = $media['url'];
        }
        return $url;
    }

    /**
     * Get media ID
     *
     * @param array $media
     * @return int
     */
    static function get_media_id( $media = array() ){
        if ( is_numeric( $media ) ) {
            return absint( $media );
        }
        $media = wp_parse_args( $media, array('url' => '', 'id' => '') );
        if ( $media['id'] != '' ) {
            return absint( $media['id'] );
        }
        return 0;
    }

    function hex_to_rgb( $colour ) {
        if ( $colour[0] == '#' ) {
            $colour = substr( $colour, 1 );
        }
        if ( strlen( $colour ) == 6 ) {
            list( $r, $g, $b ) = array( $colour[0] . $colour[1], $colour[2] . $colour[3], $colour[4] . $colour[5] );
        } elseif ( strlen( $colour ) == 3 ) {
            list( $r, $g, $b ) = array( $colour[0] . $colour[0], $colour[1] . $colour[1], $colour[2] . $colour[2] );
        } else {
            return false;
        }
        $r = hexdec( $r );
        $g = hexdec( $g );
        $b = hexdec( $b );
        return array( 'r' => $r, 'g' => $g, 'b' => $b );
    }

    function check_hex( $color ){

        $color = ltrim( $color, '#' );
        if ( '' === $color ){
            return '';
        }

        // 3 or 6 hex digits, or the empty string.
        if ( preg_match('|^#([A-Fa-f0-9]{3}){1,2}$|', '#' . $color ) ) {
            return '#' . $color;
        }

        return '';
    }

    function hex_to_rgba( $hex_color, $alpha = 1 ) {
        if ( $this->is_rgb( $hex_color ) ) {
            return $hex_color;
        }
        if ( $hex_color = $this->check_hex( $hex_color ) ) {
            $rgb = $this->hex_to_rgb( $hex_color );
            $rgb['a' ] = $alpha;
            return 'rgba('.join(',', $rgb ).')';
        } else {
            return '';
        }
    }

    function is_rgb( $color ){
        return strpos( trim( $color ), 'rgb' ) !== false ?  true : false;
    }

    /**
     * Check to load css, js, and more...
     */
    function int_setup() {
        if (  empty( $this->section_settings ) ) {
            $this->get_sections_settings();
        }

        $style = array();


        foreach ( $this->section_settings as $section ) {
            $section = wp_parse_args( $section, array(
                'section_id' => '',
                'show_section' => '',
                'bg_color' => '',
                'bg_type' => '',
                'bg_opacity' => '',
                'bg_opacity_color' => '',
                'bg_image' => '',
                'bg_video' => '',
                'bg_video_webm' => '',
                'bg_video_ogv' => '',
                'enable_parallax' => '',
                'padding_top' => '',
                'padding_bottom' => '',
            ) );

            if ( $section['section_id'] == 'map' && $section['show_section'] ) {
                wp_enqueue_script( 'jquery' );
                $key = '';
                if ( get_theme_mod( 'sovenco_map_enable_api' ) ) {
                    $key = get_theme_mod( 'sovenco_map_api_key' );
                }
                if ( ! $key ) {
                    $key = 'AIzaSyASkFdBVeZHxvpMVIOSfk2hGiIzjOzQeFY'; // default key
                }
                $map_api_uri = 'https://maps.googleapis.com/maps/api/js?key='.$key;
                wp_enqueue_script( 'gmap', apply_filters( 'google_map_api_url', $map_api_uri ), array( 'jquery'), '', true  );
            }

            if ( $section['padding_top'] != '' ) {
                if ( strpos( $section['padding_top'], '%' ) !== false ) {
                    $section['padding_top'] = intval( $section['padding_top'] ).'%';
                } else {
                    $section['padding_top'] = intval( $section['padding_top'] ).'px';
                }
                $style[ $section['section_id'] ][] = "padding-top: {$section['padding_top']};";
            }

            if ( $section['padding_bottom'] != '' ) {
                if ( strpos( $section['padding_bottom'], '%' ) !== false ) {
                    $section['padding_bottom'] = intval( $section['padding_bottom'] ).'%';
                } else {
                    $section['padding_bottom'] = intval( $section['padding_bottom'] ).'px';
                }

                $style[ $section['section_id'] ][] = "padding-bottom: {$section['padding_bottom']};";

            }

            switch ($section['bg_type']) {

                case 'video':

                   // $video_url =  $this->get_media_url( $section['bg_video'] );
                   // $video_webm_url =  $this->get_media_url( $section['bg_video_webm'] );
                    //$video_ogv_url =  $this->get_media_url( $section['bg_video_ogv'] );
                   // $is_video = ( $video_url || $video_webm_url ||  $video_ogv_url ) ;
                    if ( $this->is_rgb( $section['bg_opacity_color'] ) ) {
                        $bg_opacity_color = $section['bg_opacity_color'];
                    } else {
                        $bg_opacity_color = $this->hex_to_rgba( $section['bg_opacity_color'] , .4 );
                    }
                    $this->custom_css .= " .section-{$section['section_id']}::before{background-color: {$bg_opacity_color}; } \n ";

                    break;

                case 'image':

                    if ( $this->is_rgb( $section['bg_opacity_color'] ) ) {
                        $bg_opacity_color = $section['bg_opacity_color'];
                    } else {
                        $bg_opacity_color = $this->hex_to_rgba( $section['bg_opacity_color'] , .4 );
                    }

                    $image = $this->get_media_url($section['bg_image']);

                    if ( $image && ! $bg_opacity_color ) {
                        if ( $bg_opacity_color ) {
                            $style[$section['section_id']]['bg'] = "background-color: #{$bg_opacity_color};";
                        }
                        // check background image and not parallax enable
                        if ($section['enable_parallax'] != 1 && $image) {
                            $style[$section['section_id']][] = "background-image: url(\"{$image}\");";
                        }
                    } else if ( $image && $bg_opacity_color ) {
                        if ( $image ) {
                            $this->custom_css .=".bgimage-{$section['section_id']} {background-image: url(\"{$image}\");}";
                        }

                        if ( $bg_opacity_color ) {
                            $style[$section['section_id']][] = "background-color: {$bg_opacity_color}";
                        }

                    }

                    if ( $bg_opacity_color ) {
                        if ($section['enable_parallax'] == 1) {
                            $this->custom_css .= " #parallax-{$section['section_id']} .parallax-bg::before{background-color: {$bg_opacity_color}; } \n ";
                        }
                    }

                    /*
                    if ( $image ) {
                        if ($section['enable_parallax'] == 1) {
                            $this->custom_css .= " #parallax-{$section['section_id']} .parallax-bg::after {background-image: url(\"{$image}\");}";
                        }
                    }
                    */

                    break;

                default: // Background color

                    if ( $this->is_rgb( $section['bg_color'] ) ) {
                        $bg_color = $section['bg_color'];
                    } else {
                        $bg_color = $this->hex_to_rgba( $section['bg_color'] , 1 );
                    }
                    if( $bg_color ) {
                        $style[$section['section_id']]['bg'] = "background-color: {$bg_color};";
                    }

            }

        }

        foreach ( $style as $k => $code ) {
            if ( ! empty( $code ) ) {
                $this->custom_css .= " .section-{$k}{ ".join( ' ', $code )." } \n ";
            }
        }
    }

    /**
     * Load CSS, JS for frontend
     */
    function frontend_scripts(){

        wp_enqueue_style( 'sovenco-style' );
        wp_register_style( 'sovenco-plus-style', sovenco_PLUS_URL.'sovenco-plus.css', array( 'sovenco-style' ), sovenco_PLUS_VERSION );
        wp_enqueue_style( 'sovenco-plus-style' );

        /**
         * Plugin style
         */
        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( 'sovenco-plus', sovenco_PLUS_URL.'assets/js/sovenco-plus.js', array( 'jquery', 'sovenco-theme' ), sovenco_PLUS_VERSION, true );
        wp_localize_script( 'jquery' , 'sovenco_Plus', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'browser_warning'=> esc_html__( ' Your browser does not support the video tag. I suggest you upgrade your browser.', 'sovenco-plus' )
        ) );
    }

    /**
     * Print CSS in header tag
     */
    function custom_css(){
        if ( $this->custom_css ) {
            wp_add_inline_style( 'sovenco-style', $this->custom_css  );
        }
    }

    /**
     * Change onepage section classes
     *
     * @param $class
     * @param $section_id
     * @return array|string
     */
    function filter_section_class( $class, $section_id ){

        if (  empty( $this->section_settings ) ) {
            $this->get_sections_settings();
        }

        if ( isset( $this->section_settings[ $section_id ] ) ) {
            $class = explode( " ", $class );
            if ( isset( $this->section_settings[ $section_id ]['section_inverse'] ) && $this->section_settings[ $section_id ]['section_inverse'] ) {
                if ( ! in_array( 'section-inverse', $class ) ) {
                    $class[] =  'section-inverse';
                }
            } else {
                if( ( $key = array_search( 'section-inverse' , $class ) ) !== false ) {
                    unset( $class[ $key ] );
                }
            }
            $class  = join( ' ', $class );
        }

        return $class;
    }

    function load_section_part( $section ){
        $file_name = 'section-parts/section-' . $section['section_id'] . ".php";
        if ( ! $this->locate_template( $file_name, true, false ) ) {
            $section =  wp_parse_args( $section, array(
                'section_id' => '',
                'subtitle' => '',
                'title' => '',
                'content' => '',
                'hide_title' => '',
            ) );
            ?>
            <section id="<?php if ( $section['section_id'] != '' ) echo esc_attr( $section['section_id'] ); ?>" <?php do_action( 'sovenco_section_atts', $section['section_id'] ); ?> class="<?php echo esc_attr( apply_filters( 'sovenco_section_class', 'section-'.$section['section_id'].' onepage-section section-meta section-padding', $section['section_id'] ) ); ?>">
                <?php do_action( 'sovenco_section_before_inner', $section['section_id'] ); ?>
                <div class="container">
                    <?php if ( $section['subtitle'] || ( ! $section['hide_title'] && $section['title'] ) ) { ?>
                        <?php if ( $section['title'] || $section['subtitle']  || $section['desc']  ) { ?>
                        <div class="section-title-area">
                            <?php if ( $section['subtitle'] != '' ) echo '<h5 class="section-subtitle">' . esc_html( $section['subtitle'] ) . '</h5>'; ?>
                            <?php if ( ! $section['hide_title'] ) { ?>
                            <?php if ( $section['title'] ) echo '<h2 class="section-title">' . esc_html( $section['title'] ) . '</h2>'; ?>
                            <?php } ?>
                            <?php if ( $section['desc'] ) {
                                echo '<div class="section-desc">' . apply_filters( 'the_content', wp_kses_post( $section['desc'] ) ) . '</div>';
                            } ?>
                        </div>
                        <?php } ?>
                    <?php } ?>
                    <div class="section-content-area custom-section-content"><?php echo apply_filters( 'the_content', wp_kses_post( $section['content'] ) ); ?></div>
                </div>
                <?php do_action( 'sovenco_section_after_inner', $section['section_id'] ); ?>
            </section>
            <?php

        }

    }

    /**
     * Load section parts
     *
     * @param $sections
     */
    function load_section_parts(  ){

        $sections = $this->get_sections_settings();

        /**
         * Section: Hero
         */

        /**
         * Hook before section
         */
        do_action('sovenco_before_section_hero' );
        do_action( 'sovenco_before_section_part', 'hero' );

        $this->locate_template('section-parts/section-hero.php', true, false );

        /**
         * Hook after section
         */
        do_action('sovenco_after_section_part', 'hero' );
        do_action('sovenco_after_section_hero' );


        if ( is_array( $sections ) ) {
            add_filter( 'sovenco_section_class', array( $this, 'filter_section_class' ), 15, 2 );
            foreach ( $sections as $index => $section ) {
                //$GLOBALS['current_section'] = $section;
                $section = wp_parse_args( $section,
                    array(
                        'section_id' => '',
                        'show_section' => '',
                        'add_buy' => '',
                        'content' => '',
                        'bg_color' => '',
                        'bg_type' => '',
                        'bg_opacity' => '',
                        'bg_image' => '',
                        'bg_video_webm' => '',
                        'bg_video_ogv' => '',
                        'enable_parallax' => '',
                    )
                );

                // make sure we not disable from theme template
                add_filter( 'theme_mod_sovenco_'.$section['section_id'].'_disable', '__return_false', 99 );
                // If disabled section the code this line below will handle this
                if ( $section['show_section'] ) {
                    if ( $section['section_id'] != '' ) {
                        do_action('sovenco_before_section_'.$section['section_id'] );
                        do_action('sovenco_before_section_part', $section['section_id'] );

                        switch ( $section['bg_type'] ) {

                            case 'video':


                                $video_url =  $this->get_media_url( $section['bg_video'] );
                                $video_webm_url =  $this->get_media_url( $section['bg_video_webm'] );
                                $video_ogv_url =  $this->get_media_url( $section['bg_video_ogv'] );
                                $image = $this->get_media_url( $section['bg_image'] );

                                if (  $video_url || $video_webm_url || $video_ogv_url ) {
                                    ?>
                                    <div class="video-section"
                                    data-mp4="<?php echo esc_url( $video_url ); ?>"
                                    data-webm="<?php echo esc_url( $video_webm_url ); ?>"
                                    data-ogv="<?php echo esc_url( $video_ogv_url ); ?>"
                                    data-bg="<?php echo esc_attr( $image ); ?>">
                                <?php
                                }

                                $this->load_section_part( $section );

                                if ( $video_url || $video_webm_url || $video_ogv_url ) {
                                    echo '</div>'; // End video-section
                                }

                                break;
                            case 'image':


                                $image = $this->get_media_url( $section['bg_image'] );
                                $alpha = $this->hex_to_rgba( $section['bg_opacity_color'], .3 );
                                if ( $section['enable_parallax'] == 1 ) {
                                    echo '<div id="parallax-'.esc_attr( $section['section_id'] ).'" class="section-parallax">';
                                    echo ' <div class="parallax-bg" data-stellar-ratio="0.1" style="background-image: url('.esc_url( $image ).');"></div>';
                                   // echo ' <div class="parallax-bg" data-stellar-ratio="0.1" style="background-image: url('.esc_url( $image ).');"></div>';
                                } else if ( $image && $alpha ) { // image bg
                                    echo '<div id="bgimage-'.esc_attr( $section['section_id'] ).'" class="bgimage-alpha bgimage-'.esc_attr( $section['section_id'] ).'">';
                                }

                                $this->load_section_part( $section );

                                if ( $section['enable_parallax'] == 1 ) {
                                    echo '</div>'; // End parallax
                                } else if ( $image && $alpha ) {
                                    echo '</div>'; // // image bg
                                }


                                break;
                            default:

                                $this->load_section_part( $section );

                        }


                        do_action('sovenco_after_section_part', $section['section_id']);
                        do_action('sovenco_after_section_'.$section['section_id'] );
                    }
                }
            }
            remove_filter( 'sovenco_section_class', array( $this, 'filter_section_class' ), 15, 2 );
        }
    }

    /**
    * Retrieve the name of the highest priority template file that exists.
    *
    * Searches in the STYLESHEETPATH before TEMPLATEPATH so that themes which
    * inherit from a parent theme can just overload one file.
    *
    * @since 2.7.0
    *
    * @param string|array $template_names Template file(s) to search for, in order.
    * @param bool         $load           If true the template file will be loaded if it is found.
    * @param bool         $require_once   Whether to require_once or require. Default true. Has no effect if $load is false.
    * @return string The template filename if one is located.
    */
    function locate_template( $template_names, $load = false, $require_once = true ) {
        $located = '';

        $is_child =  STYLESHEETPATH != TEMPLATEPATH ;

        foreach ( (array) $template_names as $template_name ) {
            if (  !$template_name )
                continue;

            if ( $is_child && file_exists( STYLESHEETPATH . '/' . $template_name ) ) {  // Child them
                $located = STYLESHEETPATH . '/' . $template_name;
                break;

            } elseif ( file_exists( sovenco_PLUS_PATH  . $template_name ) ) { // Check part in the plugin
                $located = sovenco_PLUS_PATH . $template_name;
                break;
            } elseif ( file_exists(TEMPLATEPATH . '/' . $template_name) ) { // current_theme
                $located = TEMPLATEPATH . '/' . $template_name;
                break;
            }
        }

        if ( $load && '' != $located ) {
            load_template( $located, $require_once );
        }
        return $located;
    }
}

/**
 * call plugin
 */
function sovenco_plus_setup(){
    new sovenco_PLus();
}
add_action( 'plugins_loaded', 'sovenco_plus_setup' );
