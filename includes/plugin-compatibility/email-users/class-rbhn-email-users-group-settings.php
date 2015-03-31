<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


/**
 * RBHN_Email_Users_Settings class.
 */
class RBHN_Email_Users_Settings {

	// Refers to a single instance of this class.
    private static $instance = null;
	
    /**
     * __construct function.
     *
     * @access public
     * @return void
     */	 
    private function __construct() {

            // add the new extra settings to the option pages
            add_filter( 'rbhn_settings', array( $this, 'register_email_users_settings' ), 10, 1 );

            // hook to save the role settings into the WP capabilities for roles.
            add_action( 'admin_post_' . 'rbhn_enable_email_users_roles', array ( $this, 'field_roles_for_group_email_custom_save' ) );	
                  
    }

    /**
     * register_extra_settings function.
     *
     * @access public
     * @return void
     */
    public function register_email_users_settings( $settings ) {

            //$license 	= get_option( 'rbhne_license_key' );
            //$status 	= get_option( 'rbhne_license_status' );

            $rbhn_email_users_settings = 	array(
                                                    'rbhn_email_user_groups' => array( 
                                                            'access_capability' => 'promote_users',
                                                            'title' 		=> __( 'Email Groups', 'role-based-help-notes-text-domain' ),
                                                            'description' 	=> __( 'Allow group email functionality, this uses the "Email Users" Plugin.', 'role-based-help-notes-text-domain' ),
                                                            'form_action'       => admin_url( 'admin-post.php' ),
                                                            'settings' 		=> array(														
                                                                                        array(
                                                                                                'name' 		=> 'rbhn_enable_email_users_roles',
                                                                                                'std' 		=> array(),
                                                                                                'label' 	=> __( 'Add User Role(s)', 'role-based-help-notes-text-domain' ),
                                                                                                'desc'		=> __( 'Enables the <strong>email_user_groups</strong> custom capability for individual roles, this will then be used by the <strong>email-users</strong> to enable group emailing.', 'role-based-help-notes-text-domain' ),
                                                                                                'type'      => 'field_roles_for_group_email_checkbox',
                                                                                                ),					
                                                                                        ),			
                                                    ),
                                    );	

            $new_settings = array_merge ( (array) $settings, (array)$rbhn_email_users_settings );

            // Move plugin extensions tab to the end.
            $plugin_extension_tab = $new_settings['rbhn_plugin_extension'];
            unset($new_settings['rbhn_plugin_extension']);
            $plugin_extension_array = array();
            $plugin_extension_array['rbhn_plugin_extension'] = $plugin_extension_tab;		
            $new_settings = array_merge ( (array)$new_settings, (array)$plugin_extension_array );		
            return 	$new_settings;
    }


    public function field_roles_for_group_email_custom_save( ) {

        // authenticate
        $_nonce = isset( $_POST['rbhn_email_user_groups_nonce'] ) ? $_POST['rbhn_email_user_groups_nonce'] : '';

        if ( ! wp_verify_nonce( $_nonce , 'rbhn_email_user_groups' ) ) { 
           wp_die( __( 'You do not have permission.', 'role-based-help-notes-text-domain' ) );
        }

        $option_name = 'rbhn_enable_email_users_roles';

        if ( isset ( $_POST[ $option_name ] ) ) {
            update_option( $option_name, $_POST[ $option_name ] );
            $msg = 'updated';
        } else {
            delete_option( $option_name );
                        //wp_die( $_POST[ $option_name ] );
            $msg = 'deleted';
        }


        $url = add_query_arg( 'msg', $msg, urldecode( $_POST['_wp_http_referer'] ) );


        if ( ! defined( 'get_editable_roles' ) ) {
                require_once( ABSPATH.'wp-admin/includes/user.php' );
        }

        // Collect all WP roles
        global $wp_roles;
 
        if ( ! isset( $wp_roles ) ) {
            $wp_roles = new WP_Roles( );
        }
        
        // collect an array of all roles names
        $roles = $wp_roles->get_names( );
        
        // collect our option for the roles to include the email_user_groups custom capability 
        $new_roles = get_option( $option_name );

        // set the capabilities
        foreach ( $roles as $role_key=>$_rolename ) {
            if ( in_array( $role_key, $new_roles ) ) {
                $role = get_role( $role_key );
                $role->add_cap( 'email_user_groups' );
            } else {
                $wp_roles->remove_cap( $role_key, 'email_user_groups' );
            }
        }

        wp_safe_redirect( $url );
        exit;

    }		

    /**
     * Creates or returns an instance of this class.
     *
     * @return   A single instance of this class.
     */
    public static function get_instance() {
	
        if ( null == self::$instance ) {
            self::$instance = new self;
        }
 
        return self::$instance;
		
    }
        
}

// Create new tabbed settings object for this plugin..
// and Include additional functions that are required.
RBHN_Email_Users_Settings::get_instance();


/**
 * RBHN_Email_Users_Settings_Additional_Methods class.
 */
