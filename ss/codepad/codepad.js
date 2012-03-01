    jQuery('#view_html').live('click', function() {
		var frame = jQuery('#test');
		frame.attr('src', frame.attr('src').replace('_t',''));
		return false;
    });
    jQuery('#view_text').live('click', function() {
        var frame = jQuery('#test');
        frame.attr('src', frame.attr('src').replace('_t','') + '_t');
		return false;
    });
