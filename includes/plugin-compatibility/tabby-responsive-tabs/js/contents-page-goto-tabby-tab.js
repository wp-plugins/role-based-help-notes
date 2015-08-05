function GetURLParameter( sParam ) {
	var sPageURL = window.location.search.substring( 1 );
	var sURLVariables = sPageURL.split( '&' );
	for ( var i = 0; i < sURLVariables.length; i++ ) {
		var sParameterName = sURLVariables[i].split('=');
		if ( sParameterName[0] == sParam ) {
			return sParameterName[1].replace('MyPostType', '');
		}
	}
}

jQuery(document).ready(function(){

    setTimeout(function() {

	var tabby_tab_id = GetURLParameter( 'tabby_tab' );
	if (!tabby_tab_id)
		return false;
	
	console.log("tabby_tab_id...=" + tabby_tab_id); 
	jQuery( "#tablist1-" + tabby_tab_id).click();	 
		 
    }, 1000);

})
