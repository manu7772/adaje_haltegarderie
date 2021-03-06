<?php
namespace site\interfaceBundle\services;

use \Twig_Extension;
use \Twig_SimpleFunction;

use site\UserBundle\Entity\User;

/**
 * Service Roles
 * - Gestion des Roles users
 */
class RolesService extends Twig_Extension {

	protected $rolesHierarchy;
	protected $roleNames;
	protected $colors;

	public function __construct($rolesHierarchy) {
		$this->rolesHierarchy = $rolesHierarchy;
		$this->roleNames = array(
			'ROLE_USER'					=> 'inscrit',
			'ROLE_TRANSLATOR'			=> 'traducteur',
			'ROLE_EDITOR'				=> 'éditeur',
			'ROLE_ADMIN'				=> 'administrateur',
			'ROLE_SUPER_ADMIN'			=> 'super admin',
			'ROLE_ALLOWED_TO_SWITCH'	=> 'autorisé switch',
			'ERROR'						=> 'tous',
			);
		$this->colors = array(
			'ROLE_USER'					=> 'info',
			'ROLE_TRANSLATOR'			=> 'success',
			'ROLE_EDITOR'				=> 'success',
			'ROLE_ADMIN'				=> 'warning',
			'ROLE_SUPER_ADMIN'			=> 'danger',
			'ROLE_ALLOWED_TO_SWITCH'	=> 'primary',
			'ERROR'						=> 'muted',
			);
	}

	public function getFunctions() {
		return array(
			new Twig_SimpleFunction('userRoles', array($this, 'getRoles')),
			new Twig_SimpleFunction('userAllRoles', array($this, 'getAllRoles')),
			new Twig_SimpleFunction('roleName', array($this, 'getRoleName')),
			new Twig_SimpleFunction('roleNames', array($this, 'getRoleNames')),
			new Twig_SimpleFunction('roleColor', array($this, 'getRoleColor')),
			new Twig_SimpleFunction('roleColors', array($this, 'getRoleColors')),
			new Twig_SimpleFunction('roleRights', array($this, 'getRoleRights')),
			new Twig_SimpleFunction('roleList', array($this, 'getListOfRoles')),
			new Twig_SimpleFunction('userColor', array($this, 'getUserColor')),
			);
	}

	public function getName() {
		return 'RolesService';
	}

	public function getRoles() {
		return array_keys($this->rolesHierarchy);
	}

	public function getAllRoles() {
		$roles = array();
		array_walk_recursive($this->rolesHierarchy, function($val) use (&$roles) {
			$roles[] = $val;
		});
		foreach ($this->rolesHierarchy as $key => $value) {
			$roles[] = $key;
		}
		return array_unique($roles);
	}

	/**
	 * Renvoie le même role s'il est valide, sinon 'ERROR'
	 * @param string $role
	 * @return string
	 */
	public function verifRole($role) {
		$roles = $this->getRoles();
		return in_array($role, $roles) ? $role : 'ERROR';
	}

	public function getRoleName($role = 'ERROR') {
		return isset($this->roleNames[$role]) ? $this->roleNames[$role] : $this->roleNames['ERROR'];
	}

	public function getRoleNames() {
		return $this->roleNames;
	}

	public function getRoleColor($role = 'ERROR') {
		return isset($this->colors[$role]) ? $this->colors[$role] : $this->colors['ERROR'];
	}

	public function getRoleColors() {
		return $this->colors;
	}

	public function getUserColor(User $user) {
		$color = 'muted'; // défaut
		$roles = $user->getRoles();
		foreach ($this->getRoles() as $role) {
			if(in_array($role, $roles)) $color = $this->getRoleColor($role);
		}
		return $color;
	}

	/**
	 * Renvoie tous les droits d'un ROLE
	 * @param string $role
	 * @return array / false si erreur
	 */
	public function getRoleRights($role) {
		return isset($this->rolesHierarchy[$role]) ? $this->rolesHierarchy[$role] : false;
	}

	public function getListOfRoles() {
		$arrayRoles = array();
		$roles = $this->getRoles();
		foreach ($roles as $role) {
			$arrayRoles[$role]['libelle'] = $this->getRoleName($role);
			$arrayRoles[$role]['rights'] = $this->getRoleRights($role);
		}
		return $arrayRoles;
	}

	// public function addRole(User &$user, $role) {
	// 	if(in_array($role, haystack))
	// }

	public function checkRoles(User &$user, $save = false) {
		//
		$user->addRole('ROLE_USER');
	}

}