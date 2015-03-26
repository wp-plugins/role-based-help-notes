<?php

/**
 * Additional Compatibility with Plugins
 * 
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/* Role Based Help Notes Extra email-diff and SSL Support 
 *  
 * Load the entension plugin settings
 */

if ( is_admin() ) {
    include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
    if ( is_plugin_active( 'role-based-help-notes-extra/role-based-help-notes-extra.php' ) || is_plugin_active_for_network( 'role-based-help-notes-extra/role-based-help-notes-extra.php' ) ) {
        require_once( RBHNE_PATH . 'includes/class-rbhne-settings.php' );
    }
}

/* tabby-responsive-tabs 
 * 
 * If tabby-responsive-tabs is installed and active and tabbed helpnotes are 
 * selected in the settings then the main rbhn class will provide a tabbed 
 * contents page.
 */

/* If the plugin is not active remove the settings */
if ( ! is_plugin_active( 'tabby-responsive-tabs/tabby-responsive-tabs.php' ) && ! is_plugin_active_for_network( 'tabby-responsive-tabs/tabby-responsive-tabs.php' ) ) {
    add_filter( 'rbhn_settings', 'tabby_responsive_tabs_settings', 10, 1 );
}     

function tabby_responsive_tabs_settings( $settings ) {
    
    foreach ( $settings['rbhn_general']['settings'] as $setting=>$options ) {
        //var_dump( $options );

        if ( isset( $options['name'] ) && ( $options['name'] == 'rbhn_tabbed_contents_page' ) ) {
            unset($settings['rbhn_general']['settings'][$setting]);
            break 1;
        }

    }
    return $settings;
}



/* buddypress 
 * 
 * If BuddyPress is installed and active then re-route the user page
 * to the BuddyPress profile page instead of the user archive of posts
 */
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