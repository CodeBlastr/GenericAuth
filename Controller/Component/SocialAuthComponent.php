<?php
App::import('Vendor', 'GenericAuth.hybridauth/Hybrid/Auth');
App::import('Vendor', 'GenericAuth.hybridauth/Hybrid/Endpoint');
Configure::load('generic_auth');
/**
 * Generic component to handle different ways of authentication especially social
 * network authentication using(OpenId and OAuth2)
 * This is basically wrapper around third party implementation
 * To beging with it is cakephp wrappen for HybridAuth implementation.
 * Please refer http://hybridauth.sourceforge.net/ for details about HybridAuth
 * This basically delegates to the HybriAuth library and then gets the user
 * profile from it
 * User profile is then conveted to User model data and then saved in the
 * database if not already available
 * It is then passed through normal cake authentication mechanism to take over
 * @author sachin
 */
class SocialAuthComponent extends Component {

	public $authAdapter = null;

	public function __construct(ComponentCollection $collection, $settings = array()) {
		parent::__construct($collection, $settings);
		$this->Controller = $collection->getController();
		// ensure the session is started to avoid "Undefined variable _SESSION" notices
		// from the OpenID lib
		//session_start();
		CakeSession::check('_dummy_check_to_ensure_session_isavailable_');
	}

	public function process_authentication() {
		Hybrid_Endpoint::process();
	}

/**
 * Authenticate
 * 
 * @param string
 * @param bool
 * @param mixed
 */
	public function authenticate($provider, $alreadyLoggedin = false, $defaultRoleId = null) {

		$genericAuthConfig = Configure::read('GenericAuthConfig');

		try {
			// create an instance for Hybridauth with the configuration data
			$hybridauth = new Hybrid_Auth($genericAuthConfig);
			// try to authenticate the selected $provider
			if ($alreadyLoggedin && $provider == 'facebook') {
				$adapter = $hybridauth->getAdapter($provider);
				$userProfile = $this->__getUserNativeFbProfile($adapter);
			} else {
				$adapter = $hybridauth->authenticate($provider);
				$userProfile = $adapter->getUserProfile();
			}
			$this->authAdapter = $adapter;
			// grab the user profile
			//$this->log($userProfile);
			$photo = $this->__getProfilePicture($adapter, 'large', $provider, $alreadyLoggedin);
			//$this->log($photo);
			if (!empty($userProfile)) {
				$this->Controller->set('user_profile', $userProfile);
				return $this->__convertToUserModel($provider, $userProfile, $photo, $defaultRoleId);
			}
		} catch( Exception $e ) {
			$this->log($e);
			// Display the recived error
			switch( $e->getCode() ) {
				case 0 :
					$error = "Unspecified error.";
					break;
				case 1 :
					$error = "Hybriauth configuration error.";
					break;
				case 2 :
					$error = "Provider not properly configured.";
					break;
				case 3 :
					$error = "Unknown or disabled provider.";
					break;
				case 4 :
					$error = "Missing provider application credentials.";
					break;
				case 5 :
					$error = "Authentification failed. The user has canceled the authentication or the provider refused the connection.";
					break;
				case 6 :
					$error = "User profile request failed. Most likely the user is not connected to the provider and he should to authenticate again.";
					$adapter->logout();
					break;
				case 7 :
					$error = "User not connected to the provider.";
					$adapter->logout();
					break;
			}
			// well, basically your should not display this to the end user, just give him a
			// hint and move on..
			$error .= "<br /><br /><b>Original error message:</b> " . $e->getMessage();
			$error .= "<hr /><pre>Trace:<br />" . $e->getTraceAsString() . "</pre>";
			$this->Controller->set('error', $error);
		}
	}

	public function logout() {
		$genericAuthConfig = Configure::read('GenericAuthConfig');
		try {
			// create an instance for Hybridauth with the configuration file path as
			// parameter
			$hybridauth = new Hybrid_Auth($genericAuthConfig);
		} catch(Exception $e) {
			debug($e->getMessage());
			exit;
		}
		$hybridauth->logoutAllProviders();
	}

/**
 * Convert to user Model
 * 
 */
	private function __convertToUserModel($provider, $userProfile, $photo, $defaultRoleId) {
		$user['UserSocialNetwork']['type'] = $provider;
		$user['UserSocialNetwork']['auth_type'] = 'oauth';
		$user['UserSocialNetwork']['auth_response_data'] = json_encode($userProfile);
		//$user['UserSocialNetwork']['contacts_data'] = json_encode($userContacts);
		foreach ($userProfile as $key => $value) {
			switch ($key) {
				case "firstName" :
					$user['User']['first_name'] = $value;
					break;
				case "lastName" :
					$user['User']['last_name'] = $value;
					break;
				case "gender" :
					$user['User']['gender'] = $value;
					break;
				case "email" :
					$user['User']['email'] = $value;
					$user['User']['username'] = $value;
					break;
				case "identifier" :
					//$user['UserSocialNetwork']['identifier']= $value;
					$user['UserSocialNetwork']['social_net_id'] = $value;
					break;
				case "photoURL" :
					if ($provider == 'twitter') {
						$user['UserPhoto'] = array('file' => $value);
					}
					break;
				case "city" :
					//$user['UserDetail']['city']= $value;
					break;
			}
		}
		$user['User']['full_name'] = $user['User']['first_name'] . ' ' . $user['User']['last_name'];
		if (!empty($photo)) {
			$user['UserPhoto'] = $photo;
		}
		if (empty($user['User']['username'])) {
			$user['User']['username'] = $this->getDummyEmailForMissing($user, $provider);
		}
		if (empty($user['User']['email'])) {
			$user['User']['email'] = $user['User']['username'];
		}
		$adminRoleId = 1;
		if (empty($defaultRoleId) || $defaultRoleId == $adminRoleId) {
			$defaultRoleId = defined('__APP_DEFAULT_USER_REGISTRATION_ROLE_ID') ? __APP_DEFAULT_USER_REGISTRATION_ROLE_ID : 5;
		}
		$user['User']['user_role_id'] = $defaultRoleId;
		$user['User']['password'] = $this->__randPass();
		return $user;
	}

