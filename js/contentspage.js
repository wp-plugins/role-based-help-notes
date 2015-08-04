//jQuery(document).ready(function(){
//	var hidesection = 'a[id*="h_it"]';
//	jQuery(#hidesection).hide(); 
//});

/*
//the function to hide the div
function hideDiv(){

    if ($(window).width() < 1024) {

            jQuery("#h_it").fadeOut("slow");

    }else{

        jQuery("#h_it").fadeIn("slow");

    }

}

//run on document load and on window resize
//jQuery(document).ready(function () {

    //on load
 //   hideDiv();

    //on resize
 //   $(window).resize(function(){
 //       hideDiv();
 //   });

//});
*/

/*
//jQuery(document).ready(function(){
function getUrlParam( ){
	
	var getUrlParameter = function getUrlParameter(sParam) {
		var sPageURL = decodeURIComponent(window.location.search.substring(1)),
			sURLVariables = sPageURL.split('&'),
			sParameterName,
			i;

		for (i = 0; i < sURLVariables.length; i++) {
		 
			sParameterName = sURLVariables[i].split('=');

			if (sParameterName[0] === sParam) {
				return sParameterName[1] === undefined ? true : sParameterName[1].replace('MyPostType', '');
			}
		}
	};
	
 	
}
//);
*/
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
	
	// this works
	//jQuery("#h_it").hide();
	
	
	//var post_type = <?php echo json_encode($post_type) ?>;
	//var post_type = "#h_it";
	//var post_type = "#h_subscriber";
	//var post_type = "#" + arguments[0];
	var post_type = "#" + arguments[0];
	
	
	
	//text = text.replace("MyPostType", "");
	//var post_arg = getUrlParameter('post_type');
	//var post_type = post_arg.replace("MyPostType", "");
	
	jQuery('html, body').animate({
		scrollTop: jQuery(post_type).offset().top -50
	}, 'slow');
};

jQuery(document).ready(function(){
	//var post_type = getUrlParam( 'post_type' );
	var post_type = GetURLParameter( 'post_type' );
	console.log("post_type...=" + post_type); 
	//goto_section( "h_it" );
	goto_section( post_type );

});