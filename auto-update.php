<?php

// this is the URL our updater / license checker pings. This should be the URL of the site with EDD installed
define( 'EDD_ONEPRESS_PLUS_STORE_URL', 'https://www.famethemes.com' ); // you should use your own CONSTANT name, and be sure to replace it throughout this file

// the name of your product. This should match the download name in EDD exactly
define( 'EDD_ONEPRESS_PLUS_ITEM_NAME', 'OnePress Plus' ); // you should use your own CONSTANT name, and be sure to replace it throughout this file

if( !class_exists( 'EDD_SL_Plugin_Updater' ) ) {
	// load our custom updater
	include( dirname( __FILE__ ) . '/EDD_SL_Plugin_Updater.php' );
}


function onepress_plus_add_admin_tabs(){
    // Check for current viewing tab
    $tab = null;
    if ( isset( $_GET['tab'] ) ) {
        $tab = $_GET['tab'];
    } else {
        $tab = null;
    }
    ?>
    <a href="?page=ft_onepress&tab=auto_update" class="nav-tab<?php echo $tab == 'auto_update' ? ' nav-tab-active' : null; ?>"><?php esc_html_e( 'OnePress Plus License', 'onepress-plus' ); ?></a>
    <?php
}
add_action( 'onepress_admin_more_tabs', 'onepress_plus_add_admin_tabs' );

