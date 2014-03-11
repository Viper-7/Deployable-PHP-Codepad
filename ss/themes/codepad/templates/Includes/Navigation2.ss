<div class="nav user">
	<div class="head">User<% if CurrentUser %><a href="/Security/logout" style="position:absolute; right:10px;">Logout</a><% end_if %></div>
	<% if CurrentUser %>
		<!-- include NavUserDetails -->
		<div class="user_content">
			<span>Logged in as: </span>$CurrentUser.FirstName<br>
		</div>
	<% end_if %>
		<% if CurrentUser %>
			<!-- <li>
				<a href="/account">Account</a>
			</li> -->
		<% else %>
			<ul class="subnav">
				<li>
					<a href="/account">Create Account</a>
				</li>
				<li>
					<a href="/Security/login?BackURL=/">Log In</a>
				</li>
			</ul>
		<% end_if %>
</div>

<div class="nav_spacer">&nbsp;</div>
<% if Filename %>
<ul class="nav views">
	<li class="head">Views</li>
	<li><a class="view_popup" href="$VerLink/request_headers">Request Headers</a></li>
	<li><a class="view_popup" href="$VerLink/response_headers">Response Headers</a></li>
	<li><a class="view_popup" href="$VerLink/response_body">Response Body</a></li>
	<li><a class="view_popup" href="$VerLink/opcodes">Opcodes</a></li>
	<li><a class="view_popup" href="$VerLink/profile">Profile</a></li>
	<li><a class="view_popup" href="$VerLink/call_graph">Call Graph</a></li>
</ul>

<div class="nav_spacer">&nbsp;</div>
<% end_if %>

<% if Filename %>
	<ul class="nav controls">
		<li class="head">Controls</li>
		<li>
			<a href="/">New Paste</a>
		</li>
	</ul>

	<div class="nav_spacer">&nbsp;</div>

	<div class="nav history">
		<div class="head">Paste History</div>
		<div class="user_content">
			<% if History %>
				<% control History %>
					<span style="font-size: 8pt;"><% if AuthorID %>$Author.FirstName<% else %>unknown<% end_if %>: </span><a href="$Link"<% if isCurrentPaste %> class="active"<% end_if %>>$Title</a><br/>
				<% end_control %>
			<% end_if %>
		</div>
		<div style="clear: both;" class="clear"><!-- --></div>
	</div>
<% end_if %>

<% if CurrentUser %>
	<div class="nav_spacer">&nbsp;</div>
	
	<div class="nav history">
		<div class="head">My History</div>
		<div class="user_content">
			<% control UserHistory %>
				<a title="$Created.Nice" href="$Link"<% if isCurrentPaste %> class="active"<% end_if %>>$Title</a><br/>
			<% end_control %>
		</div>
		<div style="clear: both;" class="clear"><!-- --></div>
	</div>

        <div class="nav_spacer">&nbsp;</div>

        <div class="nav history recent">
                <div class="head">Recent Pastes</div>
                <div class="user_content">
                        <% if RecentPastes %>
                                <% control RecentPastes %>
                                        <span style="font-size: 8pt;"><% if AuthorID %>$Author.FirstName<% else %>unknown<% end_if %>: </span><a href="$Link"<% if isCurrentPaste %> class="active"<% end_if %>>$Title</a><br/>
                                <% end_control %>
                        <% end_if %>
                </div>
                <div style="clear: both;" class="clear"><!-- --></div>
        </div>
<% end_if %>

<!--	<li>
		<a href="/settings">Settings</a>
	</li> -->
<!--	<li>
		<a href="#">Options</a>
		<ul>
			<li class="inputfield">Paste Name: <input type="text"/></li>
			<li class="optionfield"><span style="float:left;">Privacy: </span>
				<fieldset>
					<label><input type="radio" name="privacy" value="public" checked="checked"/>Public</label><br>
					<label><input type="radio" name="privacy" value="friends"/>Friends Only</label><br>
					<label><input type="radio" name="privacy" value="personal"/>Personal</label><br>
				</fieldset>
			</li>
			<li class="selectfield">Category: 
				<select name="category" size="3">
				</select>
				<br>
				<a href="#" class="inline">Add New</a>
			</li>
			<li class="selectfield addfield">Post Vars: 
				<select name="postvars" size="3">
					<% control PostVars %>
						<option value="$Key">$Key="$Value"</option>
					<% end_control %>
				</select>
				<br>
				<a href="#" class="inline">Add New</a>
			</li>
			<li class="selectfield addfield">Files: 
				<select name="files" size="3">
					<% control AttachedFiles %>
						<option value="$Key">$Key="$Name"</option>
					<% end_control %>
				</select>
				<br>
				<a href="upload" class="view_popup inline">Upload</a>
			</li>
			<li><a href="custom_body" class="view_popup">Custom request body</a></li>
		</ul>
	</li>
-->

