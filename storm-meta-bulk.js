// @ref http://rachelcarden.com/2012/03/manage-wordpress-posts-using-bulk-edit-and-quick-edit/
(function($) {

	// create a copy of the WP inline edit post function
	var $wp_inline_edit = inlineEditPost.edit;
	inlineEditPost.edit = function( id ) {
	
		// call the original WP edit function
		$wp_inline_edit.apply( this, arguments );
	
		// get the post ID
		var $post_id = 0;
		if ( typeof( id ) == 'object' )
			$post_id = parseInt( this.getId( id ) );
	
		if ( $post_id > 0 ) {
	
			// define the edit row
			var $edit_row = $( '#edit-' + $post_id );
	
			// get the custom meta field
			var $storm_meta_input = $( '#storm-meta-input-' + $post_id ).text();
	
			// populate the custom meta field
			$edit_row.find( 'input[name="storm_meta_input"]' ).val( $storm_meta_input );
	   }
	};

	$( '#bulk_edit' ).live( 'click', function() {
	
		// define the bulk edit row
		var $bulk_row = $( '#bulk-edit' );
		
		// get the selected post ids that are being edited
		var $post_ids = new Array();
		$bulk_row.find( '#bulk-titles' ).children().each( function() {
			$post_ids.push( $( this ).attr( 'id' ).replace( /^(ttle)/i, '' ) );
		});
		
		// get the custom meta field
		var $storm_meta_input = $bulk_row.find( 'input[name="storm_meta_input"]' ).val();
		
		// save the data
		$.ajax({
			url: ajaxurl, 
			type: 'POST',
			async: false,
			cache: false,
			data: {
				action: 'storm_save_bulk_edit', 
				post_ids: $post_ids, 
				storm_meta_input: $storm_meta_input
			}
		});
	});
	
})(jQuery);