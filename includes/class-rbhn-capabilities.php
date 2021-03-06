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
                
 		// Add upload_files Capability  
		add_filter( 'map_meta_cap', array( $this, 'rbhn_upload_file_map_meta_cap' ), 10, 4);               
                
                // cater for attachment editing if attached to a Help Note
                add_filter( 'map_meta_cap', array( $this, 'rbhn_attachment_map_meta_cap' ), null, 4 );   
                
                //add to the posts query for attachments to filter to a user
                add_filter( 'posts_where', array( $this, 'rbhn_posts_where' ), 10, 2 );
                add_filter( 'ajax_query_attachments_args', array( $this, 'rbhn_limit_current_user_attachments' ), 10, 1 );
	}
        
        public function rbhn_load_media() {
            if ( ! current_user_can( 'edit_posts' ) ) {
                wp_die( __( 'You do not have permission to access the Media Library.', 'role-based-help-notes' ) );
            }
        }

        public function rbhn_admin_menu() {
            if ( ! current_user_can( 'edit_posts' ) ) {
                remove_menu_page( 'upload.php' );
            }
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


					if ( in_array( $active_role, $caps_options ) ) {
						break ; // if capabilities are already created drop out
                                        }
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
                                        "manage_categories_{$capability_type}",
                                        "upload_files_{$capability_type}"
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
		
		$post_types_array = array_filter( ( array ) get_option( 'rbhn_post_types' ) );		// remove empty entries

		if ( ! empty( $post_types_array ) ) {
		
			$help_note_found = false;
	
			foreach( $post_types_array as $active_posttype ) {
	
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

				if( $user_id == $post->post_author ) {
					$caps[] = $post_type->cap->edit_posts;
                                }
				else {
					$caps[] = $post_type->cap->edit_others_posts;	
                                }

			}
					
			/* If deleting a help note, assign the required capability. */
			elseif( $help_note_found && ( "delete_{$capability_type}" == $cap ) ) {
				
				if( isset( $post->post_author ) && $user_id == $post->post_author  && isset( $post_type->cap->delete_posts ) ) {
					$caps[] = $post_type->cap->delete_posts;
                                }
				elseif ( isset( $post_type->cap->delete_others_posts ) ) {
					$caps[] = $post_type->cap->delete_others_posts;	
                                }
			}

			/* If reading a private help note, assign the required capability. */
			elseif( $help_note_found && ( "read_{$capability_type}" == $cap ) ) {

				if( 'private' != $post->post_status ) {
					$caps[] = 'read';
                                }
				elseif ( $user_id == $post->post_author ) {
					$caps[] = 'read';
                                }
				else {
					$caps[] = $post_type->cap->read_private_posts;
                                }
			}
		}
		
		/* Return the capabilities required by the user. */
		return $caps;	
	}
    

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

            if ( 'edit_post' == $cap || 'delete_post' == $cap || 'read_post' == $cap ) {
                $post = get_post( $args[ 0 ] );
		$post_type = get_post_type_object( $post->post_type );

         
                if ( 'attachment' == $post->post_type ) {
                   
                    $parent = get_post( $post->post_parent );

                    $role_based_help_notes = RBHN_Role_Based_Help_Notes::get_instance( );
                    $active_roles = array_filter( ( array ) $role_based_help_notes->help_notes_role( ) );  // Filter out any empty entries, if non active.	

                    if ( empty( $active_roles ) ) {
                        return $caps;
                    }
 
                    $caps = array( );

                    foreach( $active_roles as $role ) {  
                        $help_note_post_Type = $role_based_help_notes->clean_post_type_name( $role );
                    
                        if ( $help_note_post_Type == $parent->post_type ) {
  
                            /* If editing a attachment with a parent help note, assign the required capability. */
                            if ( "edit_post" == $cap )  {
                                $caps[] = "edit_{$help_note_post_Type}s"; 

                            }

                             
                            // If deleting a attachment with a parent help note, assign the required capability. 
                            if ( "delete_post" == $cap )  {
                                
                                if( isset( $post->post_author ) && $user_id == $post->post_author ) { 
                                    $caps[] = "delete_{$help_note_post_Type}s";
                                } elseif ( isset( $post_type->cap->delete_others_posts ) ) {
                                    $caps[] = "delete_others_{$help_note_post_Type}s";
                                }            
                            }

                            /* If reading a attachment with a parent help note, assign the required capability. 
                             * this is never used by Wordpress's capabilities for attachements as edit_posts is used.
                             *  if ( "edit_read" == $cap )  {
                             *      $caps[] = "read_{$help_note_post_Type}s"; //$caps[] = $parent->post_type->cap->read_posts;
                             *  }   
                             */

                        }
                    } 
                }
            }
            
            return $caps;
        }
  

        /**
         * Hooks the WP map_meta_cap filter and gives the current user the 'upload_files' capability
         * if there are active help_notes and they have the upload_files_{helpnoterole} capability.
         * With a default setup this means users with help notes available will have the 'upload_file'
         * capabiltiy granted.
         *
         * @param array $caps An array of capabilities that the user must have to be allowed the requested capability
         * @param array $cap The specific capability requested
         * @param int $user_id The ID of the user whose capability we are checking
         * @param array $args The arguments passed when checking for the capability
         * @return array An array of capabilities that the user must have to be allowed the requested capability
         **/
        public function rbhn_upload_file_map_meta_cap( $caps, $cap, $user_id, $args ) {
            
            if ( 'upload_files' == $cap ) {
  
                $role_based_help_notes = RBHN_Role_Based_Help_Notes::get_instance( );
		$active_roles = array_filter( ( array ) $role_based_help_notes->active_help_notes( ) );  // Filter out any empty entries, if non active.	

		if ( empty( $active_roles ) ) {
                    return $caps;
                }
           
                $caps = array( );
 
                foreach( $active_roles as $role ) {
                    if ( $role_based_help_notes->help_notes_current_user_has_role( $role ) ) {
                        $caps[] = 'upload_files_' . $role_based_help_notes->clean_post_type_name( $role ); 
                    }
                } 
                
            }

            return $caps;
        }
        
        /**
         * Hooks the WP_Query $where filter to limit further the Media files see in the upload.php admin screen.
         *
         * @param string $where of the current WP_Query where sql statement part
         * @param array $object is the WP_Query object
         * @return array $where 
         **/
        public function rbhn_posts_where(  $where, $object  ){

            if( ! is_admin( ) ) {   
                return $where;
            }
    
       //     if(  ( ! $object->query_vars['post_type'] = 'attachment' ) ) {         // or if not in the main loop
       //         return $where;
       //     }
            
            // this function has been seen not loaded on some sites
            // 
            if ( ! function_exists('get_current_screen') ) {
                return $where;
            }
       
            $currentScreen = get_current_screen();


            if( ( ! $currentScreen->base === 'upload' )     // if not on the media library page
                 || ( ! is_admin( ) )                       // or if not on the admin side of the site
            ) {   
                return $where;
            }
            
            // remove the 'posts_where' filter hook to stop infinite nested looping error
            // as this method uses calls to the WP_Query which inturn would otherwise call 'rbhn_posts_where'
            // in turn creating an infinite loop
            remove_filter( 'posts_where', array( $this, 'rbhn_posts_where' ), 10, 2 );     
            
            $role_based_help_notes = RBHN_Role_Based_Help_Notes::get_instance( );
            
            // remove upload_files map_meta_cap hook so that 'upload_files' capabilty relates to the 
            // user allocated caps before HelpNotes adds the cap to users.
            remove_filter( 'map_meta_cap', array( $this, 'rbhn_upload_file_map_meta_cap' ), 10, 4);
            $user_has_upload_files_wp_cap = current_user_can( 'upload_files' );
            add_filter( 'map_meta_cap', array( $this, 'rbhn_upload_file_map_meta_cap' ), 10, 4); 

            if ( $user_has_upload_files_wp_cap ) {


                // limit the media by removing attachments to HelpNotes that the current user does not have access to

                $enabled_help_notes = $role_based_help_notes->enabled_help_notes();
                $active_help_notes = $role_based_help_notes->active_help_notes();
                
                $help_notes_not_available_to_user = array_filter( array_diff( $enabled_help_notes, $active_help_notes ) ); // diff and remove empty elements.
          
                // drop out if the user has access to all enabled Help Notes
                if ( ! $help_notes_not_available_to_user ) {
                    return $where;
                }

                // collecting the Help Notes post IDs and while completing this
                // remove the 'posts_where' filter hook to stop infinite nested looping error
                             
                $helpnote_parent_not_available = $role_based_help_notes->help_note_ids( $help_notes_not_available_to_user );

                global $wpdb;
                $post_parent__not_in = implode(',', array_map( 'absint', $helpnote_parent_not_available ) ); 
                $where .= " AND ( $wpdb->posts.post_parent NOT IN ( $post_parent__not_in ) )";

            } 
            
            
            /* re-evaluate the current screen 
             * this returns null for the  displayed media library in modal on the post edit screen
             * hence the if statement will not execute instead the 'rbhn_limit_current_user_attachments' method 
             * caters for this case.
             */
           
            $currentScreen = get_current_screen();
            
    
            if ( current_user_can( 'upload_files' )         // otherwise now check in the current user has the 'upload_files' cap provdied by Help Notes via meta caps
                    && $currentScreen->base === 'upload'    // current screen is the upload media screen
            ) {  

                $author = get_current_user_id();

                /* limit to attachments that are uploaded by the current user (author) */
                $where .= ' AND post_author = ' . $author;

                $active_help_note_ids = $role_based_help_notes->help_note_ids( );
                $post_parent__in = implode(',', array_map( 'absint', $active_help_note_ids ) );
                
                global $wpdb;     
                $where .= " OR ( $wpdb->posts.post_parent IN ( $post_parent__in ) ) "; 
                $where .= " AND post_type != 'revision'";       
            }

            // re-hook the filter
            add_filter( 'posts_where', array( $this, 'rbhn_posts_where' ), 10, 2 );
            return $where;
        }

        
        
        /**
         * Hooks the ajax_query_attachments_args filter to limit the Media files seen on the add media popup screen.
         *
         * @param array $query is the WP_Query query
         * @return array $query 
         **/
        public function rbhn_limit_current_user_attachments(  $query = array()  ){

            /* drop out if the user has already permission to upload_files and see media the 
             * 'rbhn_posts_where' hooked function will fire and restric the media seen by these users.
             * 
             * remove upload_files map_meta_cap hook so that 'upload_files' capabilty relates to the 
             * user allocated caps before HelpNotes adds the cap to users.
             */
            remove_filter( 'map_meta_cap', array( $this, 'rbhn_upload_file_map_meta_cap' ), 10, 4);
            if ( current_user_can( 'upload_files' ) ) {
               return $query;
            }
            add_filter( 'map_meta_cap', array( $this, 'rbhn_upload_file_map_meta_cap' ), 10, 4); 

            $role_based_help_notes = RBHN_Role_Based_Help_Notes::get_instance( );
            $active_help_note_ids = $role_based_help_notes->help_note_ids( );

            /* join 2 queries together 
             * 1) one for finding the attchments that the current user has authored.
             * 2) the second to find the attachments that are attached to helpnotes that the current user has access to
             */
            $user_id = get_current_user_id();
            $current_user_attachments   = get_posts( array( 'post_type' => 'attachment', 'fields' => 'ids', 'author' => $user_id ) );
            $attachments_with_parent_help_notes = get_posts( array( 'post_type' => 'attachment', 'fields' => 'ids', 'post_parent__in' => $active_help_note_ids ) );
            $show_attachments = array_merge( $attachments_with_parent_help_notes, $current_user_attachments );
            $query['post__in'] = $show_attachments;        
            wp_reset_postdata();
            return $query;     
        }
}

new RBHN_Capabilities( );