<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" >
	<head>
		<% base_tag %>
		$MetaTags
		<link rel="shortcut icon" href="/favicon.ico" />
		<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
	</head>
<body>
<div class="main">
	<div class="header">
		<div class="gfxHeader"><h1>$Hostname</h1></div>
	</div>
	<div class="content">
	  $Layout
	</div>
	<div class="navcontainer">
		<% include Navigation2 %>
	</div>
</div>
<div class="footer">
  <% include Footer %>
</div>
</body>
</html>
