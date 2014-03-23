<?php
Configure::load('generic_auth'); 
$genericAuthConfig = Configure::read('GenericAuthConfig');
$fbEnabled = $genericAuthConfig['providers']['Facebook']['enabled'];
if($fbEnabled){
$fbAppId = $genericAuthConfig['providers']['Facebook']['keys']['id'];
$fbDefPerms = $genericAuthConfig['providers']['Facebook']['scope'];
?>
<div id="fb-root"></div>

<script type="text/javascript">

// logs the user in the application and facebook
function fblogin(redirection,perms){
	if(typeof perms == 'undefined'){
		var perms = '<?php echo $fbDefPerms?>'; 
	}
	//console.log(perms);
	FB.login(function (response) {
		if(response.authResponse) {
			// user is logged in
			// console.log('Welcome!');
			if(redirection != null && redirection != ''){
				top.location.href = redirection;
			}
		} else {
			// user could not log in
			console.log('User cancelled login or did not fully authorize.');
		}
	}, {scope: perms});
}

// logs the user out of the application and facebook
function fblogout(redirection){
	FB.logout(function(response) {
		// user is logged out
		// redirection if any
		if(redirection != null && redirection != ''){
			top.location.href = redirection;
		}
	});
}

//<![CDATA[
window.fbAsyncInit = function() {
	FB.init({
		appId : <?php echo $fbAppId; ?>,
		status : true, // check login status
		cookie : true, // enable cookies to allow the server to access the session
		xfbml : true, // parse XFBML
		oauth : true // use Oauth
	});
};

// Load the SDK Asynchronously
(function(d){
   var js, id = 'facebook-jssdk', ref = d.getElementsByTagName('script')[0];
   if (d.getElementById(id)) {return;}
   js = d.createElement('script'); js.id = id; js.async = true;
   js.src = "//connect.facebook.net/en_US/all.js";
   ref.parentNode.insertBefore(js, ref);
 }(document));
//]]>

</script>
<?php }?>