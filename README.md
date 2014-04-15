GenericAuth-Zuha-Cakephp-Plugin
===============================
* Version: 1.0

This is self sufficient plugin to handle different authentication mechanism. As of version 1.0 it handles authentication using different social network login using oauth2/ openid.
Social network authentication is wrapper around HybridAuth(http://hybridauth.sourceforge.net/).

## Install Instructions
* Log in to admin <yoursite>/admin
* Click on "Install Plugins"
* Click on "GenericAuth"  from the available plugins list
* Go back to admin dashboard, and click on Settings.
* On that page select Type : APP, Name : DEFAULT_USER_REGISTRATION_ROLE_ID, and give it a value for the user role id that people who register with a social network should be.
* Follow directions at setting up apps on the various networks here : http://hybridauth.sourceforge.net/userguide.html
* Edit  sites/[your-site-folder]/Config/generic_auth.php and provide details about app details for corresponsing social network login which mostly includes appid, secrets and permissions
* * example config file at app/Config/generic_auth.php
* Example links to use on your view files for registering and logging in.
```
<p><a id="facebook" href="/generic_auth/social_auth/authenticate/facebook"><img src="/img/helpers/facebook.jpg" alt="facebook signup"></a></p>
<p><a id="twitter" href="/generic_auth/social_auth/authenticate/twitter"><img src="/img/helpers/twitter.jpg" alt="twitter signup"></a></p>
<p><a id="linkedin" href="/generic_auth/social_auth/authenticate/linkedin"><img src="/img/helpers/linkkedin.jpg" alt="linkedin signup"></a></p>
```

