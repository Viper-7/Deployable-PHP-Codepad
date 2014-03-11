<link rel="stylesheet" href="codemirror/lib/codemirror.css"></link>
<link rel="stylesheet" type="text/css" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.7.1/themes/base/jquery-ui.css"></link>
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.7.1/jquery-ui.min.js"></script>
<script src="codemirror/lib/codemirror.js"></script>
<script src="codemirror/mode/xml/xml.js"></script>
<script src="codemirror/mode/javascript/javascript.js"></script>

<script src="codemirror/mode/css/css.js"></script>
<script src="codemirror/mode/clike/clike.js"></script>
<script src="codemirror/mode/php/php.js"></script>
<script type="text/javascript">
jQuery(function() {
	var shower, hider;
	
	hider = function(e) {
		jQuery(this).text('(show)');
		var h1 = jQuery(this).closest('h1');
		var div = h1.next('div');
		var sibling = div.next('h1').next('div').children('textarea,iframe');
		if(!sibling.length)
			sibling = div.prev('h1').prevUntil('h1', 'div').children('textarea,iframe');
		
		sibling.height(parseInt(sibling.height()) + 200);
		div.addClass('hidden');

		jQuery(this).unbind('click', hider);
		jQuery(this).bind('click', shower);
	};
	
	shower = function(e) {
		jQuery(this).text('(hide)');
		var h1 = jQuery(this).closest('h1');
		var div = h1.next('div');
		var sibling = div.next('h1').next('div').children('textarea,iframe');
		if(!sibling.length)
			sibling = div.prev('h1').prevUntil('h1', 'div').children('textarea,iframe');
		
		sibling.height(parseInt(sibling.height()) - 200);
		div.removeClass('hidden');
		
		jQuery(this).unbind('click', shower);
		jQuery(this).bind('click', hider);
	};
	
	jQuery('h1 span.showhide a').click(hider);
	
	
	jQuery('a.view_popup').click(function(e) {
		var el = jQuery(this);
		var target = el.attr('href');
		
		var dialog = jQuery('<div class="loader"><img src="/loader.gif"/></div>').dialog({
			autoOpen: true,
			title: 'View ' + target	// ucfirst?
		});
		
		var url = window.location.protocol + '//' + window.location.host + window.location.pathname + '/' + target;
		var url = window.location.protocol + '//' + window.location.host + '/';
		
		jQuery.get(
			url,
			function(data) {
				dialog.html(data);
				dialog.dialog('open');
			}
		);
		
		return false;
	});
	
});


</script>

<h1><span>Script: </span>Untitled <span class="showhide"><a href="#">(hide)</a></span></h1>
<div class="pad_stats">
	<table class="stats left">
		<thead></thead><tfoot></tfoot>
		<tbody>
			<tr>
				<th>Author</th>
				<td><a href="#">Viper-7</a></td>
			</tr>
			<tr>
				<th>Size:</th>
				<td>148 Bytes</td>
			</tr>
			<tr>
				<th>Created:</th>
				<td>2012-03-04 03:22:04 UTC</td>
			</tr>
		</tbody>
	</table>

	<table class="stats middle">
		<thead></thead><tfoot></tfoot>
		<tbody>
			<tr>
				<th>PHP Version</th>
				<td>5.3.10</td>
			</tr>
			<tr>
				<th>Hits:</th>
				<td>2</td>
			</tr>
			<tr>
				<th>Render Time:</th>
				<td>128.3ms</td>
			</tr>
		</tbody>
	</table>

	<table class="stats right">
		<thead></thead><tfoot></tfoot>
		<tbody>
			<tr>
				<th>Privacy:</th>
				<td><span class="vis_public">Public</span></td>
			</tr>
			<tr>
				<th>Fork:</th>
				<td>
					<a href="#">As revision</a><br>
				</td>
			</tr>
			<tr>
				<th>&nbsp;</th>
				<td>
					<a href="#">As new paste</a>
				</td>
			</tr>
		</tbody>
	</table>
	
	<div class="clear" style="clear:both;"><!-- --></div>
	
</div>

<h1><span>Output: </span>HTML <span class="showhide"><a href="#">(hide)</a></span></h1>
<div class="pad_output">
	<iframe src="http://codepad.viper-7.com/XP6RoS/53dev"></iframe>
</div>

<h1><span>Code: </span><span class="errorcount good">0</span> Errors <span class="showhide"><a href="#">(hide)</a></span></h1>
<div class="pad_code">
	<textarea name="code" id="code">&lt;?php
	echo "Hello, World!";
?&gt;</textarea>
	<div class="paste_button">
		<input type="submit" value="Paste"/>
	</div>
</div>
<script type="text/javascript">
var editor = CodeMirror.fromTextArea(document.getElementById("code"), {
	mode: "application/x-httpd-php",
	lineNumbers: true,
	lineWrapping: true,
	matchBrackets: true,
	indentUnit: 4,
	indentWithTabs: true,
	enterMode: "keep",
	tabMode: "shift",
	onCursorActivity: function() {
		editor.setLineClass(hlLine, null);
		hlLine = editor.setLineClass(editor.getCursor().line, "activeline");
	}
});
var hlLine = editor.setLineClass(0, "activeline");
</script>
