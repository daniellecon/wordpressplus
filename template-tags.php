<?php
function onepress_plus_team_member_socials( $member ){
    $member = wp_parse_args(
        $member,
        array(
            'url' => '',
            'facebook' => '',
            'twitter' => '',
            'google_plus' => '',
            'linkedin' => '',
            'email' => '',
        )
    );
    ?>
    <div class="member-profile">
        <?php if ( $member['url'] != '' ) { ?>
            <a href="<?php echo esc_url( $member['url'] ); ?>"><span class="fa-stack"><i class="fa fa-circle fa-stack-2x"></i><i class="fa fa-globe fa-stack-1x fa-inverse"></i></span></a>
        <?php } ?>
        <?php if ( $member['twitter'] != '' ) { ?>
            <a href="<?php echo esc_url( $member['twitter'] ); ?>"><span class="fa-stack"><i class="fa fa-circle fa-stack-2x"></i><i class="fa fa-twitter fa-stack-1x fa-inverse"></i></span></a>
        <?php } ?>
        <?php if (  $member['facebook'] != '' ) { ?>
            <a href="<?php echo esc_url( $member['facebook'] ); ?>"><span class="fa-stack"><i class="fa fa-circle fa-stack-2x"></i><i class="fa fa-facebook fa-stack-1x fa-inverse"></i></span></a>
        <?php } ?>
        <?php if ( $member['google_plus'] != '' ) { ?>
            <a href="<?php echo esc_url($member['google_plus']); ?>"><span class="fa-stack"><i class="fa fa-circle fa-stack-2x"></i><i class="fa fa-google-plus fa-stack-1x fa-inverse"></i></span></a>
        <?php } ?>
        <?php if ( $member['linkedin'] != '' ) { ?>
            <a href="<?php echo esc_url($member['linkedin']); ?>"><span class="fa-stack"><i class="fa fa-circle fa-stack-2x"></i><i class="fa fa-linkedin fa-stack-1x fa-inverse"></i></span></a>
        <?php } ?>

        <?php if ( $member['email'] != '' ) { ?>
            <a  href="mailto:<?php echo antispambot( $member['email'] ) ?>"><span class="fa-stack"><i class="fa fa-circle fa-stack-2x"></i><i class="fa fa-envelope-o fa-stack-1x fa-inverse"></i></span></a>
        <?php } ?>
    </div>
    <?php
}

add_action( 'onepress_section_team_member_media', 'onepress_plus_team_member_socials' );

/**
 * Add docs links
 */
function onepress_plus_dashboard_theme_links(){
    ?>
    <p>
        <a href="http://docs.famethemes.com/category/50-onepress-plus" target="_blank" class="button button-primary"><?php esc_html_e('OnePress Plus Documentation', 'onepress-plus'); ?></a>
    </p>
    <?php
}
add_action( 'onepress_dashboard_theme_links', 'onepress_plus_dashboard_theme_links' );

/**
 * Change theme footer info
 */
function onepress_plus_add_theme_footer_info(){
    $c =  get_theme_mod( 'onepress_footer_copyright_text', sprintf( esc_html__( 'Copyright %1$s %2$s %3$s', 'onepress-plus' ), '&copy;', esc_attr( date( 'Y' ) ), esc_attr( get_bloginfo() ) ) );
    $d = get_theme_mod( 'onepress_hide_author_link' );
    if ( ! $d ) {
        if ( $c ) {
            $c.= '<span class="sep"> &ndash; </span>';
        }
        $c.= sprintf(esc_html__('%1$s theme by %2$s', 'onepress-plus'), '<a href="' . esc_url('https://www.famethemes.com/themes/onepress', 'onepress-plus') . '">OnePress</a>', 'FameThemes');
    }
    echo $c;
}

/**
 * Chang theme footer info
 *
 * @todo Remove default theme hook
 * @todo Add new plugin hook
 */
