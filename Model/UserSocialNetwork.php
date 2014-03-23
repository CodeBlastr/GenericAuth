<?php
App::uses('GenericAuthAppModel', 'GenericAuth.Model');
/**
 * UserSocialNetwork Model
 *
 * @property User $User
 */
class UserSocialNetwork extends GenericAuthAppModel {

/**
 * Validation rules
 *
 * @var array
 */
	public $validate = array(
		'type' => array(
			'notempty' => array(
				'rule' => array('notempty'),
				//'message' => 'Your custom message here',
				//'allowEmpty' => false,
				//'required' => false,
				//'last' => false, // Stop validation after this rule
				//'on' => 'create', // Limit validation to 'create' or 'update' operations
			),
		),
	);

/**
 * belongsTo associations
 *
 * @var array
 */
	public $belongsTo = array(
		'User' => array(
			'className' => 'Users.User',
			'foreignKey' => 'user_id',
			'conditions' => '',
			'fields' => '',
			'order' => ''
		)
	);

/**
 * Get details by auth id and provider
 */
	public function getDetailsByAuthIdAndProvider($authId, $provider){
		$this->Behaviors->attach('Containable');
		$this->contain();
		$userSocialNetwork = $this->find("first",array('conditions' => array('UserSocialNetwork.social_net_id' => $authId,'UserSocialNetwork.type' => $provider)));
		return $userSocialNetwork;
	}

/**
 * Get details by User Id and Provider
 */
	public function getDetailsByUserIdAndProvider($userId, $provider){
		$this->Behaviors->attach('Containable');
		$this->contain();		
		$userSocialNetwork = $this->find("first",array('conditions' => array('UserSocialNetwork.user_id' => $userId, 'UserSocialNetwork.type' => $provider)));
		return $userSocialNetwork;
	}	
}
