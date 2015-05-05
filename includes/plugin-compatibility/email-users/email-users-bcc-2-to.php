<?php
/**
 * This function removes the bbc default functionality of email-users
 * it forces the sent emails to use the TO and not the BCC email header 
 * so that recpients of the email can reply-all.
 *
 */
function mailusers_rbhn_headers($to, $headers, $bcc)
{
    //  Copy the BCC headers to the TO header without the "Bcc:" prefix
    $to = preg_replace('/^Bcc:\s+/', '', $bcc) ;

    //  Empty out the BCC header
    $bcc = array() ;

    return array($to, $headers, $bcc) ;
}

$Role_Based_Help_Notes = RBHN_Role_Based_Help_Notes::get_instance( );
//$user_ID = get_current_user_id();
//$help_note_roles = $role_based_help_notes->active_help_notes( $user_ID );
$help_note_roles = $role_based_help_notes->help_notes_role( );
        
// add conditionals for the filter moving email addresses from  BCC > TO

if ( isset($_POST['send_targets']) && is_array($_POST['send_targets']) && count($_POST['send_targets']) == 1 ) {    // limit to only where one group is selected.
                                                                                                                    // if the email is "To" only one group (implies that it could be a help note related email.
               
    $send_2_email_users_group = $_POST['send_targets'];
    $send_2_role = preg_replace('/role-/', '', $send_2_email_users_group) ;
    
   // if ( array_key_exists( array_shift(array_values($send_2_role)), $help_note_roles ) ) {  // and if the group/role has help notes enabled
     if ( in_array( array_shift(array_values($send_2_role)), $help_note_roles ) ) {  // and if the group/role has help notes enabled
        die();
    }
    
    
      add_filter('mailusers_manipulate_headers', 'mailusers_rbhn_headers', 10, 3) ;  
}

                



?>