(function($){

	$(document).on('click', '.button-close', function( e ){
		
		e.preventDefault();
		
		
		// validate parent
		if( !parent ) {
		
			return;
			
		}
		
		
		// update the div
		parent.live_edit.close_panel();
		
	});	

})(jQuery);
