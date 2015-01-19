<?php
App::uses('GenericAuthAppController', 'GenericAuth.Controller');
/**
 * Generic component to handle different ways of authentication especially social network authentication using(OpenId and OAuth2)
 * This is basically wrapper around third party implementation
 * To beging with it is cakephp wrappen for HybridAuth implementation.
 * Please refer http://hybridauth.sourceforge.net/ for details about HybridAuth
 * This basically delegates to the HybriAuth library and then gets the user profile from it
 * User profile is then conveted to User model data and then saved in the database if not already available
 * It is then passed through normal cake authentication mechanism to take over
 * Google OAuth2 step
 * * http://hybridauth.sourceforge.net/userguide/IDProvider_info_Google.html
 * * https://developers.google.com/accounts/docs/OAuth2?hl=fr
 * * https://developers.google.com/accounts/docs/OAuth2
 * * https://developers.google.com/console/help/#UsingOAuth2
 * @author sachin
 */
class SocialAuthController extends GenericAuthAppController {

	public $components =array('GenericAuth.SocialAuth');

	public $uses = array('Users.User','GenericAuth.UserSocialNetwork');//Associate with User Model.

	public function beforeFilter(){
		$this->Auth->allow('index', 'authenticate');
		//parent::beforeFilter();
	}

	public function index(){
		$this->SocialAuth->process_authentication();
	}
/**
 * Entry points for authetication through generic auth.
 * Provider cab be google, facebook etc
 * @param string $provider
 * @param boolean $doRedirect
 * @param string $redirUrl landing url post authentication, defaults to auth login redirect url
 * @param string $authId auth handles e.g facebookid. Useful when using facebook javascript and php sdk together wherein js can pass handle
 */
	public function authenticate($provider, $defaultRoleId = null, $doRedirect = true, $redirUrl = null, $authId = null) {
		$alreadyLoggedin = false;
		$this->log('starting common auth');
		$isFirstTimeAccess = false;
		if(empty($redirUrl)){
			// not sure what removing this might break // $userProfile['UserSocialNetwork']['auth_response_data'] = $this->__getPostAuthLandingUrl();
			$redirUrl = $this->__getPostAuthLandingUrl();
		}

		$userProfile = $this->SocialAuth->authenticate($provider, $alreadyLoggedin, $defaultRoleId);//Gets the user data if authenticated by generic auth
		if (!empty($userProfile)) {
			$authUsr = $this->getLoggedInUser();
			$this->log($authUsr);
			$userSocialNetwork = $this->UserSocialNetwork->getDetailsByAuthIdAndProvider($userProfile['UserSocialNetwork']['social_net_id'],$provider);

			// Check if user is already provisioned in our database. If not then save the record in our database
			if (empty($userSocialNetwork)) {
				if (!empty($authUsr) && $userProfile['User']['email'] == $authUsr['User']['email']){//Alredy logged in to the application with the same email as with this social login. Just associate with this social record with loggedin record
					$userId = $authUsr['User']['id'];
				} else {
					$user = $this->User->findByUsername($userProfile['User']['email']);//Check if this user with the email is registered
					if(empty($user)){
						//If user with no email is in our database then save the user record
						//$this->User->save($userProfile);
						$this->User->add($userProfile['User'], array('validate' => false));
						$this->log($this->User->validatorErrors);
						$userId = $this->User->id;


						$user=$this->User->findById($userId);
						$user['User']['email'] = $user['User']['username'];
						if(strpos($userProfile['User']['email'],"@") !==false){
							//EventManager::fireUserCreatedEvent($user, array());
						}
						$isFirstTimeAccess = true;
					} else {
						$userId = $user['User']['id'];
					}
				}
				//Create new social network record for this user for this particular provider
				$userProfile['UserSocialNetwork']['user_id'] = $userId;
				$userContacts = $this->SocialAuth->getUserContacts($provider, $alreadyLoggedin);
				//$this->log($userContacts);
				$userProfile['UserSocialNetwork']['contacts_data'] = json_encode($userContacts);
				$this->UserSocialNetwork->save($userProfile['UserSocialNetwork']);
			} else {
				$userId = $userSocialNetwork['UserSocialNetwork']['user_id'];
				//Get users contact list from corresponding social network if its not already present
				if(empty($userSocialNetwork['UserSocialNetwork']['contacts_data']) || strlen($userSocialNetwork['UserSocialNetwork']['contacts_data']) <= 2 ){
					$userContacts = $this->SocialAuth->getUserContacts($provider, $alreadyLoggedin);
					//$this->log($userContacts);
					$this->log(strlen($userSocialNetwork['UserSocialNetwork']['contacts_data']));
					$upUsN = array('id' => $userSocialNetwork['UserSocialNetwork']['id'],'contacts_data' => json_encode($userContacts));
					if(!empty($userContacts)){
						$this->UserSocialNetwork->save($upUsN);
					}
				}
			}
			//Alredy logged in to the application with different email as with this social login. logout and treat as new user
			if(!empty($authUsr) && $userProfile['User']['email'] != $authUsr['User']['username']){
				$authUsr = array();
				//$this->Session->destroy();
				$this->Auth->logout();
			}
			//If not already authenticated with cake auth then do manual auth login
			if(empty($authUsr)){
				$user = $this->User->findById($userId);//get the provisioned user record from our database
				$this->Auth->login($user['User']);//Now let cake auth to take over providing the user data
			}
			$authUsr=$this->getLoggedInUser();
			if(!empty($authUsr) && $authUsr['User']['id'] != $userId ){
				$upUsN = array('id' => $userSocialNetwork['UserSocialNetwork']['id'],'user_id' =>  $authUsr['User']['id']);
				$this->UserSocialNetwork->save($upUsN);
			}

			$this->log('rediecting to '.$redirUrl);
			if($doRedirect){
				$this->Session->setFlash(__('Successfully logged in with %s', Inflector::humanize($provider)), 'flash_success');
				return $this->redirect($redirUrl);//take user to redirect url
			}
		}
	}

	private function __getPostAuthLandingUrl(){
		$redcUrl = null;
		if(isset($this->request->query['rUrl']) && !empty($this->request->query['rUrl'])){
			$redcUrl = $this->request->query['rUrl'];
		}else if(isset($this->request->data['rUrl']) && !empty($this->request->data['rUrl'])){
			$redcUrl = $this->request->data['rUrl'];
		}
		if(is_null($redcUrl) || empty($redcUrl)){
			//$redcUrl = $this->Auth->redirect();
			$redcUrl = array('plugin' => 'users', 'controller' => 'users', 'action' => 'my');
		}
		return $redcUrl;
	}

/**
 * Returns the current logged in user from the session
 * Will need changes as we move to Auth component
 * @return User
 */
	protected function getLoggedInUser(){
		$curUser=$this->Auth->user();
		if(!empty($curUser) && empty($curUser['User'])){
			$curUser['User']=$this->Auth->user();
			//$this->__storeUserPhotoInSession($curUser);

		}

		return $curUser;
	}

/**
 * Gets the contacts(friends) list
 */
	public function contacts($provider){
		//$this->autoLayout = null;
		$this->layout = 'no_header_footer';
		$this->authenticate($provider,false);
		$authUsr=$this->getLoggedInUser();
		$userSocialNetwork = $this->User->UserSocialNetwork->getUserByUserIdAndProvider($authUsr['User']['id'],$provider);
		$this->set("contactList",$userSocialNetwork['UserSocialNetwork']['contacts_data']);

	}
}
