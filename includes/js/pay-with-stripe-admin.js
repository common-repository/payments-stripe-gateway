jQuery( document ).ready(function() {

	jQuery( document ).on( 'click', '.pws-click-copy', function(e) {
		e.preventDefault();
		var copyText = jQuery( this ).prev();
		copyText.select();
		//copyText.setSelectionRange(0, 99999); /* For mobile devices */
		document.execCommand("copy");
		return false;
	});

});