class RBHN_Email_Users_Settings_Additional_Methods {

	
		/**
		 * field_roles_checkbox 
		 *
		 * @param array of arguments to pass the option name to render the form field.
		 * @return void
		 */
		public function field_roles_for_group_email_checkbox( array $args  ) {

			$option   = $args['option'];

			//  loop through the site roles and create a custom post for each
			global $wp_roles;
			
			if ( ! isset( $wp_roles ) ) {
				$wp_roles = new WP_Roles( );
			}

			$roles = $wp_roles->get_names( );
			unset( $wp_roles );
			
			?><ul><?php 
			asort( $roles );
                        
                        // this is necessary for the admin-post.php hook to find the element
                        ?><input type="hidden" name="action" value="<?php echo $option['name']; ?>"><?php
                                                            
			foreach( $roles as $role_key=>$role_name )
			{
                        $role = get_role( $role_key );

				$id = sanitize_key( $role_key );
				$value = ( array ) get_option( $option['name'] );

				// Render the output  
				?> 
				<li><label>
				<input type='checkbox'  
					id="<?php echo esc_html( "exclude_enabled_{$id}" ) ; ?>" 
					name="<?php echo esc_html( $option['name'] ); ?>[]"
                                        value="<?php echo esc_attr( $role_key )	; ?>"<?php checked( $role->has_cap( 'email_user_groups' ) ) ;?>

				>
				<?php echo esc_html( $role_name ) . " <br/>"; ?>	
				</label></li>
				<?php 
			}?></ul><?php 
			if ( ! empty( $option['desc'] ) )
				echo ' <p class="description">' . $option['desc'] . '</p>';		
		}
	
}



/**
 * RE_EXCLUDER class.
 */
class RBHN_EMAIL_GROUPS {

	// Refers to a single instance of this class.
    private static $instance = null;

	
    /**
    * __construct function.
    *
    * @access public
    * @return void
    */
    public function __construct() {

           // block standard WP roles from the primary selections.
           add_filter( 'editable_roles', array( $this, 'exclude_role_from_user' ) );

    }

    public function exclude_role_from_user( $editable_roles ) {
        
        /* Drop out if visible roles are being handled by the role-excluder plugin
         */
        if ( is_plugin_active( 'role-excluder/role-excluder.php' ) || is_plugin_active_for_network( 'role-excluder/role-excluder.php' ) ) {
            return $editable_roles;
        }             

        global $wp_roles;
       
        // drop out if not on the group emails page.
         if	( ! is_admin() || ! ( isset( $_GET['page'] ) && ( $_GET['page'] == 'mailusers-send-to-group-page' ) ) )  {
             return $editable_roles;
         }	

        if ( ! isset( $wp_roles ) ) {
                $wp_roles = new WP_Roles();
        }

        $roles_all = array_keys( $wp_roles->get_names( ) );

        
        $roles_with_cap_email_user_groups = array();
        $current_user_assigned_roles = array( );
        
        
        /* Loop through each role object because we need to get the caps. */
        foreach ( $wp_roles->role_objects as $key => $role ) {
            
            /* build up the allowed roles for the current user */
            if ( $this->rbhn_current_user_has_role( $key ) ) {
                $current_user_assigned_roles[] = $key;
            }
            
            /* Roles without capabilities will cause an error, so we need to check if $role->capabilities is an array. */
            if ( is_array( $role->capabilities ) ) {

                /* Loop through the role's capabilities to find roles with the 'email_user_groups' capabiltiy set. */
                foreach ( $role->capabilities as $cap => $grant )
                    if ( ( $cap == 'email_user_groups' ) && $grant ) {
                         $roles_with_cap_email_user_groups[] = $key;
                         break;
                    }
            }
        }        
        
        //also limit to only the roles where the 'email_user_groups' capabiltiy is set 
        //(this matches the Help Notes settings for enabled group emailing per role)

        $current_user_assigned_roles_which_are_enabled_for_group_emails = array_intersect( $roles_with_cap_email_user_groups, $current_user_assigned_roles ) ;

        $role_excluder_roles_allowed = array();

         // now we have gathered all roles that are still allowed so now we will find the 
         // inverse to get an array of roles to be excluded
         if ( $roles_all != $current_user_assigned_roles_which_are_enabled_for_group_emails ) {


             // find roles not allowed for the current user
             $excluded_roles = array_diff( $roles_all, $current_user_assigned_roles_which_are_enabled_for_group_emails );

             // exclude roles from $editable_roles
             foreach ( $excluded_roles as $role_key_exclude ) {
                 unset ( $editable_roles[$role_key_exclude] );
             }
         }
        return $editable_roles;
    }


    /**
    * Checks if a particular user has a role. 
    * Returns true if a match was found.
    *
    * @param string $role Role name.
    * @param int $user_id (Optional ) The ID of a user. Defaults to the current user.
    * @return bool
    */
    public function rbhn_current_user_has_role( $role, $user_id = null ) {

           if ( is_numeric( $user_id ) ) {
                   $user = get_userdata( $user_id );
           } else {
                   $user = wp_get_current_user( );
           }
           if ( empty( $user ) ) {
                   return false;
           }
           return in_array( $role, ( array ) $user->roles );
    }
	
	
	/**
     * Creates or returns an instance of this class.
     *
     * @return   A single instance of this class.
     */
    public static function get_instance() {
 
        if ( null == self::$instance ) {
            self::$instance = new self;
        }
 
        return self::$instance;
 
    }		
}

/**
 * Init URE_OVERRIDE class
 */
 
RBHN_EMAIL_GROUPS::get_instance();

		
?>