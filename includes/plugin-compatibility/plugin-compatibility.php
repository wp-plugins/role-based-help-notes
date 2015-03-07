<?php

/**
 * Additional Compatibility with Plugins
 * 
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/* buddypress */
if ( defined( 'BP_ENABLE_ROOT_PROFILES' ) ) {
    // Load class for compatibilty with email-users plugin
    require_once( HELP_MYPLUGINNAME_PATH . 'includes/plugin-compatibility/buddypress/buddypress.php' );
}        


/* user-emails 
 * 
 * Load code to limit availalable/editable roles to limit email users groups availability.
 */

if ( is_plugin_active( 'email-users/email-users.php' ) || is_plugin_active_for_network( 'email-users/email-users.php' ) ) {
    require_once( HELP_MYPLUGINNAME_PATH . 'includes/plugin-compatibility/email-users/class-rbhn-email-users-group-settings.php' );
}             


/**
 * Function provided to check if the current visitor has available Help Notes
 * @return False if no help notes are available for the current user, otherwise and array of help_note post types available.
 * Can be used also in other plugins - like “Menu Items Visibility Control” plugin.
 */
if ( !function_exists( 'help_notes_available' ) ) {
	function help_notes_available( ) {
		
		$helpnote_post_types = array( );
		
		$role_based_help_notes = RBHN_Role_Based_Help_Notes::get_instance( ); 
			
		// check if no help notes are selected.
		$help_note_post_types =  get_option( 'rbhn_post_types' );

		if ( ! array_filter( ( array ) $help_note_post_types ) && ! get_option( 'rbhn_general_enabled' ) )
			return false;

		//if the current user has the role of an active Help Note.
		if ( array_filter( ( array ) $help_note_post_types ) ) {	
			foreach( $help_note_post_types as $array ) {
				foreach( $array as $active_role=>$active_posttype ) {
					if ( $role_based_help_notes->help_notes_current_user_has_role( $active_role ) ) {
						$helpnote_post_types[] = $active_posttype;
					}				
				}
			}	
		}   

		// General Help Notes
		$my_query = new WP_Query( array(
			'post_type'     => array( 'h_general' ),
			) );

		if ( $my_query->have_posts( ) ) {
			$helpnote_post_types[] = 'h_general';
			}
			
		wp_reset_postdata( );
		
		

		$helpnote_post_types = array_filter( $helpnote_post_types );
		if ( ! empty( $helpnote_post_types ) ) {
			return $helpnote_post_types;
		} else {
			return false;
		}
	}
}
?>