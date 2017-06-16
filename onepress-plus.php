<?php
/*
Plugin Name: OnePress Plus
Plugin URI: http://www.famethemes.com/
Description: The OnePress Plus plugin adds powerful premium features to OnePress theme.
Author: famethemes
Author URI:  http://www.famethemes.com/
Version: 1.2.5
Text Domain: onepress-plus
License: GPL version 2 or later - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/

define( 'ONEPRESS_PLUS_URL',  trailingslashit( plugins_url('', __FILE__) ));
define( 'ONEPRESS_PLUS_PATH', trailingslashit( plugin_dir_path( __FILE__) ) );


/**
 * Class OnePress_PLus
 */
class OnePress_PLus {


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

        load_plugin_textdomain( 'onepress-plus', false, ONEPRESS_PLUS_PATH . 'languages' );

        if ( ! function_exists( 'get_plugin_data' ) ) {
            require_once ABSPATH .'wp-admin/includes/plugin.php';
        }
        $plugin_data = get_plugin_data( __FILE__ );
        define( 'ONEPRESS_PLUS_VERSION', $plugin_data['Version'] );

        add_action( 'onepress_frontpage_section_parts', array( $this, 'load_section_parts' ) );
        add_filter( 'onepress_reepeatable_max_item', array( $this, 'unlimited_repeatable_items' ) );
        add_action( 'onepress_customize_after_register', array( $this, 'plugin_customize' ), 40 );
        add_action( 'wp', array( $this, 'int_setup' ) );

        add_action( 'wp_enqueue_scripts',  array( $this, 'custom_css' ) , 150 );
        add_action( 'wp_enqueue_scripts', array( $this, 'frontend_scripts' ), 60 );

        require_once ONEPRESS_PLUS_PATH.'inc/post-type.php';
        require_once ONEPRESS_PLUS_PATH.'inc/template-tags.php';
        require_once ONEPRESS_PLUS_PATH.'inc/typography/helper.php';
        require_once ONEPRESS_PLUS_PATH.'inc/typography/auto-apply.php';
        require_once ONEPRESS_PLUS_PATH.'inc/auto-update/auto-update.php';
        require_once ONEPRESS_PLUS_PATH.'inc/ajax.php';
        /**
         * @todo Include custom template file
         */
        add_filter( 'template_include', array( $this, 'template_include' ) );

        /**
         * @todo add selective refresh
         */
        add_filter( 'onepress_customizer_partials_selective_refresh_keys', array( $this, 'selective_refresh' ) );

        // hook to import data
        add_action( 'ft_demo_import_current_item', array( $this, 'auto_import_id' ), 45 );