function onepress_plus_more_tabs_details( $details ){

    $tab = null;
    if ( isset( $_GET['tab'] ) ) {
        $tab = $_GET['tab'];
    } else {
        $tab = null;
    }

    if ( $tab != 'auto_update' ) {
        return ;
    }

    $error = '';

    if ( isset ( $_REQUEST['edd_onepress_plus_license_key'] ) ) {
        update_option( 'edd_onepress_plus_license_key', trim( $_REQUEST['edd_onepress_plus_license_key'] ) );
    }

    if( isset( $_POST['edd_license_activate'] ) || ( isset( $_POST['submit'] ) ) ) {
        $error = edd_onepress_plus_activate_license();
    }

    if( isset( $_POST['edd_license_deactivate'] ) ) {
        $error = edd_onepress_plus_deactivate_license();
    }


    $license = get_option( 'edd_onepress_plus_license_key' );

    //$expires = date_i18n( get_option( 'date_format' ), strtotime( $license_data->expires ) );
    $data =  get_option( 'edd_onepress_plus_license_data' );
    $status  = $can_action = false;
    if ( ! $license ) {
        $message = '<p class="description">'.esc_html__( 'Please enter your License key.', 'onepress-plus' ).'</p>';
    } else {
        if ( is_object( $data ) && property_exists( $data, 'license' ) ) {
            if ( $data->license == 'valid' ) {
                $status =  true;
                $can_action =  true;

                if ( $data->activations_left > 0 ) {
                    if ($data->expires == 'lifetime') {
                        $message = '<p class="description" style="color: green;">' . esc_html__('License key is active. Expires: Lifetime', 'onepress-plus') . '</p>';
                    } else {
                        $message = '<p class="description" style="color: green;">' . sprintf(esc_html__('License key is active. Expires %s.', 'onepress-plus'), date_i18n(get_option('date_format'), strtotime($data->expires))) . '</p>';
                    }
                } else {
                    $message = '<p class="description" style="color: green;">'.sprintf( esc_html__( 'License key is active. Activations: %1$s/%2$s.', 'onepress-plus' ), $data->site_count, $data->license_limit ).'</p>';
                    $message .= '<p class="description" style="color: green;">' . sprintf(esc_html__('Expires %s.', 'onepress-plus'), date_i18n(get_option('date_format'), strtotime($data->expires))) . '</p>';
                }
            } else if ( $data->license == 'deactivated' ) {
                $can_action =  true;
                $message = '<p class="description" style="color: red;">'.esc_html__( 'Your License is deactivated.', 'onepress-plus' ).'</p>';
            } else { // invalid

                if ( $data->error == 'expired' ) {
                    $message = '<p class="description" style="color: red;">'.sprintf( esc_html__( 'Your License key is expired. Expires %s.', 'onepress-plus' ), date_i18n( get_option('date_format'), strtotime( $data->expires ) ) ).'</p>';
                    $message .= '<p>'.esc_html__( 'This license must be renewed before it can be upgraded.', 'onepress-plus' ).' <a target="_blank" href="'.esc_url( 'https://www.famethemes.com/checkout/?edd_license_key='.$license.'' ).'">'.esc_html__( 'Click here to Renewal', 'onepress-plus' ).'</a> </p>';
                } else if ( $data->license_limit == 1 || $data->error == 'no_activations_left' )  {
                    $message = '<p class="description" style="color: red;">'.sprintf( esc_html__( 'Your license is limited. Activations: %1$s/%2$s.', 'onepress-plus' ), $data->site_count, $data->max_sites ).'</p>';
                } else {
                    $message = '<p class="description" style="color: red;">'.esc_html__( 'Your License key is invalid.', 'onepress-plus' ).'</p>';
                }

            }
        } else {
            if ( ! empty( $error ) ) {

                $message = '<div style="color: red;"><p>' .__( 'Could not connect to FameThemes server.', 'onepress-plus' ).'</p>';
                if ( is_array( $error ) ) {
                    foreach ( $error as $msg ) {
                        $message .= '<p>'.$msg.'</p>';
                    }
                } else {
                    $message = '<p class="description" style="color: red;">'.$message.'</p>';
                }
                $message .= '</div>';

            } else {
                $message = '<p class="description" style="color: red;">'.__( 'Your License key is invalid.', 'onepress-plus' ).'</p>';
            }

        }
    }

    ?>
    <form method="post" action="?page=ft_onepress&tab=auto_update">
        <table class="form-table">
            <tbody>
            <tr valign="top">
                <th scope="row" valign="top">
                    <?php _e('License Key', 'onepress-plus'); ?>
                </th>
                <td>
                    <input id="edd_onepress_plus_license_key" name="edd_onepress_plus_license_key" type="text" class="regular-text" value="<?php esc_attr_e( $license ); ?>" />
                    <div class="license-status-message">
                        <?php echo $message; ?>
                    </div>
                    <p><?php _e( 'Enter your license key to enable automatic theme updates. Find your license key at your FameThemes Dashboard, under Licenses section.', 'onepress-plus' ); ?></p>
                </td>
            </tr>
            <?php if( $can_action ) { ?>
                <tr valign="top">
                    <th scope="row" valign="top">
                        <?php _e('Activate License', 'onepress-plus' ); ?>
                    </th>
                    <td>
                        <?php if( $status ) { ?>
                            <input type="submit" class="button-secondary" name="edd_license_deactivate" value="<?php _e('Deactivate License', 'onepress-plus' ); ?>"/>
                        <?php } else { ?>
                            <input type="submit" class="button-secondary" name="edd_license_activate" value="<?php _e('Activate License', 'onepress-plus' ); ?>"/>
                        <?php } ?>
                        <?php wp_nonce_field( 'edd_onepress_plus_nonce', 'edd_onepress_plus_nonce' ); ?>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
        <?php submit_button(); ?>

    </form>
    <?php
}
add_action( 'onepress_more_tabs_details', 'onepress_plus_more_tabs_details' );


function edd_sl_onepress_plus_plugin_updater() {
	// retrieve our license key from the DB
	$license_key = trim( get_option( 'edd_onepress_plus_license_key' ) );

	// setup the updater
	$edd_updater = new EDD_SL_Plugin_Updater( EDD_ONEPRESS_PLUS_STORE_URL, ONEPRESS_PLUS_PATH.'onepress-plus.php', array(
			'version' 	=> ONEPRESS_PLUS_VERSION, // current version number
			'license' 	=> $license_key, 		// license key (used get_option above to retrieve from DB)
			'item_name' => EDD_ONEPRESS_PLUS_ITEM_NAME, 	// name of this plugin
			'author' 	=> 'FameThemes'  // author of this plugin
		)
	);

}
add_action( 'admin_init', 'edd_sl_onepress_plus_plugin_updater', 0 );


/**
 * This illustrates how to activate
 *
 * @return bool|void
 */
function edd_onepress_plus_activate_license( ) {

    // retrieve the license from the database
    $license = trim( get_option( 'edd_onepress_plus_license_key' ) );

    // data to send in our API request
    $api_params = array(
        'edd_action'=> 'activate_license',
        'license' 	=> $license,
        'item_name' => urlencode( EDD_ONEPRESS_PLUS_ITEM_NAME ), // the name of our product in EDD
        'url'       => home_url()
    );

    // Call the custom API.
    $response = wp_remote_post( EDD_ONEPRESS_PLUS_STORE_URL, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );
    $error = null;
    // make sure the response came back okay
    if ( is_wp_error( $response ) ) {
        $error = $response->get_error_messages();
        delete_option( 'edd_onepress_plus_license_data' );
    } else {
        // decode the license data
        $license_data = json_decode( wp_remote_retrieve_body( $response ) );
        // $license_data->license will be either "valid" or "invalid"
        update_option( 'edd_onepress_plus_license_data', $license_data );
    }

    return $error;
}


/**
 * Illustrates how to deactivate a license key.
 *
 * @return bool|void
 */
function edd_onepress_plus_deactivate_license() {

    // retrieve the license from the database
    $license = trim( get_option( 'edd_onepress_plus_license_key' ) );

    // data to send in our API request
    $api_params = array(
        'edd_action'=> 'deactivate_license',
        'license' 	=> $license,
        'item_name' => urlencode( EDD_ONEPRESS_PLUS_ITEM_NAME ), // the name of our product in EDD
        'url'       => home_url()
    );

    // Call the custom API.
    $response = wp_remote_post( EDD_ONEPRESS_PLUS_STORE_URL, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

    // make sure the response came back okay
    if ( is_wp_error( $response ) ) {

        delete_option( 'edd_onepress_plus_license_data', $license_data );
    } else {
        // decode the license data
        $license_data = json_decode( wp_remote_retrieve_body( $response ) );
        // $license_data->license will be either "deactivated" or "failed"
        if( $license_data->license == 'deactivated' ) {

        }
        update_option( 'edd_onepress_plus_license_data', $license_data );
    }

}


