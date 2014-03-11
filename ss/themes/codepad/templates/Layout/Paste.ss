<script type="text/javascript" src="http://code.jquery.com/ui/1.8.18/jquery-ui.min.js"></script>

<link rel="stylesheet" href="codemirror/lib/codemirror.css"></link>
<link rel="stylesheet" type="text/css" href="http://code.jquery.com/ui/1.8.18/themes/base/jquery-ui.css"></link>

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
		
		var dialog = jQuery('#codepadloader').html('<img src="/themes/codepad/images/loader.gif"/>').dialog({
			autoOpen: true,
			modal: true,
			width: 850,
			height: 550,
			title: el.text()
		});
		
		var url = window.location.protocol + '//' + window.location.host + target + window.location.search;
		
		jQuery.get(
			url,
			function(data) {
				dialog.html(data);
			}
		);
		
		return false;
	});
	
});
</script>
<style type="text/css">
textarea.hon { display: none; }
</style>
<div id="codepadloader" style="display:none;">
   <img src="/themes/codepad/images/loader.gif"/>
</div>
<form method="post" action="/Paste">
<h1><span>Script: </span><input type="text" class="script_title" value="$Title" name="Title"/> <span class="showhide"><a href="#">(hide)</a></span></h1>
<div class="pad_stats">
	<table class="stats left">
		<thead></thead><tfoot></tfoot>
		<tbody>
			<tr>
				<th>Author</th>
				<td><% if Author %><a href="$Author.Link">$Author.FirstName</a><% else %>unknown<% end_if %></td>
			</tr>
			<tr>
				<th>Size:</th>
				<td>$Size</td>
			</tr>
			<tr>
				<th>Created:</th>
				<td><% if Filename %>$Created.Nice<% else %>$Created.Now.Nice<% end_if %></td>
			</tr>
		</tbody>
	</table>

	<table class="stats middle">
		<thead></thead><tfoot></tfoot>
		<tbody>
			<tr>
				<th>Version:</th>
				<td>$Version</td>
			</tr>
			<tr>
				<th>Hits:</th>
				<td>$Hits</td>
			</tr>
			<tr>
				<th>Render Time:</th>
				<td>$RenderTime</td>
			</tr>
		</tbody>
	</table>

	<table class="stats right">
		<thead></thead><tfoot></tfoot>
		<tbody>
			<tr>
				<th>PHP Version</th>
				<td rowspan="3">
					<select name="PHPVersion" size="4">
						<% control PHPVersions %>
							<option value="$ID" <% if isCurrent %>selected="selected"<% end_if %>>$Title</option>
						<% end_control %>
					</select>
				</td>
			</tr>
			<tr>
			</tr>
			<tr>
			</tr>
		</tbody>
	</table>
	
	<div class="clear" style="clear:both;"><!-- --></div>
</div>

<% if Filename %>
	<h1><span>Output: </span>HTML <span class="showhide"><a href="#">(hide)</a></span></h1>
	<div class="pad_output">
		<iframe src="$ExecuteLink"></iframe>
	</div>
<% end_if %>

<h1><span>Code: </span><span class="showhide"><a href="#">(hide)</a></span></h1>
<div class="pad_code <% if Not(Filename) %>nofile<% end_if %>">
<textarea name="hon" rows="20" cols="40" class="hon"></textarea>
	<textarea name="code" id="code"><% if Code %>$Code<% else %>&lt;?php
	echo "Hello, World!";
<% end_if %></textarea>
	<div class="paste_button">
		<input type="submit" value="Paste" class="paste"/>
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
	tabMode: "spaces",
	onCursorActivity: function() {
		editor.setLineClass(hlLine, null);
		hlLine = editor.setLineClass(editor.getCursor().line, "activeline");
	}
});
var hlLine = editor.setLineClass(0, "activeline");
</script>
<% if Filename %>
	<input type="hidden" name="SeriesID" value="$SeriesID"/>
<% end_if %>
</form>