function onepress_plus_change_theme_footer_info(){
    remove_action( 'onepress_footer_site_info', 'onepress_footer_site_info' );
    add_action( 'onepress_footer_site_info', 'onepress_plus_add_theme_footer_info' );
}
add_action( 'wp_loaded', 'onepress_plus_change_theme_footer_info'  );

if ( ! function_exists( 'onepress_is_selective_refresh' ) ) {
    function onepress_is_selective_refresh()
    {
        return isset($GLOBALS['onepress_is_selective_refresh']) && $GLOBALS['onepress_is_selective_refresh'] ? true : false;
    }
}

// based on https://gist.github.com/cosmocatalano/4544576
if ( ! function_exists( 'onepress_scrape_instagram' ) ) {
    function onepress_scrape_instagram( $username )
    {
        $username = strtolower($username);
        $username = str_replace('@', '', $username);

        $remote = wp_remote_get('http://instagram.com/' . trim($username));
        if ( is_wp_error( $remote ) ) {
            return false;
        }
        if ( 200 != wp_remote_retrieve_response_code($remote) ) {
            return false;
        }

        $shards = explode('window._sharedData = ', $remote['body']);
        $insta_json = explode(';</script>', $shards[1]);
        $insta_array = json_decode($insta_json[0], TRUE);
        if ( ! $insta_array )
            return  false;
        if (isset($insta_array['entry_data']['ProfilePage'][0]['user']['media']['nodes'])) {
            $images = $insta_array['entry_data']['ProfilePage'][0]['user']['media']['nodes'];
        } else {
            return false;
        }
        if ( ! is_array( $images ) ) {
            return false;
        }

        $instagram = array();
        foreach ($images as $image) {
            $image['thumbnail_src'] = preg_replace('/^https?\:/i', '', $image['thumbnail_src']);
            $image['display_src'] = preg_replace('/^https?\:/i', '', $image['display_src']);
            // handle both types of CDN url
            if ((strpos($image['thumbnail_src'], 's640x640') !== false)) {
                $image['thumbnail'] = str_replace('s640x640', 's160x160', $image['thumbnail_src']);
                $image['small'] = str_replace('s640x640', 's320x320', $image['thumbnail_src']);
            } else {
                $urlparts = wp_parse_url($image['thumbnail_src']);
                $pathparts = explode('/', $urlparts['path']);
                array_splice($pathparts, 3, 0, array('s160x160'));
                $image['thumbnail'] = '//' . $urlparts['host'] . implode('/', $pathparts);
                $pathparts[3] = 's320x320';
                $image['small'] = '//' . $urlparts['host'] . implode('/', $pathparts);
            }
            $image['large'] = $image['thumbnail_src'];
            if ($image['is_video'] == true) {
                $type = 'video';
            } else {
                $type = 'image';
            }
            $caption = esc_html__('Instagram Image', 'onepress');
            if (!empty($image['caption'])) {
                $caption = $image['caption'];
            }
            $instagram[] = array(
                'title' => $caption,
                'link' => trailingslashit('//instagram.com/p/' . $image['code']),
                'time' => $image['date'],
                'comments' => $image['comments']['count'],
                'likes' => $image['likes']['count'],
                //'thumbnail' => $image['thumbnail'],
                'thumbnail' => $image['large'],
                'small' => $image['small'],
                //'full' => $image['large'],
                'full' => $image['display_src'],
                'original' => $image['display_src'],
                'type' => $type
            );
        }
        return $instagram;
    }
}


