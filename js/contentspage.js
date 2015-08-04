function GetURLParameter( sParam ) {
	var sPageURL = window.location.search.substring( 1 );
	var sURLVariables = sPageURL.split( '&' );
	for ( var i = 0; i < sURLVariables.length; i++ ) {
		var sParameterName = sURLVariables[i].split('=');
		if ( sParameterName[0] == sParam ) {
			return sParameterName[1].replace('MyPostType', '');
		}
	}
};

function goto_section( ){
	
	var post_type = "#" + arguments[0];

	jQuery('html, body').animate({
		scrollTop: jQuery(post_type).offset().top -50
	}, 'slow');
};

jQuery(document).ready(function(){

	var post_type = GetURLParameter( 'post_type' );
	console.log("post_type...=" + post_type); 
	goto_section( post_type );

});