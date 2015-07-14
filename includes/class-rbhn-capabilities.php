<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * RBHN_Capabilities class.
 */
class RBHN_Capabilities {

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct( ) {
	
		// Add Meta Capability Handling 
		add_filter( 'map_meta_cap', array( $this, 'rbhn_map_meta_cap' ), 10, 4);
                
                // cater for attachment editing if attached to a Help Note
                add_filter( 'map_meta_cap', array( $this, 'rbhn_attachment_map_meta_cap' ), null, 4 );   
    
                
                //add to the posts query for attachments to filter to a user
                add_filter( 'posts_where', array( $this, 'rbhn_posts_where' ), 10, 2 );
                
                
                
                
                
                //http://jeffreycarandang.com/tutorials/hide-wordpress-posts-media-uploaded-users/
//               add_filter('pre_get_posts', array( $this, 'rbhn_hide_posts_media_by_other' ) );
                
                
	//	add_action( 'admin_menu', array( $this, 'rbhn_admin_menu' ), 11 );
                
              //  add_action( 'load-media-new.php', 'rbhn_load_media' );
              //  add_action( 'load-media.php', 'rbhn_load_media' );
              //  add_action( 'load-upload.php', 'rbhn_load_media' );                
	}
        

        public function rbhn_load_media() {
            if ( ! current_user_can( 'edit_posts' ) )
                wp_die( __( 'You do not have permission to access the Media Library.', 'role-based-help-notes' ) );
        }

        public function rbhn_admin_menu() {
            if ( ! current_user_can( 'edit_posts' ) )
                remove_menu_page( 'upload.php' );
        }
          
	/**
	 * rbhn_add_role_caps function.
	 *
	 * @access public
	 * @return void
	 */
	public static function rbhn_add_role_caps( ) {

		// option collection  
		$post_types_array 	= get_option( 'rbhn_post_types' );		
		$caps_options 		= ( array ) get_option( 'rbhn_caps_created' );  

		if ( ! empty( $post_types_array ) ) {

			foreach( $post_types_array as $help_note_post_types_array ) {

				foreach( $help_note_post_types_array as $active_role=>$active_posttype ) {


					if ( in_array( $active_role, $caps_options ) )
						break ; // if capabilities are already created drop out

					// add active role to option to stop re-creating its capabilities
					$caps_options[] = $active_role;
					update_option( 'rbhn_caps_created', $caps_options ); 

					// gets the new Help Note active role
					$role = get_role( $active_role );
					$capability_type = $active_posttype;
					
					$role->add_cap( "edit_{$capability_type}s" );
					$role->add_cap( "edit_others_{$capability_type}s" );
					$role->add_cap( "publish_{$capability_type}s" );
					$role->add_cap( "read_private_{$capability_type}s" );
					$role->add_cap( "delete_{$capability_type}s" );
					$role->add_cap( "delete_private_{$capability_type}s" );
					$role->add_cap( "delete_published_{$capability_type}s" );
					$role->add_cap( "delete_others_{$capability_type}s" );
					$role->add_cap( "edit_private_{$capability_type}s" );
					$role->add_cap( "edit_published_{$capability_type}s" );
					$role->add_cap( "create_{$capability_type}s" );
					$role->add_cap( "manage_categories_{$capability_type}" );
					$role->add_cap( "upload_files_{$capability_type}" );
//add clean up and uninstall parts.. for upload_files					
					
					// As of 12/11/2014 help notes are created all the time so Admins are no-longer given access by default 
					// they must be in a role to get the Help Notes.  This is for the flush of permalinks to save correctly
					// For Super-Admins on a multi-site Network installation all Help Notes will be available.
					// For Administrators Help Notes will only be available if the role is given.


				}
			}
		}
	}

	/**
	 * rbhn_role_caps_cleanup function.
	 *
	 * @access public
	 * @param mixed $role_key
	 * @return void
	 */
	public static function rbhn_role_caps_cleanup( $role_key ) {

		$role_based_help_notes = RBHN_Role_Based_Help_Notes::get_instance( );
		
		// Since the clean up is for roles not stored within the options array we need to regenerate the post-type/capability-type that would have existed
		// if the help note for a role was enabled.  Once we know this we can then clean up the capabilities if they still exist.
		$capability_type = $role_based_help_notes->clean_post_type_name( $role_key );
			
		$delete_caps = 	array(
                                        "edit_{$capability_type}",
                                        "read_{$capability_type}",
                                        "delete_{$capability_type}",
                                        "edit_{$capability_type}s",
                                        "edit_others_{$capability_type}s",
                                        "publish_{$capability_type}s",
                                        "read_private_{$capability_type}s",
                                        "delete_{$capability_type}s",
                                        "delete_private_{$capability_type}s",
                                        "delete_published_{$capability_type}s",
                                        "delete_others_{$capability_type}s",
                                        "edit_private_{$capability_type}s",
                                        "edit_published_{$capability_type}s",
                                        "create_{$capability_type}s",
                                        "manage_categories_{$capability_type}"
                                );


		global $wp_roles;
		
		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles( );
		}
			
