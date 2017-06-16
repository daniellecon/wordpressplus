<?php
add_action( 'init', 'codex_portfolio_init' );
/**
 * Register a portfolio post type.
 *
 * @link http://codex.wordpress.org/Function_Reference/register_post_type
 */
function codex_portfolio_init() {

    $slug  = get_theme_mod( 'sovenco_project_slug', 'portfolio' );
    if ( ! $slug ) {
        $slug = 'portfolio';
    }

    $labels = array(
        'name'               => _x( 'Portfolios', 'post type general name', 'sovenco-plus' ),
        'singular_name'      => _x( 'Portfolio', 'post type singular name', 'sovenco-plus' ),
        'menu_name'          => _x( 'Portfolios', 'admin menu', 'sovenco-plus' ),
        'name_admin_bar'     => _x( 'Portfolio', 'add new on admin bar', 'sovenco-plus' ),
        'add_new'            => _x( 'Add New', 'portfolio', 'sovenco-plus' ),
        'add_new_item'       => __( 'Add New Portfolio', 'sovenco-plus' ),
        'new_item'           => __( 'New Portfolio', 'sovenco-plus' ),
        'edit_item'          => __( 'Edit Portfolio', 'sovenco-plus' ),
        'view_item'          => __( 'View Portfolio', 'sovenco-plus' ),
        'all_items'          => __( 'All Portfolios', 'sovenco-plus' ),
        'search_items'       => __( 'Search Portfolios', 'sovenco-plus' ),
        'parent_item_colon'  => __( 'Parent Portfolios:', 'sovenco-plus' ),
        'not_found'          => __( 'No portfolios found.', 'sovenco-plus' ),
        'not_found_in_trash' => __( 'No portfolios found in Trash.', 'sovenco-plus' )
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array( 'slug' => $slug ),
        'capability_type'    => 'post',
        'has_archive'        => false,
        'hierarchical'       => false,
        'menu_position'      => null,
        'supports'           => array( 'title', 'editor', 'thumbnail' )
    );

    register_post_type( 'portfolio', $args );

    // Portfolio category
    $labels = array(
        'name'                       => _x( 'Categories', 'taxonomy general name', 'sovenco-plus' ),
        'singular_name'              => _x( 'Category', 'taxonomy singular name', 'sovenco-plus' ),
        'search_items'               => __( 'Search Categories', 'sovenco-plus' ),
        'popular_items'              => __( 'Popular Categories', 'sovenco-plus' ),
        'all_items'                  => __( 'All Categories', 'sovenco-plus' ),
        'parent_item'                => null,
        'parent_item_colon'          => null,
        'edit_item'                  => __( 'Edit Category', 'sovenco-plus' ),
        'update_item'                => __( 'Update Category', 'sovenco-plus' ),
        'add_new_item'               => __( 'Add New Category', 'sovenco-plus' ),
        'new_item_name'              => __( 'New Category Name', 'sovenco-plus' ),
        'separate_items_with_commas' => __( 'Separate categories with commas', 'sovenco-plus' ),
        'add_or_remove_items'        => __( 'Add or remove categories', 'sovenco-plus' ),
        'choose_from_most_used'      => __( 'Choose from the most used categories', 'sovenco-plus' ),
        'not_found'                  => __( 'No categories found.', 'sovenco-plus' ),
        'menu_name'                  => __( 'Categories', 'sovenco-plus' ),
    );

    $args = array(
        'hierarchical'          => false,
        'labels'                => $labels,
        'show_ui'               => true,
        'show_admin_column'     => true,
        'update_count_callback' => '_update_post_term_count',
        'query_var'             => false,
        'rewrite'               => array( 'slug' => 'portfolio_cat' ),
    );

    register_taxonomy( 'portfolio_cat', 'portfolio', $args );

}