        add_action( 'wp', array( $this, 'wp' ) );
    }

    function wp(){
        $gallery_mod_name = 'onepress_gallery_disable';
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
        return 'onepress-plus';
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
                    'onepress_clients',
                    'onepress_clients_title',
                    'onepress_clients_subtitle',
                    'onepress_clients_layout',
                    'onepress_clients_desc',
                ),
            ),

            // + section cta
            array(
                'id' => 'cta',
                'selector' => '.section-cta',
                'settings' => array(
                    'onepress_cta_title',
                    'onepress_cta_btn_label',
                    'onepress_cta_btn_link',
                    'onepress_cta_btn_link_style',
                ),
            ),

            // + section pricing
            array(
                'id' => 'pricing',
                'selector' => '.section-pricing',
                'settings' => array(
                    'onepress_pricing_plans',
                    'onepress_pricing_title',
                    'onepress_pricing_subtitle',
                    'onepress_pricing_desc',
                ),
            ),
            // + section projects
            array(
                'id' => 'projects',
                'selector' => '.section-projects',
                'settings' => array(
                    'onepress_projects_title',
                    'onepress_projects_subtitle',
                    'onepress_projects_desc',
                    'onepress_projects_number',
                    'onepress_projects_orderby',
                    'onepress_projects_order',
                ),
            ),

            // + section testimonials
            array(
                'id' => 'testimonials',
                'selector' => '.section-testimonials',
                'settings' => array(
                    'onepress_testimonial_boxes',
                    'onepress_testimonial_title',
                    'onepress_testimonial_subtitle',
                    'onepress_testimonial_desc',
                ),
            ),
        );

        $settings = array_merge( $settings, $plus_settings );
        if ( isset( $settings['gallery'] ) ) {
            $settings['gallery']['settings'] = array(
                'onepress_gallery_source',
                'onepress_gallery_title',
                'onepress_gallery_subtitle',
                'onepress_gallery_desc',

                'onepress_gallery_source_page',
                'onepress_gallery_source_flickr',
                'onepress_gallery_api_flickr',
                'onepress_gallery_source_facebook',
                'onepress_gallery_api_facebook',
                'onepress_gallery_layout',
                'onepress_gallery_display',
                'onepress_g_number',
                'onepress_g_row_height',
                'onepress_g_col',

                'onepress_g_readmore_link',
                'onepress_g_readmore_text',
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
                } elseif ( file_exists( ONEPRESS_PLUS_PATH .'templates/' . $template_name ) ) { // Check part in the plugin
                    $located = ONEPRESS_PLUS_PATH .'templates/'. $template_name;
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

        //$wp_customize->remove_setting( 'onepress_hero_disable' );
        //$wp_customize->remove_control( 'onepress_hero_disable' );

        $wp_customize->remove_setting( 'onepress_features_disable' );
        $wp_customize->remove_control( 'onepress_features_disable' );

        $wp_customize->remove_setting( 'onepress_about_disable' );
        $wp_customize->remove_control( 'onepress_about_disable' );

        $wp_customize->remove_setting( 'onepress_services_disable' );
        $wp_customize->remove_control( 'onepress_services_disable' );

        $wp_customize->remove_setting( 'onepress_counter_disable' );
        $wp_customize->remove_control( 'onepress_counter_disable' );

        $wp_customize->remove_setting( 'onepress_testimonials_disable' );
        $wp_customize->remove_control( 'onepress_testimonials_disable' );

        $wp_customize->remove_setting( 'onepress_team_disable' );
        $wp_customize->remove_control( 'onepress_team_disable' );

        $wp_customize->remove_setting( 'onepress_news_disable' );
        $wp_customize->remove_control( 'onepress_news_disable' );

        $wp_customize->remove_setting( 'onepress_contact_disable' );
        $wp_customize->remove_control( 'onepress_contact_disable' );

        // Remove upsell panel/section
        $wp_customize->remove_setting( 'onepress_order_styling_message' );
        $wp_customize->remove_control( 'onepress_order_styling_message' );

        $wp_customize->remove_setting( 'onepress_videolightbox_image' );
        $wp_customize->remove_control( 'onepress_videolightbox_image' );
        $wp_customize->remove_control( 'onepress_videolightbox_disable' );
        $wp_customize->remove_control( 'onepress_videolightbox_disable' );

        $wp_customize->remove_setting( 'onepress_gallery_disable' );
        $wp_customize->remove_control( 'onepress_gallery_disable' );
        remove_theme_mod( 'onepress_gallery_disable' );

        // Remove hero background media upsell
        $wp_customize->remove_control( 'onepress_hero_videobackground_upsell' );

    }

    /**
     *  Get default sections settings
     *
     * @return array
     */
    function get_default_sections_settings(){
        return apply_filters( 'onepress_get_default_sections_settings', array(

                array(
                    'title' => esc_html__( 'Clients', 'onepress-plus' ),
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
                    'title' => esc_html__( 'Features', 'onepress-plus' ),
                    'section_id' => 'features',
                    'show_section' => get_theme_mod( 'onepress_features_disable', '' ) == 1 ?  '': 1,
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
                    'title' => esc_html__( 'About', 'onepress-plus' ),
                    'section_id' => 'about',
                    'show_section' => get_theme_mod( 'onepress_about_disable', '' ) == 1 ?  '': 1,
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
                    'title' => esc_html__( 'Services', 'onepress-plus' ),
                    'section_id' => 'services',
                    'show_section' => get_theme_mod( 'onepress_services_id', '' ) == 1 ?  '': 1,
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
                    'title' => esc_html__( 'Videolightbox', 'onepress-plus' ),
                    'section_id' => 'videolightbox',
                    'show_section' => get_theme_mod( 'onepress_videolightbox_disable', '' ) == 1 ?  '': 1,
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
                    'title' => esc_html__( 'Gallery', 'onepress-plus' ),
                    'section_id' => 'gallery',
                    'show_section' => get_theme_mod( 'onepress_gallery_disable', 1 ) == 1 ?  '': 1,
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
                    'title' => esc_html__( 'Projects', 'onepress-plus' ),
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
                    'title' => esc_html__( 'Counter', 'onepress-plus' ),
                    'section_id' => 'counter',
                    'show_section' => get_theme_mod( 'onepress_counter_disable', '' ) == 1 ?  '': 1,
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
                    'title' => esc_html__( 'Testimonials', 'onepress-plus' ),
                    'section_id' => 'testimonials',
                    'show_section' => get_theme_mod( 'onepress_testimonials_disable', '' ) == 1 ?  '': 1,
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
                    'title' => esc_html__( 'Pricing', 'onepress-plus' ),
                    'section_id' => 'pricing',
                    'show_section' => get_theme_mod( 'onepress_pricing_disable', '' ) == 1 ?  '': 1,
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
                    'title' => esc_html__( 'Call to Action', 'onepress-plus' ),
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
                    'title' => esc_html__( 'Team', 'onepress-plus' ),
                    'section_id' => 'team',
                    'show_section' => get_theme_mod( 'onepress_team_disable', '' ) == 1 ?  '': 1,
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
                    'title' => esc_html__( 'News', 'onepress-plus' ),
                    'section_id' => 'news',
                    'show_section' => get_theme_mod( 'onepress_news_disable', '' ) == 1 ?  '': 1,
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
                    'title' => esc_html__( 'Contact', 'onepress-plus' ),
                    'section_id' => 'contact',
                    'show_section' => get_theme_mod( 'onepress_contact_disable', '' ) == 1 ?  '': 1,
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
                    'title' => esc_html__( 'Map', 'onepress-plus' ),
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

        include_once ONEPRESS_PLUS_PATH.'inc/typography/typography.php';

        // Theme Global
        // Copyright text option
        $wp_customize->add_setting( 'onepress_footer_copyright_text',
            array(
                'sanitize_callback' => 'onepress_sanitize_text',
                'default'           => sprintf( esc_html__( 'Copyright %1$s %2$s %3$s', 'onepress-plus' ), '&copy;', esc_attr( date( 'Y' ) ), esc_attr( get_bloginfo() ) ),
            )
        );

        $wp_customize->add_control( new OnePress_Editor_Custom_Control(
            $wp_customize,
            'onepress_footer_copyright_text',
            array(
                'label'       => esc_html__('Footer Copyright', 'onepress-plus'),
                'section'     => 'onepress_global_settings',
                'description' => esc_html__('Arbitrary text or HTML.', 'onepress-plus')
            )
        ));

        // Disable theme author link
        $wp_customize->add_setting( 'onepress_hide_author_link',
            array(
                'sanitize_callback' => 'onepress_sanitize_checkbox',
                'default'           => '',
            )
        );
        $wp_customize->add_control( 'onepress_hide_author_link',
            array(
                'type'        => 'checkbox',
                'label'       => esc_html__('Hide theme author link?', 'onepress-plus'),
                'section'     => 'onepress_global_settings',
                'description' => esc_html__('Check this box to hide theme author link.', 'onepress-plus')
            )
        );

        // Typography
        // Register typography control JS template.
        $wp_customize->register_control_type( 'OnePress_Customize_Typography_Control' );

       $wp_customize->add_panel( 'onepress_typo', array( 'priority' => 25, 'title' => esc_html__( 'Typography', 'onepress-plus' ) ) );

        // For P tag
        $wp_customize->add_section(
            'onepress_typography_section',
            array( 'panel'=> 'onepress_typo',
                'title' => esc_html__( 'Paragraphs', 'onepress-plus' ), 'priority' => 5, )
        );

        // Add the `<p>` typography settings.
        // @todo Better sanitize_callback functions.
        $wp_customize->add_setting(
            'onepress_typo_p',
            array(
                'sanitize_callback' => 'onepress_sanitize_typography_field',
                'transport' => 'postMessage'
            )
        );

        $wp_customize->add_control(
            new OnePress_Customize_Typography_Control(
                $wp_customize,
                'onepress_typo_p',
                array(
                    'label'       => esc_html__( 'Paragraph Typography', 'onepress-plus' ),
                    'description' => esc_html__( 'Select how you want your paragraphs to appear.', 'onepress-plus' ),
                    'section'       => 'onepress_typography_section',
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
            'onepress_typo_menu_section',
            array(
                'panel'=> 'onepress_typo',
                'title' => esc_html__( 'Menu', 'onepress-plus' ), 'priority' => 5, )
        );

        // Add the menu typography settings.

       // Site title font
        $wp_customize->add_setting(
            'onepress_typo_site_title',
            array(
                'sanitize_callback' => 'onepress_sanitize_typography_field',
                'transport' => 'postMessage',
                'priority' => 100,
            )
        );

        $wp_customize->add_control(
            new OnePress_Customize_Typography_Control(
                $wp_customize,
                'onepress_typo_site_title',
                array(
                    'label'       => esc_html__( 'Site title Typography', 'onepress-plus' ),
                    'description' => esc_html__( 'Select how you want your site to appear.', 'onepress-plus' ),
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
            'onepress_typo_site_tagline',
            array(
                'sanitize_callback' => 'onepress_sanitize_typography_field',
                'transport' => 'postMessage',
                'priority' => 120,
            )

        );

        $wp_customize->add_control(
            new OnePress_Customize_Typography_Control(
                $wp_customize,
                'onepress_typo_site_tagline',
                array(
                    'label'       => esc_html__( 'Site Tagline Typography', 'onepress-plus' ),
                    'description' => esc_html__( 'Select how you want your site to appear.', 'onepress-plus' ),
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
            'onepress_typo_menu',
            array(
                'sanitize_callback' => 'onepress_sanitize_typography_field',
                'transport' => 'postMessage'
            )
        );

        $wp_customize->add_control(
            new OnePress_Customize_Typography_Control(
                $wp_customize,
                'onepress_typo_menu',
                array(
                    'label'       => esc_html__( 'Menu Typography', 'onepress-plus' ),
                    'description' => esc_html__( 'Select how you want your Menu to appear.', 'onepress-plus' ),
                    'section'       => 'onepress_typo_menu_section',
                    'css_selector'       => '.onepress-menu a', // css selector for live view
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
            'onepress_typo_heading_section',
            array(
                'panel'=> 'onepress_typo',
                'title' => esc_html__( 'Heading', 'onepress-plus' ), 'priority' => 5, )
        );

        // Add the menu typography settings.
        // @todo Better sanitize_callback functions.
        $wp_customize->add_setting(
            'onepress_typo_heading',
            array(
                'sanitize_callback' => 'onepress_sanitize_typography_field',
                'transport' => 'postMessage'
            )
        );

        $wp_customize->add_control(
            new OnePress_Customize_Typography_Control(
                $wp_customize,
                'onepress_typo_heading',
                array(
                    'label'       => esc_html__( 'Heading Typography', 'onepress-plus' ),
                    'description' => esc_html__( 'Select how you want your Heading to appear.', 'onepress-plus' ),
                    'section'       => 'onepress_typo_heading_section',
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
        $wp_customize->remove_setting( 'onepress_team_members' );
        $wp_customize->remove_control( 'onepress_team_members' );


        $wp_customize->add_setting(
            'onepress_team_members',
            array(
                'sanitize_callback' => 'onepress_sanitize_repeatable_data_field',
                'transport' => 'refresh', // refresh or postMessage
            ) );


        $wp_customize->add_control(
            new Onepress_Customize_Repeatable_Control(
                $wp_customize,
                'onepress_team_members',
                array(
                    'label'     => esc_html__('Team members', 'onepress-plus'),
                    'description'   => '',
                    'section'       => 'onepress_team_content',
                    //'live_title_id' => 'user_id', // apply for unput text and textarea only
                    'title_format'  => esc_html__( '[live_title]', 'onepress-plus'), // [live_title]
                    'max_item'      => 4, // Maximum item can add
                    'fields'    => array(
                        'user_id' => array(
                            'title' => esc_html__('User media', 'onepress-plus'),
                            'type'  =>'media',
                            'desc'  => '',
                        ),
                        'link' => array(
                            'title' => esc_html__('Custom Link', 'onepress-plus'),
                            'type'  =>'text',
                            'desc'  => '',
                        ),

                        'url' => array(
                            'title' => esc_html__('Website', 'onepress-plus'),
                            'type'  =>'text',
                            'desc'  => '',
                        ),
                        'facebook' => array(
                            'title' => esc_html__('Facebook', 'onepress-plus'),
                            'type'  =>'text',
                            'desc'  => '',
                        ),
                        'twitter' => array(
                            'title' => esc_html__('Twitter', 'onepress-plus'),
                            'type'  =>'text',
                            'desc'  => '',
                        ),
                        'google_plus' => array(
                            'title' => esc_html__('Google+', 'onepress-plus'),
                            'type'  =>'text',
                            'desc'  => '',
                        ),
                        'linkedin' => array(
                            'title' => esc_html__('linkedin', 'onepress-plus'),
                            'type'  =>'text',
                            'desc'  => '',
                        ),
                        'email' => array(
                            'title' => esc_html__('Email', 'onepress-plus'),
                            'type'  =>'text',
                            'desc'  => '',
                        ),
                    ),

                )
            )
        );
        // End section team



        // Order and styling
        $wp_customize->add_section( 'onepress_section_order' ,
            array(
                'priority'    => 125,
                'title'       => esc_html__( 'Section Order & Styling', 'onepress-plus' ),
                'description' => '',
                'active_callback' => ( function_exists( 'onepress_showon_frontpage' ) ) ? 'onepress_showon_frontpage' : false
            )
        );
       // remove_theme_mod( 'onepress_section_order_styling' );

        // Hero section
        // Video MP4
        $wp_customize->add_setting( 'onepress_hero_video_mp4',
            array(
                'sanitize_callback' => 'onepress_sanitize_text',
                'default'           => '',
                'transport' => 'refresh', // refresh or postMessage
            )
        );
        $wp_customize->add_control( new WP_Customize_Media_Control(
                $wp_customize,
                'onepress_hero_video_mp4',
                array(
                    'label' 		=> esc_html__('Background Video (.MP4)', 'onepress-plus'),
                    'section' 		=> 'onepress_hero_images',
                    'priority'      => 100,
                )
            )
        );
        // Video webm
        $wp_customize->add_setting( 'onepress_hero_video_webm',
            array(
                'sanitize_callback' => 'onepress_sanitize_text',
                'default'           => '',
                'transport' => 'refresh', // refresh or postMessage
            )
        );
        $wp_customize->add_control( new WP_Customize_Media_Control(
                $wp_customize,
                'onepress_hero_video_webm',
                array(
                    'label' 		=> esc_html__('Background Video(.WEBM)', 'onepress-plus'),
                    'section' 		=> 'onepress_hero_images',
                    'priority'      => 105,
                )
            )
        );
        // Video OGV
        $wp_customize->add_setting( 'onepress_hero_video_ogv',
            array(
                'sanitize_callback' => 'onepress_sanitize_text',
                'default'           => '',
                'transport' => 'refresh', // refresh or postMessage
            )
        );
        $wp_customize->add_control( new WP_Customize_Media_Control(
                $wp_customize,
                'onepress_hero_video_ogv',
                array(
                    'label' 		=> esc_html__('Background Video(.OGV)', 'onepress-plus'),
                    'section' 		=> 'onepress_hero_images',
                    'priority'      => 110,
                )
            )
        );
        // Hero mobile video fallback
        $wp_customize->add_setting( 'onepress_hero_mobile_img',
            array(
                'sanitize_callback' => 'onepress_sanitize_checkbox',
                'default'           => '',
            )
        );
        $wp_customize->add_control( 'onepress_hero_mobile_img',
            array(
                'type'        => 'checkbox',
                'priority'      => 115,
                'label'       => esc_html__('On mobile replace video with first background image.', 'onepress-plus'),
                'section'     => 'onepress_hero_images',
            )
        );

        // END Hero section

        $wp_customize->add_setting(
            'onepress_section_order_styling',
            array(
                //'default' => json_encode( $this->get_default_sections_settings() ),
                'sanitize_callback' => 'onepress_sanitize_repeatable_data_field',
                'transport' => 'refresh', // refresh or postMessage
            ) );

        $wp_customize->add_control(
            new Onepress_Customize_Repeatable_Control(
                $wp_customize,
                'onepress_section_order_styling',
                array(
                    'label' 		=> esc_html__('Section Order & Styling', 'onepress-plus'),
                    'description'   => '',
                    'section'       => 'onepress_section_order',
                    'live_title_id' => 'title', // apply for unput text and textarea only
                    'title_format'  => esc_html__('[Custom Section]: [live_title]', 'onepress-plus'), // [live_title]
                    'changeable'    => 'no', // Can Remove, add new button  default yes
                    'defined_values'   => $this->get_default_sections_settings(),
                    'id_key'    => 'section_id',
                    'default_empty_title'  => esc_html__('Untitled', 'onepress-plus'), // [live_title]
                    'fields'    => array(
                        'add_by' => array(
                            'type'  =>'add_by',
                        ),
                        'title' => array(
                            'title' => esc_html__('Title', 'onepress-plus'),
                            'type'  =>'hidden',
                            'desc'  => ''
                        ),
                        'section_id' => array(
                            'title' => esc_html__('Section ID', 'onepress-plus'),
                            'type'  =>'hidden',
                            'desc'  => ''
                        ),
                        'show_section' => array(
                            'title' => esc_html__('Show this section', 'onepress-plus'),
                            'type'  =>'checkbox',
                            'default'  =>'1',
                        ),
                        'section_inverse' => array(
                            'title' => esc_html__('Inverted Style', 'onepress-plus'),
                            'desc'  => esc_html__('Make this section darker', 'onepress-plus'),
                            'type'  =>'checkbox',
                        ),
                        'hide_title' => array(
                            'title' => esc_html__('Hide section title', 'onepress-plus'),
                            'type'  =>'checkbox',
                            'desc'  => '',
                            'required' => array( 'add_by', '=', 'click' ) ,
                        ),
                        'subtitle' => array(
                            'title' => esc_html__('Subtitle', 'onepress-plus'),
                            'type'  =>'text',
                            'required' => array( 'add_by', '=', 'click' ) ,
                        ),
                        'desc' => array(
                            'title' => esc_html__('Section Description', 'onepress-plus'),
                            'type'  =>'editor',
                            'required' => array( 'add_by', '=', 'click' ) ,
                        ),
                        'content' => array(
                            'title' => esc_html__('Section Content', 'onepress-plus'),
                            'type'  =>'editor',
                            'required' => array( 'add_by', '=', 'click' ) ,
                        ),
                        'bg_type' => array(
                            'title' => esc_html__('Background Type', 'onepress-plus'),
                            'type'  =>'select',
                            'options'  => array(
                                'color' => esc_html__('Color', 'onepress-plus'),
                                'image' => esc_html__('Image', 'onepress-plus'),
                                'video' => esc_html__('Video', 'onepress-plus'),
                            ),
                        ),
                        'bg_color' => array(
                            'title' => esc_html__('Background Color', 'onepress-plus'),
                            'type'  =>'coloralpha',
                            'required' => array( 'bg_type', '=', 'color' ) ,
                        ),
                        'bg_image' => array(
                            'title' => esc_html__('Background Image', 'onepress-plus'),
                            'type'  =>'media',
                            'required' => array( 'bg_type', 'in', array( 'video', 'image' ) ) ,
                        ),
                        'enable_parallax' => array(
                            'title' => esc_html__('Enable Parallax', 'onepress-plus'),
                            'desc'  => esc_html__('Required background image and Inverted Style is checked', 'onepress-plus'),
                            'type'  =>'checkbox',
                            'required' => array( 'bg_type', '=', 'image' ) ,
                        ),
                        'bg_video' => array(
                            'title' => esc_html__('Background video(.MP4)', 'onepress-plus'),
                            'type'  =>'media',
                            'media'  =>'video',
                            'required' => array( 'bg_type', '=', 'video' ) ,
                            //'desc' => esc_html__('Select your video background', 'onepress-plus'),
                        ),
                        'bg_video_webm' => array(
                            'title' => esc_html__('Background video(.WEBM)', 'onepress-plus'),
                            'type'  =>'media',
                            'media'  =>'video',
                            'required' => array( 'bg_type', '=', 'video' ) ,
                            //'desc' => esc_html__('Select your video background', 'onepress-plus'),
                        ),
                        'bg_video_ogv' => array(
                            'title' => esc_html__('Background video(.OGV)', 'onepress-plus'),
                            'type'  =>'media',
                            'media'  =>'video',
                            'required' => array( 'bg_type', '=', 'video' ) ,
                            //'desc' => esc_html__('Select your video background', 'onepress-plus'),
                        ),

                        'bg_opacity_color' => array(
                            'title' => esc_html__('Overlay Color', 'onepress-plus'),
                            'type'  =>'coloralpha',
                            'required' => array( 'bg_type', 'in', array( 'video', 'image' ) ) ,
                        ),

                        /*
                        'bg_opacity' => array(
                            'title' => esc_html__('Overlay Color Opacity', 'onepress-plus'),
                            'type'  =>'text',
                            'desc' => esc_html__('Enter a float number between 0.1 to 0.9', 'onepress-plus'),
                        ),
                        */

                        'padding_top' => array(
                            'title' => esc_html__('Section Padding Top', 'onepress-plus'),
                            'type'  =>'text',
                            'desc' => esc_html__('Eg. 50px, 30%, leave empty for default value', 'onepress-plus'),
                        ),
                        'padding_bottom' => array(
                            'title' => esc_html__('Section Padding Bottom', 'onepress-plus'),
                            'type'  =>'text',
                            'desc' => esc_html__('Eg. 50px, 30%, leave empty for default value', 'onepress-plus'),
                        ),

                    ),
                )
            )
        );

        /*------------------------------------------------------------------------*/
        /*  Section: Testimonials
        /*------------------------------------------------------------------------*/
        $wp_customize->add_panel( 'onepress_testimonial' ,
            array(
                'priority'        => 220,
                'title'           => esc_html__( 'Section: Testimonial', 'onepress-plus' ),
                'description'     => '',
                'active_callback' => 'onepress_showon_frontpage'
            )
        );

        $wp_customize->add_section( 'onepress_testimonial_settings' ,
            array(
                'priority'    => 3,
                'title'       => esc_html__( 'Section Settings', 'onepress-plus' ),
                'description' => '',
                'panel'       => 'onepress_testimonial',
            )
        );
        // Show Content
        /*
        $wp_customize->add_setting( 'onepress_testimonials_disable',
            array(
                'sanitize_callback' => 'onepress_sanitize_checkbox',
                'default'           => '',
            )
        );
        $wp_customize->add_control( 'onepress_testimonials_disable',
            array(
                'type'        => 'checkbox',
                'label'       => esc_html__('Hide this section?', 'onepress-plus'),
                'section'     => 'onepress_testimonial_settings',
                'description' => esc_html__('Check this box to hide this section.', 'onepress-plus'),
            )
        );
        */

        // Section ID
        $wp_customize->add_setting( 'onepress_testimonial_id',
            array(
                'sanitize_callback' => 'onepress_sanitize_text',
                'default'           => esc_html__('testimonials', 'onepress-plus'),
            )
        );
        $wp_customize->add_control( 'onepress_testimonial_id',
            array(
                'label'     => esc_html__('Section ID:', 'onepress-plus'),
                'section' 		=> 'onepress_testimonial_settings',
                'description'   => esc_html__( 'The section id, we will use this for link anchor.', 'onepress-plus' ),
            )
        );

        // Title
        $wp_customize->add_setting( 'onepress_testimonial_title',
            array(
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => esc_html__('Testimonials', 'onepress-plus'),
            )
        );
        $wp_customize->add_control( 'onepress_testimonial_title',
            array(
                'label'     => esc_html__('Section Title', 'onepress-plus'),
                'section' 		=> 'onepress_testimonial_settings',
                'description'   => '',
            )
        );

        // Sub Title
        $wp_customize->add_setting( 'onepress_testimonial_subtitle',
            array(
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => esc_html__('Section subtitle', 'onepress-plus'),
            )
        );
        $wp_customize->add_control( 'onepress_testimonial_subtitle',
            array(
                'label'     => esc_html__('Section Subtitle', 'onepress-plus'),
                'section' 		=> 'onepress_testimonial_settings',
                'description'   => '',
            )
        );

        // Description
        $wp_customize->add_setting( 'onepress_testimonial_desc',
            array(
                'sanitize_callback' => 'onepress_sanitize_text',
                'default'           => '',
            )
        );
        $wp_customize->add_control( new OnePress_Editor_Custom_Control(
            $wp_customize,
            'onepress_testimonial_desc',
            array(
                'label' 		=> esc_html__('Section Description', 'onepress-plus'),
                'section' 		=> 'onepress_testimonial_settings',
                'description'   => '',
            )
        ));

        // Testimonials content
        $wp_customize->add_section( 'onepress_testimonials_content' ,
            array(
                'priority'    => 3,
                'title'       => esc_html__( 'Section Content', 'onepress-plus' ),
                'description' => '',
                'panel'       => 'onepress_testimonial',
            )
        );
        $wp_customize->add_setting(
            'onepress_testimonial_boxes',
            array(
                'default' => json_encode(
                    array(
                        array(
                            'title' 		=> esc_html__( 'Praesent placerat', 'onepress-plus' ),
                            'name' 			=> esc_html__( 'Alexander Rios', 'onepress-plus' ),
                            'subtitle' 		=> esc_html__( 'Founder & CEO', 'onepress-plus' ),
                            'style'         => 'warning',
                            'image' 		=> array(
                                'url' => get_template_directory_uri() . '/assets/images/testimonial_1.jpg',
                                'id'  => ''
                            ),
                            'content' 		=> esc_html__( 'Praesent dapibus, neque id cursus faucibus, tortor neque egestas augue, eu vulputate magna eros eu erat. Aliquam erat volutpat.', 'onepress-plus' ),

                        ),
                        array(
                            'title' 		=> esc_html__( 'Cras iaculis', 'onepress-plus' ),
                            'name' 			=> esc_html__( 'Alexander Max', 'onepress-plus' ),
                            'subtitle' 		=> esc_html__( 'Founder & CEO', 'onepress-plus' ),
                            'style'         => 'success',
                            'image' 		=> array(
                                'url' => get_template_directory_uri() . '/assets/images/testimonial_2.jpg',
                                'id'  => ''
                            ),
                            'content' 		=> esc_html__( 'Praesent dapibus, neque id cursus faucibus, tortor neque egestas augue eu vulputate.', 'onepress-plus' ),

                        ),
                        array(
                            'title' 		=> esc_html__( 'Fusce lobortis', 'onepress-plus' ),
                            'name' 			=> esc_html__( 'Peter Mendez', 'onepress-plus' ),
                            'subtitle' 		=> esc_html__( 'Example Company', 'onepress-plus' ),
                            'style'         => 'theme-primary',
                            'image' 		=> array(
                                'url' => get_template_directory_uri() . '/assets/images/testimonial_3.jpg',
                                'id'  => ''
                            ),
                            'content' 		=> esc_html__( 'Sed adipiscing ornare risus. Morbi est est, blandit sit amet, sagittis vel, euismod vel, velit. Pellentesque egestas sem. Suspendisse commodo ullamcorper magna egestas sem.', 'onepress-plus' ),
                        ),

                    )
                ),
                'sanitize_callback' => 'onepress_sanitize_repeatable_data_field',
                'transport' => 'refresh', // refresh or postMessage
            ) );

        $wp_customize->add_control(
            new Onepress_Customize_Repeatable_Control(
                $wp_customize,
                'onepress_testimonial_boxes',
                array(
                    'label'     => esc_html__('Testimonial', 'onepress-plus'),
                    'description'   => '',
                    'section'       => 'onepress_testimonials_content',
                    'live_title_id' => 'title', // apply for unput text and textarea only
                    'title_format'  => esc_html__( '[live_title]', 'onepress-plus'), // [live_title]
                    'max_item'      => 3, // Maximum item can add

                    'fields'    => array(
                        'title' => array(
                            'title' => esc_html__('Title', 'onepress-plus'),
                            'type'  =>'text',
                            'desc'  => '',
                            'default'  => esc_html__('Testimonial title', 'onepress-plus'),
                        ),
                        'name' => array(
                            'title' => esc_html__('Name', 'onepress-plus'),
                            'type'  =>'text',
                            'desc'  => '',
                            'default'  => esc_html__('User name', 'onepress-plus'),
                        ),
                        'image' => array(
                            'title' => esc_html__('Avatar', 'onepress-plus'),
                            'type'  =>'media',
                            'desc'  => esc_html__( 'Suggestion: 100x100px square image.', 'onepress-plus' ),
                            'default' => array(
                                'url' => get_template_directory_uri().'/assets/images/testimonial_1.jpg',
                                'id' => ''
                            )
                        ),
                        'subtitle' => array(
                            'title' => esc_html__('Subtitle', 'onepress-plus'),
                            'type'  =>'textarea',
                            'default'  => esc_html__('Example Company', 'onepress-plus'),
                        ),
                        'content' => array(
                            'title' => esc_html__('Content', 'onepress-plus'),
                            'type'  =>'textarea',
                            'default'  => esc_html__('Whatever your user say', 'onepress-plus'),
                        ),

                        'style' => array(
                            'title' => esc_html__('Style', 'onepress-plus'),
                            'type'  =>'select',
                            'default'  => 'light',
                            'options' => array(
                                'theme-primary' => esc_html__( 'Theme default', 'onepress-plus' ),
                                'light' => esc_html__( 'Light', 'onepress-plus' ),
                                'primary' => esc_html__( 'Primary', 'onepress-plus' ),
                                'success' => esc_html__( 'Success', 'onepress-plus' ),
                                'info' => esc_html__( 'Info', 'onepress-plus' ),
                                'warning' => esc_html__( 'Warning', 'onepress-plus' ),
                                'danger' => esc_html__( 'Danger', 'onepress-plus' ),
                            )
                        ),


                    ),

                )
            )
        );


        /*------------------------------------------------------------------------*/
        /*  Section: Map
        /*------------------------------------------------------------------------*/
        $wp_customize->add_panel( 'onepress_map' ,
            array(
                'priority'        => 280,
                'title'           => __( 'Section: Map', 'onepress-plus' ),
                'description'     => '',
                'active_callback' => 'onepress_showon_frontpage'
            )
        );

        $wp_customize->add_section( 'onepress_map_settings' ,
            array(
                'priority'    => 3,
                'title'       => __( 'Section Settings', 'onepress-plus' ),
                'panel'       => 'onepress_map',
            )
        );

        // Section ID
        $wp_customize->add_setting( 'onepress_map_id',
            array(
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => 'map',
            )
        );

        $wp_customize->add_control( 'onepress_map_id',
            array(
                'label' 		=> __('Section ID', 'onepress-plus'),
                'section' 		=> 'onepress_map_settings',
                'description'   => '',
            )
        );

        // Map api key code
        $wp_customize->add_setting( 'onepress_map_api_key',
            array(
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => '',
            )
        );
        $wp_customize->add_control( 'onepress_map_api_key',
            array(
                'label'       => __('Google map api key', 'onepress-plus'),
                'section'     => 'onepress_map_settings',
                'description' => __('In order to show the Google Maps section, you must enter a validate Google Maps API key, you can get one <a href="https://developers.google.com/maps/documentation/javascript/get-api-key" target="_blank">here</a>.', 'onepress-plus'),
            )
        );

        // Latitude
        $wp_customize->add_setting( 'onepress_map_lat',
            array(
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => '37.3317115',
            )
        );

        $wp_customize->add_control( 'onepress_map_lat',
            array(
                'label' 		=> __('Latitude', 'onepress-plus'),
                'section' 		=> 'onepress_map_settings',
                'description'   => '',
            )
        );

        // Longitude
        $wp_customize->add_setting( 'onepress_map_long',
            array(
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => '-122.0301835',
            )
        );
        $wp_customize->add_control( 'onepress_map_long',
            array(
                'label' 		=> __('Longitude', 'onepress-plus'),
                'section' 		=> 'onepress_map_settings',
            )
        );

        // OnePress_Misc_Control

        $wp_customize->add_setting( 'onepress_map_message',
            array(
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => '',
            )
        );
        $wp_customize->add_control(
            new OnePress_Misc_Control(
                $wp_customize,
                'onepress_map_message',
                array(
                    'label' 		=> __('Longitude', 'onepress-plus'),
                    'type'          => 'custom_message',
                    'section' 		=> 'onepress_map_settings',
                    'description'   => sprintf( __( 'Find your Latitude, Longitude <a target="_blank" href="%1$s">Here</a>', 'onepress-plus' ), 'http://www.mapcoordinates.net/en' ),
                )
            )
        );

        // Address
        $wp_customize->add_setting( 'onepress_map_address',
            array(
                'sanitize_callback' => 'onepress_sanitize_text',
                'default'           => __( '<strong>1 Infinite Loop Cupertino <br/> CA 95014  United States</strong>' , 'onepress-plus' ),
            )
        );

        $wp_customize->add_control( new OnePress_Editor_Custom_Control(
            $wp_customize,
            'onepress_map_address',
            array(
                'label' 		=> __('Address', 'onepress-plus'),
                'section' 		=> 'onepress_map_settings',
            )
        ));

        // Extra Info
        $wp_customize->add_setting( 'onepress_map_html',
            array(
                'sanitize_callback' => 'onepress_sanitize_text',
                'default'           => __('<p>Your address description goes here.</p>', 'onepress-plus'),
            )
        );
        $wp_customize->add_control( new OnePress_Editor_Custom_Control(
            $wp_customize,
            'onepress_map_html',
            array(
                'label' 		=> __('Extra Info', 'onepress-plus'),
                'section' 		=> 'onepress_map_settings',
                'description'   => __('The HTML code that display on info window when you click to marker', 'onepress-plus'),
            )
        ));


        $wp_customize->add_setting(
            'onepress_map_items_address',
            array(
                'default' => '',
                'sanitize_callback' => 'onepress_sanitize_repeatable_data_field',
                'transport' => 'refresh', // refresh or postMessage
            ) );


        $wp_customize->add_control(
            new Onepress_Customize_Repeatable_Control(
                $wp_customize,
                'onepress_map_items_address',
                array(
                    'label'     	=> esc_html__('Multiple Address', 'onepress-plus'),
                    'description'   => '',
                    'section'       => 'onepress_map_settings',
                    'live_title_id' => 'address', // apply for unput text and textarea only
                    'title_format'  => esc_html__('[live_title]', 'onepress-plus'), // [live_title]
                    'max_item'      => 4, // Maximum item can add

                    'fields'    => array(
                        'address' => array(
                            'title' => esc_html__('Address', 'onepress-plus'),
                            'type'  =>'text',
                            'desc'  => '',
                        ),
                        'lat' => array(
                            'title' => esc_html__('Latitude', 'onepress-plus'),
                            'type'  =>'text',
                            'default' => '',
                        ),
                        'long' => array(
                            'title' => esc_html__('Longitude', 'onepress-plus'),
                            'type'  =>'text',
                            'default' => '',
                        ),
                        'desc' => array(
                            'title' => esc_html__('Extra info', 'onepress-plus'),
                            'type'  =>'textarea',
                            'default' => '',
                        ),

                        'maker' => array(
                            'title' => esc_html__('Marker', 'onepress-plus'),
                            'type'  =>'media',
                            'default' => '',
                        ),

                    ),

                )
            )
        );


        //-------------------------




        // Color
        $wp_customize->add_setting( 'onepress_map_color',
            array(
                'sanitize_callback' => 'sanitize_hex_color',
                'default'           => '',
            )
        );
        $wp_customize->add_control( new WP_Customize_Color_Control(
            $wp_customize,
            'onepress_map_color',
            array(
                'label' 		=> __('Map Color', 'onepress-plus'),
                'section' 		=> 'onepress_map_settings',
                'description'   => '',
            )
        ));

        // Maker
        $wp_customize->add_setting( 'onepress_map_maker',
            array(
                'sanitize_callback' => 'onepress_sanitize_text',
                'default'           => ONEPRESS_PLUS_URL.'assets/images/map-marker.png',
            )
        );
        $wp_customize->add_control( new WP_Customize_Image_Control(
            $wp_customize,
            'onepress_map_maker',
            array(
                'label' 		=> __('Map Marker', 'onepress-plus'),
                'section' 		=> 'onepress_map_settings',
                'description'   => __('Size no larger than 80x80px', 'onepress-plus'),
            )
        ));


        // Height
        $wp_customize->add_setting( 'onepress_map_zoom',
            array(
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => '10',
            )
        );

        $wp_customize->add_control( 'onepress_map_zoom',
            array(
                'label' 		=> __('Map Zoom', 'onepress-plus'),
                'section' 		=> 'onepress_map_settings',
                'description'   => __('Map Zoom, default 10', 'onepress-plus'),
            )
        );

        // Height
        $wp_customize->add_setting( 'onepress_map_height',
            array(
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => '',
            )
        );

        $wp_customize->add_control( 'onepress_map_height',
            array(
                'label' 		=> __('Map Height', 'onepress-plus'),
                'section' 		=> 'onepress_map_settings',
                'description'   => '',
            )
        );

        // Scroll wheel
        $wp_customize->add_setting( 'onepress_map_scrollwheel',
            array(
                'sanitize_callback' => 'onepress_sanitize_checkbox',
                'default'           => '',
            )
        );
        $wp_customize->add_control( 'onepress_map_scrollwheel',
            array(
                'type'        => 'checkbox',
                'label'       => __('Enable Scrollwheel', 'onepress-plus'),
                'section'     => 'onepress_map_settings',
                'description' => esc_html__('Check this box to enable enable mouse scroll wheel.', 'onepress-plus'),
            )
        );



        // EN Add map


        /*------------------------------------------------------------------------*/
        /*  Section: Project
        /*------------------------------------------------------------------------*/
        $wp_customize->add_panel( 'onepress_projects' ,
            array(
                'priority'        => 200,
                'title'           => __( 'Section: Projects', 'onepress-plus' ),
                'description'     => '',
                'active_callback' => 'onepress_showon_frontpage'
            )
        );

        $wp_customize->add_section( 'onepress_projects_settings' ,
            array(
                'priority'    => 3,
                'title'       => __( 'Section Settings', 'onepress-plus' ),
                'panel'       => 'onepress_projects',
            )
        );

        // Project ID
        $wp_customize->add_setting( 'onepress_projects_id',
            array(
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => 'projects',
            )
        );
        $wp_customize->add_control( 'onepress_projects_id',
            array(
                'label' 		=> esc_html__('Section ID', 'onepress-plus'),
                'section' 		=> 'onepress_projects_settings',
                'description'   => '',
            )
        );

        // Project title
        $wp_customize->add_setting( 'onepress_projects_title',
            array(
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => esc_html__( 'Highlight Projects', 'onepress-plus' ),
            )
        );
        $wp_customize->add_control( 'onepress_projects_title',
            array(
                'label' 		=> esc_html__('Section Title', 'onepress-plus'),
                'section' 		=> 'onepress_projects_settings',
                'description'   => '',
            )
        );

        // Project subtitle
        $wp_customize->add_setting( 'onepress_projects_subtitle',
            array(
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => esc_html__( 'Some of our works', 'onepress-plus' ),
            )
        );
        $wp_customize->add_control( 'onepress_projects_subtitle',
            array(
                'label' 		=> esc_html__('Section subtitle', 'onepress-plus'),
                'section' 		=> 'onepress_projects_settings',
                'description'   => '',
            )
        );

        // Description
        $wp_customize->add_setting( 'onepress_projects_desc',
            array(
                'sanitize_callback' => 'onepress_sanitize_text',
                'default'           => '',
            )
        );
        $wp_customize->add_control( new OnePress_Editor_Custom_Control(
            $wp_customize,
            'onepress_projects_desc',
            array(
                'label' 		=> esc_html__('Section Description', 'onepress-plus'),
                'section' 		=> 'onepress_projects_settings',
                'description'   => '',
            )
        ));

        // Number projects to show
        $wp_customize->add_setting( 'onepress_projects_number',
            array(
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => '6',
            )
        );
        $wp_customize->add_control( 'onepress_projects_number',
            array(
                'label' 		=> esc_html__('Number projects to show', 'onepress-plus'),
                'section' 		=> 'onepress_projects_settings',
                'description'   => '',
            )
        );

        // Project order by
        $wp_customize->add_setting( 'onepress_projects_orderby',
            array(
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => 'ID',
            )
        );

        $wp_customize->add_control( 'onepress_projects_orderby',
            array(
                'label' 		=> esc_html__('Order By', 'onepress-plus'),
                'section' 		=> 'onepress_projects_settings',
                'description'   => '',
                'type'          => 'select',
                'choices'       => array(
                    'ID' => __( 'ID', 'onepress-plus' ),
                    'title' => __( 'Title', 'onepress-plus' ),
                    'date' => __( 'Date', 'onepress-plus' ),
                    'rand' => __( 'Random', 'onepress-plus' ),
                ),
            )
        );

        // Project order
        $wp_customize->add_setting( 'onepress_projects_order',
            array(
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => 'DESC',
            )
        );

        $wp_customize->add_control( 'onepress_projects_order',
            array(
                'label' 		=> esc_html__('Order', 'onepress-plus'),
                'section' 		=> 'onepress_projects_settings',
                'description'   => '',
                'type'          => 'select',
                'choices'       => array(
                    'DESC' => __( 'Descending', 'onepress-plus' ),
                    'ASC' => __( 'Ascending', 'onepress-plus' ),
                ),
            )
        );

        // Project slug
        $wp_customize->add_setting( 'onepress_project_slug',
            array(
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => 'portfolio',
            )
        );
        $wp_customize->add_control( 'onepress_project_slug',
            array(
                'label' 		=> __('Project slug', 'onepress-plus'),
                'section' 		=> 'onepress_projects_settings',
                'description'   => __( 'If you change this option please go to Settings > Permalinks and refresh your permalink structure before your custom post type will show the correct structure.', 'onepress-plus' ),
            )
        );

        // Ajax view projects
        $wp_customize->add_setting( 'onepress_project_ajax',
            array(
                'sanitize_callback' => 'onepress_sanitize_checkbox',
                'default'           => 0,
            )
        );
        $wp_customize->add_control( 'onepress_project_ajax',
            array(
                'type'        => 'checkbox',
                'label'       => esc_html__('Use ajax for load project details', 'onepress-plus'),
                'section'     => 'onepress_projects_settings',
            )
        );


        // end project

        /*------------------------------------------------------------------------*/
        /*  Section: Pricing Table
        /*------------------------------------------------------------------------*/
        $wp_customize->add_panel( 'onepress_pricing' ,
            array(
                'priority'        => 230,
                'title'           => __( 'Section: Pricing', 'onepress-plus' ),
                'description'     => '',
                'active_callback' => 'onepress_showon_frontpage'
            )
        );

        $wp_customize->add_section( 'onepress_pricing_settings' ,
            array(
                'priority'    => 3,
                'title'       => __( 'Section Settings', 'onepress-plus' ),
                'panel'       => 'onepress_pricing',
            )
        );

        // Project ID
        $wp_customize->add_setting( 'onepress_pricing_id',
            array(
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => 'projects',
            )
        );
        $wp_customize->add_control( 'onepress_pricing_id',
            array(
                'label' 		=> __('Section ID', 'onepress-plus'),
                'section' 		=> 'onepress_pricing_settings',
                'description'   => '',
            )
        );

        // Project title
        $wp_customize->add_setting( 'onepress_pricing_title',
            array(
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => __( 'Pricing Table', 'onepress-plus' ),
            )
        );
        $wp_customize->add_control( 'onepress_pricing_title',
            array(
                'label' 		=> __('Section Title', 'onepress-plus'),
                'section' 		=> 'onepress_pricing_settings',
                'description'   => '',
            )
        );

        // Project subtitle
        $wp_customize->add_setting( 'onepress_pricing_subtitle',
            array(
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => __( 'Responsive pricing section', 'onepress-plus' ),
            )
        );
        $wp_customize->add_control( 'onepress_pricing_subtitle',
            array(
                'label' 		=> __('Some of our works', 'onepress-plus'),
                'section' 		=> 'onepress_pricing_settings',
                'description'   => '',
            )
        );

        // Description
        $wp_customize->add_setting( 'onepress_pricing_desc',
            array(
                'sanitize_callback' => 'onepress_sanitize_text',
                'default'           => '',
            )
        );
        $wp_customize->add_control( new OnePress_Editor_Custom_Control(
            $wp_customize,
            'onepress_pricing_desc',
            array(
                'label' 		=> esc_html__('Section Description', 'onepress-plus'),
                'section' 		=> 'onepress_pricing_settings',
                'description'   => '',
            )
        ));



        // Section content
        $wp_customize->add_section( 'onepress_pricing_content' ,
            array(
                'priority'    => 3,
                'title'       => __( 'Section Content', 'onepress-plus' ),
                'panel'       => 'onepress_pricing',
            )
        );
        $wp_customize->add_setting(
            'onepress_pricing_plans',
            array(
                'default' => json_encode(
                    array(
                        array(
                            'title' => esc_html__( 'Freelancer', 'onepress-plus' ),
                            'code'  => esc_html__( '$', 'onepress-plus' ),
                            'price'  => '9.90',
                            'subtitle' => esc_html__( 'Perfect for single freelancers who work by themselves', 'onepress-plus' ),
                            'content' => esc_html__( "Support Forum \nFree hosting\n 1 hour of support\n 40MB of storage space", 'onepress-plus' ),
                            'label' => esc_attr__( 'Choose Plan', 'onepress-plus' ),
                            'link' => '#',
                            'button' => 'btn-theme-primary',
                        ),
                        array(
                            'title' => esc_html__( 'Small Business', 'onepress-plus' ),
                            'code'  => esc_html__( '$', 'onepress-plus' ),
                            'price'  => '29.9',
                            'subtitle' => esc_html__( 'Suitable for small businesses with up to 5 employees', 'onepress-plus' ),
                            'content' => esc_html__( "Support Forum \nFree hosting\n 10 hour of support\n 1GB of storage space", 'onepress-plus' ),
                            'label' => esc_attr__( 'Choose Plan', 'onepress-plus' ),
                            'link' => '#',
                            'button' => 'btn-success',
                        ),
                        array(
                            'title' => esc_html__( 'Larger Business', 'onepress-plus' ),
                            'code'  => esc_html__( '$', 'onepress-plus' ),
                            'price'  => '59.90',
                            'subtitle' => esc_html__( 'Great for large businesses with more than 5 employees', 'onepress-plus' ),
                            'content' => esc_html__( "Support Forum \nFree hosting\n Unlimited hours of support\n Unlimited storage space", 'onepress-plus' ),
                            'label' => esc_attr__( 'Choose Plan', 'onepress-plus' ),
                            'link' => '#',
                            'button' => 'btn-theme-primary',
                        ),

                    )
                ),
                'sanitize_callback' => 'onepress_sanitize_repeatable_data_field',
                'transport' => 'refresh', // refresh or postMessage
            ) );


            $wp_customize->add_control(
                new Onepress_Customize_Repeatable_Control(
                    $wp_customize,
                    'onepress_pricing_plans',
                    array(
                        'label'     	=> esc_html__('Pricing Plans', 'onepress-plus'),
                        'description'   => '',
                        'section'       => 'onepress_pricing_content',
                        'live_title_id' => 'title', // apply for unput text and textarea only
                        'title_format'  => esc_html__('[live_title]', 'onepress-plus'), // [live_title]
                        'max_item'      => 4, // Maximum item can add

                        'fields'    => array(
                            'title' => array(
                                'title' => esc_html__('Title', 'onepress-plus'),
                                'type'  =>'text',
                                'desc'  => '',
                                'default' => esc_html__( 'Your service title', 'onepress-plus' ),
                            ),
                            'price' => array(
                                'title' => esc_html__('Price', 'onepress-plus'),
                                'type'  =>'text',
                                'default' => esc_html__( '99', 'onepress-plus' ),
                            ),
                            'code' => array(
                                'title' => esc_html__('Currency code', 'onepress-plus'),
                                'type'  =>'text',
                                'default' => esc_html__( '$', 'onepress-plus' ),
                            ),
                            'subtitle' => array(
                                'title' => esc_html__('Subtitle', 'onepress-plus'),
                                'type'  =>'text',
                                'desc'  => '',
                                'default' => esc_html__( 'Lorem ipsum dolor sit amet, consectetuer adipiscing elit', 'onepress-plus' ),
                            ),
                            'content'  => array(
                                'title' => esc_html__('Option list', 'onepress-plus'),
                                'desc'  => esc_html__('Earch option per line', 'onepress-plus'),
                                'type'  =>'textarea',
                                'default' => esc_html__( "Option 1\n Option 2\n Option 3\n Option 4", 'onepress-plus' ),
                            ),
                            'label' => array(
                                'title' => esc_html__('Button label', 'onepress-plus'),
                                'type'  =>'text',
                                'desc'  => '',
                                'default' =>  esc_html__('Choose Plan', 'onepress-plus'),
                            ),
                            'link' => array(
                                'title' => esc_html__('Button Link', 'onepress-plus'),
                                'type'  =>'text',
                                'desc'  => '',
                                'default' => '#',
                            ),
                            'button'  => array(
                                'title' => esc_html__('Button style', 'onepress-plus'),
                                'type'  =>'select',
                                'options' => array(
                                    'btn-theme-primary' => esc_html__('Theme default', 'onepress-plus'),
                                    'btn-default' => esc_html__('Button', 'onepress-plus'),
                                    'btn-primary' => esc_html__('Primary', 'onepress-plus'),
                                    'btn-success' => esc_html__('Success', 'onepress-plus'),
                                    'btn-info' => esc_html__('Info', 'onepress-plus'),
                                    'btn-warning' => esc_html__('Warning', 'onepress-plus'),
                                    'btn-danger' => esc_html__('Danger', 'onepress-plus'),
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

        $wp_customize->add_panel( 'onepress_cta_panel' ,
            array(
                'priority'        => 240,
                'title'           => __( 'Section: Call to Action', 'onepress-plus' ),
                'description'     => '',
                'active_callback' => 'onepress_showon_frontpage'
            )
        );

        $wp_customize->add_section( 'onepress_cta_settings' ,
            array(
                'priority'    => 3,
                'title'       => __( 'Section Settings', 'onepress-plus' ),
                'panel'       => 'onepress_cta_panel',
            )
        );


        // Section ID
        $wp_customize->add_setting( 'onepress_cta_id',
            array(
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => 'section-cta',
            )
        );
        $wp_customize->add_control( 'onepress_cta_id',
            array(
                'label' 		=> __('Section ID', 'onepress-plus'),
                'section' 		=> 'onepress_cta_settings',
            )
        );

       // Title
       $wp_customize->add_setting( 'onepress_cta_title',
           array(
               'sanitize_callback' => 'onepress_sanitize_text',
               'default'           => __( 'Use these ribbons to display calls to action mid-page.' , 'onepress-plus' ),
           )
       );
       $wp_customize->add_control( 'onepress_cta_title',
           array(
               'label' 		=> __('Title', 'onepress-plus'),
               'section' 		=> 'onepress_cta_settings',
           )
       );

       // Button label
       $wp_customize->add_setting( 'onepress_cta_btn_label',
           array(
               'sanitize_callback' => 'onepress_sanitize_text',
               'default'           => __( 'Button Text' , 'onepress-plus' ),
           )
       );
       $wp_customize->add_control( 'onepress_cta_btn_label',
           array(
               'label' 		=> __('Button Text', 'onepress-plus'),
               'section' 		=> 'onepress_cta_settings',
           )
       );

       // Button link
       $wp_customize->add_setting( 'onepress_cta_btn_link',
           array(
               'sanitize_callback' => 'onepress_sanitize_text',
               'default'           => '',
           )
       );
       $wp_customize->add_control( 'onepress_cta_btn_link',
           array(
               'label' 		=> __('Button Link', 'onepress-plus'),
               'section' 		=> 'onepress_cta_settings',
           )
       );

        // Button link style
        $wp_customize->add_setting( 'onepress_cta_btn_link_style',
            array(
                'sanitize_callback' => 'onepress_sanitize_text',
                'default'           => 'theme-primary',
            )
        );
        $wp_customize->add_control( 'onepress_cta_btn_link_style',
            array(
                'label' 		=> __('Button Link Style', 'onepress-plus'),
                'section' 		=> 'onepress_cta_settings',
                'type'          => 'select',
                'choices'       => array(

                    'theme-primary' => esc_html__( 'Theme default', 'onepress-plus' ),
                    'btn-primary' => esc_html__( 'Primary', 'onepress-plus' ),
                    'btn-secondary' => esc_html__( 'Secondary', 'onepress-plus' ),
                    'btn-success' => esc_html__( 'Success', 'onepress-plus' ),
                    'btn-info' => esc_html__( 'Info', 'onepress-plus' ),
                    'btn-warning' => esc_html__( 'Warning', 'onepress-plus' ),
                    'btn-danger' => esc_html__( 'Danger', 'onepress-plus' ),

                    'btn-outline-primary' => esc_html__( 'Outline Primary', 'onepress-plus' ),
                    'btn-outline-secondary' => esc_html__( 'Outline Secondary', 'onepress-plus' ),
                    'btn-outline-success' => esc_html__( 'Outline Success', 'onepress-plus' ),
                    'btn-outline-info' => esc_html__( 'Outline Info', 'onepress-plus' ),
                    'btn-outline-warning' => esc_html__( 'Outline Warning', 'onepress-plus' ),
                    'btn-outline-danger' => esc_html__( 'Outline Danger', 'onepress-plus' ),

                )

            )
        );

       // EN Add cta


        /*------------------------------------------------------------------------*/
        /*  Section: Clients
        /*------------------------------------------------------------------------*/

        $wp_customize->add_panel( 'onepress_clients_panel' ,
            array(
                'priority'        => 140,
                'title'           => __( 'Section: Clients', 'onepress-plus' ),
                'description'     => '',
                'active_callback' => 'onepress_showon_frontpage'
            )
        );

        $wp_customize->add_section( 'onepress_clients_settings' ,
            array(
                'priority'    => 3,
                'title'       => __( 'Section Settings', 'onepress-plus' ),
                'panel'       => 'onepress_clients_panel',
            )
        );


        // Section ID
        $wp_customize->add_setting( 'onepress_clients_id',
            array(
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => 'clients',
            )
        );
        $wp_customize->add_control( 'onepress_clients_id',
            array(
                'label' 		=> __('Section ID', 'onepress-plus'),
                'section' 		=> 'onepress_clients_settings',
            )
        );

        // Title
        $wp_customize->add_setting( 'onepress_clients_title',
            array(
                'sanitize_callback' => 'onepress_sanitize_text',
                'default'           => '',
            )
        );
        $wp_customize->add_control( 'onepress_clients_title',
            array(
                'label' 		=> __('Title', 'onepress-plus'),
                'section' 		=> 'onepress_clients_settings',
            )
        );


        // clients subtitle
        $wp_customize->add_setting( 'onepress_clients_subtitle',
            array(
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => __( 'Have been featured on', 'onepress-plus' ),
            )
        );
        $wp_customize->add_control( 'onepress_clients_subtitle',
            array(
                'label' 		=> __('Some of our works', 'onepress-plus'),
                'section' 		=> 'onepress_clients_settings',
                'description'   => '',
            )
        );

        // Services layout
        $wp_customize->add_setting( 'onepress_clients_layout',
            array(
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => 5,
            )
        );

        $wp_customize->add_control( 'onepress_clients_layout',
            array(
                'label' 		=> esc_html__('Clients Layout Setting', 'onepress-plus'),
                'section' 		=> 'onepress_clients_settings',
                'description'   => '',
                'type'          => 'select',
                'choices'       => array(
                    '2' => esc_html__( '2 Columns', 'onepress-plus' ),
                    '3' => esc_html__( '3 Columns', 'onepress-plus' ),
                    '4' => esc_html__( '4 Columns', 'onepress-plus' ),
                    '5' => esc_html__( '5 Columns', 'onepress-plus' ),
                    '6' => esc_html__( '6 Columns', 'onepress-plus' ),
                ),
            )
        );

        // Description
        $wp_customize->add_setting( 'onepress_clients_desc',
            array(
                'sanitize_callback' => 'onepress_sanitize_text',
                'default'           => '',
            )
        );
        $wp_customize->add_control( new OnePress_Editor_Custom_Control(
            $wp_customize,
            'onepress_clients_desc',
            array(
                'label' 		=> esc_html__('Section Description', 'onepress-plus'),
                'section' 		=> 'onepress_clients_settings',
                'description'   => '',
            )
        ));


        // Section content
        $wp_customize->add_section( 'onepress_clients_content' ,
            array(
                'priority'    => 3,
                'title'       => __( 'Section Content', 'onepress-plus' ),
                'panel'       => 'onepress_clients_panel',
            )
        );
        $wp_customize->add_setting(
            'onepress_clients',
            array(
                'default' => json_encode(
                    array(
                        array(
                            'title' => esc_html__( 'Hostingco', 'onepress-plus' ),
                            'image'  => array(
                                'id'=> '',
                                'url'=> ONEPRESS_PLUS_URL.'assets/images/client_logo_1.png',
                            ),
                            'link' => ''
                        ),
                        array(
                            'title' => esc_html__( 'Religion', 'onepress-plus' ),
                            'image'  => array(
                                'id'=> '',
                                'url'=> ONEPRESS_PLUS_URL.'assets/images/client_logo_2.png',
                            ),
                            'link' => ''
                        ),
                        array(
                            'title' => esc_html__( 'Viento', 'onepress-plus' ),
                            'image'  => array(
                                'id'=> '',
                                'url'=> ONEPRESS_PLUS_URL.'assets/images/client_logo_3.png',
                            ),
                            'link' => ''
                        ),
                        array(
                            'title' => esc_html__( 'Naturefirst', 'onepress-plus' ),
                            'image'  => array(
                                'id'=> '',
                                'url'=> ONEPRESS_PLUS_URL.'assets/images/client_logo_4.png',
                            ),
                            'link' => ''
                        ),
                        array(
                            'title' => esc_html__( 'Imagine', 'onepress-plus' ),
                            'image'  => array(
                                'id'=> '',
                                'url'=> ONEPRESS_PLUS_URL.'assets/images/client_logo_5.png',
                            ),
                            'link' => ''
                        ),

                    )
                ),
                'sanitize_callback' => 'onepress_sanitize_repeatable_data_field',
                'transport' => 'refresh', // refresh or postMessage
            ) );


        $wp_customize->add_control(
            new Onepress_Customize_Repeatable_Control(
                $wp_customize,
                'onepress_clients',
                array(
                    'label'     	=> esc_html__('Clients', 'onepress-plus'),
                    'description'   => '',
                    'section'       => 'onepress_clients_content',
                    'live_title_id' => 'title', // apply for unput text and textarea only
                    'title_format'  => esc_html__('[live_title]', 'onepress-plus'), // [live_title]
                    'max_item'      => 4, // Maximum item can add

                    'fields'    => array(
                        'title' => array(
                            'title' => esc_html__('Client name', 'onepress-plus'),
                            'type'  =>'text',
                            'desc'  => '',
                            'default' => esc_html__( 'My Client', 'onepress-plus' ),
                        ),
                        'image' => array(
                            'title' => esc_html__('Image', 'onepress-plus'),
                            'type'  =>'media',
                            'default' => array(
                                'id'=> '',
                                'url'=> ONEPRESS_PLUS_URL.'assets/images/client_logo_1.png',
                            ),
                        ),
                        'link' => array(
                            'title' => esc_html__('link', 'onepress-plus'),
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
        $wp_customize->add_setting( 'onepress_gallery_source_facebook',
            array(
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => '',
            )
        );
        $wp_customize->add_control( 'onepress_gallery_source_facebook',
            array(
                'label'     	=> esc_html__('Facebook Fan Page Album', 'onepress'),
                'priority'      => 15,
                'section' 		=> 'onepress_gallery_content',
                'description'   => esc_html__('Enter Facebook fan page album ID or album URL here. Your album should publish to load data.', 'onepress'),
            )
        );

        // Source flickr API settings
        $wp_customize->add_setting( 'onepress_gallery_api_facebook',
            array(
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => '',
            )
        );
        $wp_customize->add_control( 'onepress_gallery_api_facebook',
            array(
                'label'     	=> esc_html__('Facebook API', 'onepress'),
                'section' 		=> 'onepress_gallery_content',
                'priority'      => 20,
                'description'   => sprintf( esc_html__('Paste your Facebook Token here, example: {App_ID}|{App_Secret}. Click %1$s to create an app.', 'onepress'), '<a target="_blank" href="https://developers.facebook.com/apps/">'.esc_html( 'here', 'onepress' ).'</a>' ),
            )
        );

        // Source flickr settings
        $wp_customize->add_setting( 'onepress_gallery_source_flickr',
            array(
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => '',
            )
        );
        $wp_customize->add_control( 'onepress_gallery_source_flickr',
            array(
                'label'     	=> esc_html__('Flickr Username or ID', 'onepress'),
                'section' 		=> 'onepress_gallery_content',
                'priority'      => 25,
                'description'   => esc_html__('Flickr Username or ID here, Required Flickr API.', 'onepress'),
            )
        );

        // Source flickr API settings
        $wp_customize->add_setting( 'onepress_gallery_api_flickr',
            array(
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => '',
            )
        );
        $wp_customize->add_control( 'onepress_gallery_api_flickr',
            array(
                'label'     	=> esc_html__('Flickr API key', 'onepress'),
                'section' 		=> 'onepress_gallery_content',
                'priority'      => 30,
                'description'   => esc_html__('Paste your Flickr API key here.', 'onepress'),
            )
        );


        // Source instagram settings
        $wp_customize->add_setting( 'onepress_gallery_source_instagram',
            array(
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => '',
            )
        );
        $wp_customize->add_control( 'onepress_gallery_source_instagram',
            array(
                'label'     	=> esc_html__('Instagram Username', 'onepress'),
                'section' 		=> 'onepress_gallery_content',
                'priority'      => 35,
                'description'   => esc_html__('Enter your Instagram username here.', 'onepress'),
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
        $sections = get_theme_mod( 'onepress_section_order_styling', '');
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
                if ( get_theme_mod( 'onepress_map_enable_api' ) ) {
                    $key = get_theme_mod( 'onepress_map_api_key' );
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

        wp_enqueue_style( 'onepress-style' );
        wp_register_style( 'onepress-plus-style', ONEPRESS_PLUS_URL.'onepress-plus.css', array( 'onepress-style' ), ONEPRESS_PLUS_VERSION );
        wp_enqueue_style( 'onepress-plus-style' );

        /**
         * Plugin style
         */
        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( 'onepress-plus', ONEPRESS_PLUS_URL.'assets/js/onepress-plus.js', array( 'jquery', 'onepress-theme' ), ONEPRESS_PLUS_VERSION, true );
        wp_localize_script( 'jquery' , 'OnePress_Plus', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'browser_warning'=> esc_html__( ' Your browser does not support the video tag. I suggest you upgrade your browser.', 'onepress-plus' )
        ) );
    }

    /**
     * Print CSS in header tag
     */
    function custom_css(){
        if ( $this->custom_css ) {
            wp_add_inline_style( 'onepress-style', $this->custom_css  );
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
            <section id="<?php if ( $section['section_id'] != '' ) echo esc_attr( $section['section_id'] ); ?>" <?php do_action( 'onepress_section_atts', $section['section_id'] ); ?> class="<?php echo esc_attr( apply_filters( 'onepress_section_class', 'section-'.$section['section_id'].' onepage-section section-meta section-padding', $section['section_id'] ) ); ?>">
                <?php do_action( 'onepress_section_before_inner', $section['section_id'] ); ?>
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
                <?php do_action( 'onepress_section_after_inner', $section['section_id'] ); ?>
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
        do_action('onepress_before_section_hero' );
        do_action( 'onepress_before_section_part', 'hero' );

        $this->locate_template('section-parts/section-hero.php', true, false );

        /**
         * Hook after section
         */
        do_action('onepress_after_section_part', 'hero' );
        do_action('onepress_after_section_hero' );


        if ( is_array( $sections ) ) {
            add_filter( 'onepress_section_class', array( $this, 'filter_section_class' ), 15, 2 );
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
                add_filter( 'theme_mod_onepress_'.$section['section_id'].'_disable', '__return_false', 99 );
                // If disabled section the code this line below will handle this
                if ( $section['show_section'] ) {
                    if ( $section['section_id'] != '' ) {
                        do_action('onepress_before_section_'.$section['section_id'] );
                        do_action('onepress_before_section_part', $section['section_id'] );

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


                        do_action('onepress_after_section_part', $section['section_id']);
                        do_action('onepress_after_section_'.$section['section_id'] );
                    }
                }
            }
            remove_filter( 'onepress_section_class', array( $this, 'filter_section_class' ), 15, 2 );
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

            } elseif ( file_exists( ONEPRESS_PLUS_PATH  . $template_name ) ) { // Check part in the plugin
                $located = ONEPRESS_PLUS_PATH . $template_name;
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
function onepress_plus_setup(){
    new OnePress_PLus();
}
add_action( 'plugins_loaded', 'onepress_plus_setup' );