		$users 		= get_users( );
		$administrator	= get_role( 'administrator' );
		
		foreach ( $delete_caps as $cap ) {
			
			// Clean-up Capability from WordPress Roles
			foreach ( array_keys( $wp_roles->roles ) as $role ) {
				$wp_roles->remove_cap( $role, $cap );
			}
			
			// Clean-up Capability from WordPress Users where explicitly allocated 
			foreach ( $users as $user ) {
				$user->remove_cap( $cap );
			}		

			// Clean-up Capability from the Administrator Role
			$administrator->remove_cap( $cap );
			
		}
		unset( $wp_roles );
	}

	/**
	 * rbhn_clean_inactive_capabilties function.
	 *
	 * @access public
	 * @return void
	 */
	public static function rbhn_clean_inactive_capabilties( ) {

		// collect an array of all inactive Help Note Post Types an remove capabilities
		$post_types_array = get_option( 'rbhn_post_types' );  
		
		$role_based_help_notes = RBHN_Role_Based_Help_Notes::get_instance( );
		$active_roles = $role_based_help_notes->help_notes_role( );

		// Find capabilities already built.
		$caps_options = get_option( 'rbhn_caps_created' );
		if ( ! empty( $caps_options ) ) {
			foreach( $caps_options as $cap_built ) {

				// capabilities have been built so stop further re-builds.
				if ( $cap_built && ! in_array( $cap_built, $active_roles ) ) {

					// clean up the capabilities 
					self::rbhn_role_caps_cleanup( $cap_built );
					
					// remove the removed $cap_built from the built capabilities array 
					$caps_options = array_diff( $caps_options, array( $cap_built ) );
					update_option( 'rbhn_caps_created', $caps_options ); 
				}	
			}
		}
	}
	
	/**
	 * rbhn_map_meta_cap function to add Meta Capability Handling.
	 *
	 * @access public
         * @param array $caps An array of capabilities that the user must have to be allowed the requested capability
         * @param array $cap The specific capability requested
         * @param int $user_id The ID of the user whose capability we are checking
         * @param array $args The arguments passed when checking for the capability
	 * @return array An array of capabilities that the user must have to be allowed the requested capability
	 */
	public function rbhn_map_meta_cap( $caps, $cap, $user_id, $args ) {
		
		// option collection to collect active Help Note roles.  
		$post_types_array = ( array ) get_option( 'rbhn_post_types' );	// collect available roles
		$post_types_array = array_filter( $post_types_array );		// remove empty entries

		if ( ! empty( $post_types_array ) ) {
		
			$help_note_found = false;
	
			foreach( $post_types_array as $active_role=>$active_posttype ) {
	
				$active_posttype_values =  array_values ( ( array ) $active_posttype );
				$capability_type = array_shift( $active_posttype_values );

				if ( 'edit_' . $capability_type == $cap || 'delete_' . $capability_type == $cap || 'read_' . $capability_type == $cap  ) {


					$post = get_post( $args[0] );
					$post_type = get_post_type_object( $post->post_type );

					/* Set an empty array for the caps. */
					$caps = array( );
					
					$help_note_found = true;
					break;
					
				}
			}                                
                                
			/* If editing a help note, assign the required capability. */
			if ( $help_note_found && ( "edit_{$capability_type}" == $cap ) ) {

				if( $user_id == $post->post_author )
					$caps[] = $post_type->cap->edit_posts;
				else
					$caps[] = $post_type->cap->edit_others_posts;	

			}
					
			/* If deleting a help note, assign the required capability. */
			elseif( $help_note_found && ( "delete_{$capability_type}" == $cap ) ) {
				
				if( isset( $post->post_author ) && $user_id == $post->post_author  && isset( $post_type->cap->delete_posts ) )
					$caps[] = $post_type->cap->delete_posts;
				elseif ( isset( $post_type->cap->delete_others_posts ) )
					$caps[] = $post_type->cap->delete_others_posts;		
			}

			/* If reading a private help note, assign the required capability. */
			elseif( $help_note_found && ( "read_{$capability_type}" == $cap ) ) {

				if( 'private' != $post->post_status )
					$caps[] = 'read';
				elseif ( $user_id == $post->post_author )
					$caps[] = 'read';
				else
					$caps[] = $post_type->cap->read_private_posts;
			}
		}
		
		/* Return the capabilities required by the user. */
		return $caps;	
	}
    
