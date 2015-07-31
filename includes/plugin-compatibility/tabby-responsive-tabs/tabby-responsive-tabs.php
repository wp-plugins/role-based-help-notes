<?php

/* tabby-responsive-tabs 
 * 
 * If tabby-responsive-tabs is installed and active and tabbed helpnotes are 
 * selected in the settings then the main rbhn class will provide a tabbed 
 * contents page.
 */




/* If tabby-responsive-tabs is installed and selected in settings to handle the Help Notes
 * contents page listing then we hook into the available fitlers here
 */

if (  get_option( 'rbhn_tabbed_contents_page' ) ) {
	//add_filter( 'rbhn_contents_page_before_listing', 'rbhn_tabby_contents_page_before_listing', 10 );
	add_filter( 'rbhn_contents_page_role_listing_title', 'rbhn_tabby_contents_page_role_listing_title', 10, 2 );
	add_filter( 'rbhn_contents_page_role_listing', 'rbhn_tabby_contents_page_role_listing', 10 );
	add_filter( 'rbhn_contents_page_role_final_listing', 'rbhn_tabby_contents_page_role_final_listing', 10 );
}

function rbhn_tabby_contents_page_role_listing_title( $value, $posttype_Name  ) {
    //$content = $rbhn_content . '<h2>' . $posttype_Name . '</h2>';
    $content = '[tabby title="' . $posttype_Name . '"]';
    return $content ;
}

function rbhn_tabby_contents_page_role_listing( $value  ) {
    $content = $value;
    return $content ;
}

function rbhn_tabby_contents_page_role_final_listing( $value  ) {
    //$content = do_shortcode( $value . '[tabbyending]' );
    $content = do_shortcode( $value . '[tabbyending]' );
    return $content ;
}