if ( ! function_exists( 'onepress_plus_get_section_gallery_data' ) ) {
    /**
     * Get Gallery data
     *
     * @since 1.2.6
     *
     * @return array
     */
    function onepress_plus_get_section_gallery_data()
    {

        $source = get_theme_mod( 'onepress_gallery_source', 'page' );
        $data =  apply_filters( 'onepress_plus_get_section_gallery_data', false );
        if ( $data ) {
            return $data;
        }
        $data = array();
        $number_item = 100;

        $transient_expired = 6 * HOUR_IN_SECONDS;

        switch ( $source ) {
            case 'instagram':

                //Example:  https://www.instagram.com/taylorswift/media/
                $user_id = wp_strip_all_tags( get_theme_mod( 'onepress_gallery_source_instagram' ) );
                if ( ! $user_id ) {
                    return $data;
                }
                // Check cache
                //delete_transient( 'onepress_gallery_'.$source.'_'.$user_id );
                $data = get_transient( 'onepress_gallery_'.$source.'_'.$user_id.$number_item );
                if ( false !== $data && is_array( $data ) ) {
                    return $data;
                }
                $data = onepress_scrape_instagram( $user_id );

                if ( ! empty( $data ) ) {
                    set_transient('onepress_gallery_' . $source . '_' . $user_id.$number_item, $data, $transient_expired);
                } else {
                    delete_transient( 'onepress_gallery_'.$source.'_'.$user_id.$number_item );
                }

                break;
            case 'flickr':

                $api_key = get_theme_mod( 'onepress_gallery_api_flickr', 'a68c0befe246035b74a8f67943da7edc' );
                if ( ! $api_key ) {
                    return $data;
                }
                $user_id = wp_strip_all_tags( get_theme_mod( 'onepress_gallery_source_flickr' ) );
                if ( ! $user_id ) {
                    return $data;
                }

                // Check cache
                $data = get_transient( 'onepress_gallery_'.$source.'_'.$user_id.$number_item );
                if ( false !== $data && is_array( $data ) ) {
                    return $data;
                }

                $flickr_api_url = 'https://api.flickr.com/services/rest/';
                // @see https://www.flickr.com/services/api/explore/flickr.people.getPhotos
                $url = add_query_arg( array(
                    'method' => 'flickr.people.getPhotos',
                    'api_key' => $api_key,
                    'user_id' => $user_id,
                    'per_page' => $number_item,
                    'format' => 'json',
                    'nojsoncallback' => '1',
                ), $flickr_api_url );

                $res = wp_remote_get( $url );
                if ( wp_remote_retrieve_response_code( $res ) == 200 ) {
                    $res_data = wp_remote_retrieve_body( $res );
                    $res_data = json_decode( $res_data, true );
                    if ( $res_data['stat'] == 'ok' && $res_data['photos']['photo'] ) {

                        foreach ( $res_data['photos']['photo'] as $k => $photo ) {
                            $image_get_url = add_query_arg( array(
                                'method' => 'flickr.photos.getSizes',
                                'api_key' => $api_key,
                                'photo_id' => $photo['id'],
                                'format' => 'json',
                                'nojsoncallback' => '1',
                            ), $flickr_api_url );

                            $img_res = wp_remote_get( $image_get_url );
                            if ( wp_remote_retrieve_response_code( $img_res ) == 200 ) {
                                $img_res = wp_remote_retrieve_body($img_res);
                                $img_res = json_decode($img_res, true);
                                if( isset( $img_res['sizes'] ) && $img_res['stat'] == 'ok' ) {

                                    $img_full = false;
                                    $tw = 0;
                                    $images = array();
                                    foreach ( $img_res['sizes']['size'] as $img ){
                                        if ( $tw < $img['width'] ) {
                                            $tw = $img['width'];
                                            $img_full = $img['source'];
                                        }
                                        $images[ $img['label'] ] = $img['source'];
                                    }

                                    $data[$photo['id']] = array(
                                        'id' => $photo['id'],
                                        'thumbnail' => $img_full,
                                        'full' => $img_full,
                                        'sizes' => $images,
                                        'title' => $photo['title'],
                                        'content' => ''
                                    );
                                }
                            }
                        }
                    }
                }

                if ( ! empty( $data ) ) {
                    set_transient( 'onepress_gallery_'.$source.'_'.$user_id.$number_item, $data, $transient_expired );
                } else {
                    delete_transient( 'onepress_gallery_'.$source.'_'.$user_id.$number_item );
                }


                break;
            case 'facebook':
                $album_id = false;
                $album_url = get_theme_mod( 'onepress_gallery_source_facebook', '' );
                preg_match( '/a\.(.*?)\.(.*?)/', $album_url, $arr );
                if ( $arr ) {
                    $album_id = $arr[1];
                }

                if ( ! $album_id ) {
                    $tpl = explode( "album_id",  $album_url );
                    $album_id = end( $tpl );
                    $album_id = str_replace( '=', '', $album_id );
                }

                if ( ! $album_id ) {
                    $tpl = explode( "album_id",  $album_url );
                    $album_id = end( $tpl );
                    $album_id = str_replace( '=', '', $album_id );
                }

                if ( ! $album_id ) {
                    return false;
                }
                $token = get_theme_mod( 'onepress_gallery_api_facebook', '1813325532276774|c0e7681c4a5727a6ca5d31020d0b44b0' );
                if ( ! $token ) {
                    return false;
                }

                // Check cache
                $data = get_transient( 'onepress_gallery_'.$source.'_'.$album_id.$number_item );
                if ( false !== $data && is_array( $data ) ) {
                    return $data;
                }

                $url = 'https://graph.facebook.com/v2.7/'.$album_id;
                $url = add_query_arg( array(
                    'fields' => 'photos.limit('.$number_item.'){images,link,name,picture,width}',
                    'access_token' => $token ,
                ), $url );

                $res = wp_remote_get( $url );

                if ( wp_remote_retrieve_response_code( $res ) == 200 ) {
                    $res_data = wp_remote_retrieve_body( $res );
                    $res_data = json_decode( $res_data, true );

                    if ( isset( $res_data['photos'] ) && isset( $res_data['photos']['data'] ) ) {
                        foreach ( $res_data['photos']['data'] as $k => $photo ) {

                            $img_full = false;
                            $tw = 0;
                            foreach ( $photo['images'] as $img ){
                                if ( $tw < $img['width'] ) {
                                    $tw = $img['width'];
                                    $img_full = $img['source'];
                                }
                            }
                            $data[ $photo['id'] ] = array(
                                'id'        => $photo['id'],
                                'thumbnail' => $img_full,
                                'full'      => $img_full,
                                'title'     => isset( $photo['name'] ) ? $photo['name'] : '',
                                'content'  => ''
                            );
                        }

                    }
                }

                if ( ! empty( $data ) ) {
                    set_transient('onepress_gallery_' . $source . '_' . $album_id.$number_item, $data, $transient_expired);
                } else {
                    delete_transient( 'onepress_gallery_'.$source.'_'.$album_id.$number_item );
                }

                break;
            case "page":
                $page_id = get_theme_mod( 'onepress_gallery_source_page' );
                $images = '';
                if ( $page_id ) {
                    $gallery = get_post_gallery( $page_id , false );
                    if ( $gallery ) {
                        $images = $gallery['ids'];
                    }
                }

                $display_type = get_theme_mod( 'onepress_gallery_display', 'grid' );
                if ( $display_type == 'masonry' || $display_type == ' justified' ) {
                    $size = 'large';
                } else {
                    $size = 'onepress-small';
                }

                $image_thumb_size = apply_filters( 'onepress_gallery_page_img_size', $size );

                if ( ! empty( $images ) ) {
                    $images = explode( ',', $images );
                    foreach ( $images as $post_id ) {
                        $post = get_post( $post_id );
                        if ( $post ) {
                            $img_thumb = wp_get_attachment_image_src($post_id, $image_thumb_size );
                            if ($img_thumb) {
                                $img_thumb = $img_thumb[0];
                            }

                            $img_full = wp_get_attachment_image_src($post_id, 'full');
                            if ($img_full) {
                                $img_full = $img_full[0];
                            }

                            if ( $img_thumb && $img_full ) {
                                $data[ $post_id ] = array(
                                    'id'        => $post_id,
                                    'thumbnail' => $img_thumb,
                                    'full'      => $img_full,
                                    'title'     => $post->post_title,
                                    'content'   => $post->post_content,
                                );
                            }
                        }
                    }
                }

                break;
        }

        return $data;

    }
}

add_filter( 'onepress_get_section_gallery_data', 'onepress_plus_get_section_gallery_data' );



