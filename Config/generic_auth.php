<?php
$config = array('GenericAuthConfig' => array(
		"base_url" => Router::url(array(
			'controller' => 'common_auth',
			'action' => 'index'
		), true),
		"providers" => array(
			// openid providers
			"OpenID" => array("enabled" => true),
			"AOL" => array("enabled" => true),
			"Yahoo" => array(
				"enabled" => true,
				"keys" => array(
					"id" => "",
					"secret" => ""
				)
			),
			"Google_openid" => array(
				"enabled" => true,
				"wrapper" => array(
					"path" => "Providers/hybridauth-additional-providers/hybridauth-google-openid/Providers/Google.php",
					"class" => "Hybrid_Providers_Google"
				)
			),
			"Google" => array(
				"enabled" => true,
				"keys" => array(
					"id" => "665321040858-gb91r5a0f48thidn29o5uo2d9gg00ohp.apps.googleusercontent.com",
					"secret" => "QpCYCEZ_vYfZuPBJjGBqlBrG"
				),
				"contacts_param" => array("max-results" => 10000)
			),
			"Google_sachin_public" => array(
				"enabled" => true,
				"keys" => array(
					"id" => "665321040858-vvgjh620fcbdcq31stkbrouphjjs26so.apps.googleusercontent.com",
					"secret" => "Ni5JNsohJxoownGj5mll5JH1"
				),
				"contacts_param" => array("max-results" => 10000)
			),
			"Facebook" => array(
				"enabled" => true,
				"keys" => array(
					"id" => "237694149662971",
					"secret" => "5f88aabc4c7ca22e236ab2907258e37a"
				),
				"scope" => "email, user_about_me, user_hometown, user_photos, user_location"
			),
			"Twitter" => array(
				"enabled" => true,
				"keys" => array(
					"key" => "cEC4qXocdgGSh2tRPDSzJg",
					"secret" => "S2qfgaszb4HA8rA7Xvcw3SXGQFr45osMHYf0Hi8J0o"
				)
			),
			// windows live
			"Live" => array(
				"enabled" => true,
				"keys" => array(
					"id" => "",
					"secret" => ""
				)
			),
			"MySpace" => array(
				"enabled" => true,
				"keys" => array(
					"key" => "",
					"secret" => ""
				)
			),
			"LinkedIn" => array(
				"enabled" => true,
				"keys" => array(
					"key" => "",
					"secret" => ""
				)
			),
			"Foursquare" => array(
				"enabled" => true,
				"keys" => array(
					"id" => "",
					"secret" => ""
				)
			),
		),
		// if you want to enable logging, set 'debug_mode' to true  then provide a
		// writable file by the web server on "debug_file"
		"debug_mode" => false,
		"debug_file" => ""
	));