//upload_files add cap or cpt

        /**
         * Hooks the WP map_meta_cap filter.
         *
         * @param array $caps An array of capabilities that the user must have to be allowed the requested capability
         * @param array $cap The specific capability requested
         * @param int $user_id The ID of the user whose capability we are checking
         * @param array $args The arguments passed when checking for the capability
         * @return array An array of capabilities that the user must have to be allowed the requested capability
         **/
        
        public function rbhn_attachment_map_meta_cap( $caps, $cap, $user_id, $args ) {
            
            
            
            // We're going to use map_meta_cap to check for the ability to edit the
            // parent post of the attachment. If the user can edit the parent post,
            // we will allow them to edit this attachment. This should cover scenarios where
            // images are uploaded to become a featured image for a video.
            // 
            // 
            //   if ( 'edit_post' == $cap || 'delete_post' == $cap || 'read_post' == $cap ) {
            if ( 'edit_post' == $cap || 'read_post' == $cap ) {
                $post = get_post( $args[ 0 ] );
		$post_type = get_post_type_object( $post->post_type );
                
             
                if ( 'attachment' == $post->post_type ) {
                    $parent = get_post( $post->post_parent );
                    $capability_type = 'h_subscriber';
                     
                    if ( 'h_subscriber' == $parent->post_type ) {  // && ( "edit_{$capability_type}" == $cap ) ) {  //user_can( $user_id, 'edit_h_subscriber', $parent->ID ) ) {
                            $caps = array( );
                            $caps[] = 'edit_h_subscribers';
                      }
                }
            }
            return $caps;
        }
  
        

        public function rbhn_posts_where(  $where, $object  ){
                    
            remove_filter( 'posts_where', array( $this, 'rbhn_posts_where' ), 10, 2 );    // unhook to stop nested looping error
            $role_based_help_notes = RBHN_Role_Based_Help_Notes::get_instance( );
            $active_help_note_ids = $role_based_help_notes->help_note_ids( );
            add_filter( 'posts_where', array( $this, 'rbhn_posts_where' ), 10, 2 );       // rehook
            
       
            global $wpdb;       
            global $pagenow;
            
            if( is_admin() ) {

                $screen = get_current_screen();

                if( is_user_logged_in() && $pagenow == 'upload.php' && $object->post_type = 'attachment' ){
                    $author = get_current_user_id();
                   
                    // limit to attachments that are uploaded by the current user (author)
                    $where .= ' AND post_author = ' . $author;
 
                    $post_parent__in = implode(',', array_map( 'absint', $active_help_note_ids ) );
                    $where .= " OR ( $wpdb->posts.post_parent IN ($post_parent__in) ) "; //AND (  $wpdb->post_type = 'attachment'  ) ";
                    
                    $where .= " AND post_type != 'revision'";
       
                    //ref https://core.trac.wordpress.org/attachment/ticket/13927/post_parent__in.php
                    // which is by nacin..^^
//die(var_dump( $object));                    
                    //$post_parent__in =  $active_help_note_ids;
                   // $where .= " OR $wpdb->posts.post_parent NOT IN ($post_parent__in)";
                    //$where .= ' AND post_parent__in=' . $active_help_note_ids;  //array( 'post_parent__in' => array( 2, 5, 12, 14, 20 ) )
                                    //$where .= " AND ID IN ( SELECT object_id FROM {$wpdb->term_relationships} WHERE term_taxonomy_id=$author )";
                }

            }
            
            return $where;
        }
        
        function rbhn_hide_posts_media_by_other( $query ) {

                global $pagenow;

                if( ( 'edit.php' != $pagenow && 'upload.php' != $pagenow   ) || ! $query->is_admin ){
                        return $query;
                }

                if( ! current_user_can( 'manage_options' ) ) {
                        global $user_ID;
                        $query->set( 'author', $user_ID );
                        $query->set( 'post_parent', 55670 );
                }
                return $query;
        }
       
}

new RBHN_Capabilities( );


//add_filter( 'posts_where', 'devplus_attachments_wpquery_where' );
function devplus_attachments_wpquery_where( $where ){
	global $current_user;

	if( is_user_logged_in() ){
		// we spreken over een ingelogde user
		if( isset( $_POST['action'] ) ){
			// library query
			if( $_POST['action'] == 'query-attachments' ){
				$where .= ' AND post_author='.$current_user->data->ID;
			}
		}
	}

	return $where;
}









?>