	public function getDummyEmailForMissing($user, $provider) {
		return $user['UserSocialNetwork']['social_net_id'] . '_' . $provider;
	}

	private function __getProfilePicture($adapter, $type = null, $provider, $alreadyLoggedin) {
		if ($provider != 'facebook') {
			return null;
		}
		try {
			$params = array('fields' => 'picture');
			if (!empty($type)) {
				$params['type'] = $type;
			}
			if (!$alreadyLoggedin) {
				$urlToD = $adapter->api()->api('/me', array('fields' => 'picture.type(large)'));
			} else {
				$urlToD = $adapter->adapter->api->api('/me', array('fields' => 'picture.type(large)'));
			}
			if (!empty($urlToD) && isset($urlToD['picture'])) {
				if (isset($urlToD['picture']['data']) && isset($urlToD['picture']['data']['url']) && (!isset($urlToD['picture']['data']['is_silhouette']) || empty($urlToD['picture']['data']['is_silhouette']) || $urlToD['picture']['data']['is_silhouette'] == 'false')) {
					return array('file' => $urlToD['picture']['data']['url']);
				} else {
					return array('file' => $urlToD['picture']);
				}
			}
		} catch(FacebookApiException $e) {
			throw $e;
		}
	}

	public function getUserContacts($provider, $alreadyLoggedin = false) {
		if ($alreadyLoggedin && $provider == 'facebook') {
			return $this->__getFbNativeUserContacts($this->authAdapter);
		} else {
			return $this->authAdapter->getUserContacts();
		}
	}

	/**
	 * load the user profile from the IDp api client
	 */
	private function __getUserNativeFbProfile($adapter) {
		// request user profile from fb api
		try {
			$data = $adapter->adapter->api->api('/me');
		} catch( FacebookApiException $e ) {
			throw new Exception("User profile request failed! {$this->providerId} returned an error: $e", 6);
		}
		// if the provider identifier is not recived, we assume the auth has failed
		if (!isset($data["id"])) {
			throw new Exception("User profile request failed! {$this->providerId} api returned an invalid response.", 6);
		}
		# store the user profile.
		$uProfile = new stdClass();
		$uProfile->identifier = (array_key_exists('id', $data)) ? $data['id'] : "";
		$uProfile->displayName = (array_key_exists('name', $data)) ? $data['name'] : "";
		$uProfile->firstName = (array_key_exists('first_name', $data)) ? $data['first_name'] : "";
		$uProfile->lastName = (array_key_exists('last_name', $data)) ? $data['last_name'] : "";
		$uProfile->photoURL = "https://graph.facebook.com/" . $uProfile->identifier . "/picture?width=150&height=150";
		$uProfile->profileURL = (array_key_exists('link', $data)) ? $data['link'] : "";
		$uProfile->webSiteURL = (array_key_exists('website', $data)) ? $data['website'] : "";
		$uProfile->gender = (array_key_exists('gender', $data)) ? $data['gender'] : "";
		$uProfile->description = (array_key_exists('bio', $data)) ? $data['bio'] : "";
		$uProfile->email = (array_key_exists('email', $data)) ? $data['email'] : "";
		$uProfile->emailVerified = (array_key_exists('email', $data)) ? $data['email'] : "";
		$uProfile->region = (array_key_exists("hometown", $data) && array_key_exists("name", $data['hometown'])) ? $data['hometown']["name"] : "";
		if (array_key_exists('birthday', $data)) {
			list($birthday_month, $birthday_day, $birthday_year) = explode("/", $data['birthday']);
			$uProfile->birthDay = (int)$birthday_day;
			$uProfile->birthMonth = (int)$birthday_month;
			$uProfile->birthYear = (int)$birthday_year;
		}
		return $uProfile;
	}

	/**
	 * load the user contacts
	 */
	private function __getFbNativeUserContacts($adapter) {
		try {
			$response = $adapter->adapter->api->api('/me/friends');
		} catch( FacebookApiException $e ) {
			throw new Exception("User contacts request failed! returned an error: $e");
		}
		if (!$response || !count($response["data"])) {
			return ARRAY();
		}
		$contacts = ARRAY();
		foreach ($response["data"] as $item) {
			$uc = new Hybrid_User_Contact();
			$uc->identifier = (array_key_exists("id", $item)) ? $item["id"] : "";
			$uc->displayName = (array_key_exists("name", $item)) ? $item["name"] : "";
			$uc->profileURL = "https://www.facebook.com/profile.php?id=" . $uc->identifier;
			$uc->photoURL = "https://graph.facebook.com/" . $uc->identifier . "/picture?width=150&height=150";
			$contacts[] = $uc;
		}
		return $contacts;
	}

	/**
	 * Random strong string password generator
	 * @param int length
	 * @return string password
	 */
	private function __randPass($length = 8) {
		return substr(md5(rand() . rand()), 0, $length);
	}

}
