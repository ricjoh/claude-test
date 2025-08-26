<?php

use Phalcon\Acl;
use Phalcon\Acl\Role;
use Phalcon\Acl\Resource;
use Phalcon\Events\Event;
use Phalcon\Mvc\User\Plugin;
use Phalcon\Mvc\Dispatcher;
use Phalcon\Acl\Adapter\Memory as AclList;

/**
 * SecurityPlugin
 *
 * This is the security plugin which controls that users only have access to the modules they're assigned to
 */
class SecurityPlugin extends Plugin {

	const ACL_MEMCACHED_KEY = 'OSH_ACL';

	/**
	 * Get the ACL object
	 */
	public function getAcl() {
		if ($this->config->settings['environment'] === 'DEVELOPMENT' || !class_exists('Memcached')) {
			// if memcached is not available, just create the ACL config
			$acl = $this->createAcl();
		} else {
			try {
				// check if ACL config is cached
				$memcacheD = new Memcached();
				$memcacheD->addServer('localhost', 11211);
				$cachedResult = $memcacheD->get(self::ACL_MEMCACHED_KEY);
			} catch (Exception $e) {
				$this->logger->log('Error while trying to get cached ACL config: ' . $e->getMessage());
			}

			// this will short circuit if there was an exception
			if (!empty($cachedResult) && $memcacheD->getResultCode() == 0) {
				// cached
				$acl = unserialize($cachedResult);
			} else {
				// not cached, create ACL config
				$acl = $this->createAcl();

				try {
					// cache it
					$memcacheD->set(self::ACL_MEMCACHED_KEY, serialize($acl));
				} catch (Exception $e) {
					$this->logger->log('Error while trying to cache ACL config: ' . $e->getMessage());
				}
			}
		}

		return $acl;
	}

	private function createAcl() {
		$acl = new AclList();
		$acl->setDefaultAction(Acl::ALLOW);

		// add resources - resource name matches first url parameter (or 'index' for '/')
		// permission is just 'access' for now, which controls access to all pages under the resource
		$acl->addResource(new Resource('user'), [ 'access' ]);

		// add roles from Parameters
		$paramModel = new Parameter();
		$roles = $paramModel->getValuesForGroupId(
			'F9626EBA-6D4A-408D-BDF6-9A8FFBB00631',
		);

		// deny access to pages under /user/ if current user is not admin
		foreach ($roles as $role) {
			$acl->addRole(new Role($role['ParameterID']));

			if ($role['ParameterID'] != User::SU_ROLE_PID) {
				$acl->deny($role['ParameterID'], 'user', 'access');
			}
		}

		return $acl;
	}

	/**
	 * Check if a given user has the given role
	 * @param string $role The Role PID to check
	 * @param User $user If not provided, will use the current user
	 * @return bool Whether the user has the role
	 */
	public function hasRole($role, $user = null) {
		if (!$user) {
			$userAuth = $this->session->get('userAuth');
			if (!$userAuth) {
				return false;
			}

			// get current user role
			$user = User::findFirst([
				'conditions' => "UserID = '" . $userAuth['UserID'] . "'"
			]);
		}

		return $user->RolePID == $role;
	}

	/**
	 * Check if a given user has the given permission on the given resource
	 * @param string $permission
	 * @param string $resource
	 * @param User $user If not provided, will use the current user
	 * @return bool Whether the user has the permission
	 */
	public function hasPermission($permission, $resource, $user = null) {
		if (!$user) {
			$userAuth = $this->session->get('userAuth');
			if (!$userAuth) {
				return false;
			}

			// get current user role
			$user = User::findFirst([
				'conditions' => "UserID = '" . $userAuth['UserID'] . "'"
			]);
		}

		$userRole = $user->RolePID;

		// make sure role is valid
		$roleParam = Parameter::findFirst([
			'conditions' => "ParameterGroupID = 'F9626EBA-6D4A-408D-BDF6-9A8FFBB00631' AND ParameterID = '" . $userRole . "'"
		]);

		if (!$roleParam) {
			$this->logger->log("Error: User RolePID is not valid!\nUserID: " . $userAuth['UserID'] . "\nRolePID: " . $user->RolePID);
			return false;
		}

		$acl = $this->getAcl();

		return $acl->isAllowed($userRole, $resource, $permission);
	}

	/**
	 * This action is executed before any action in the application
	 *
	 * @param Event $event
	 * @param Dispatcher $dispatcher
	 */
	public function beforeDispatch(Event $event, Dispatcher $dispatcher) {
		// $controller = $dispatcher->getControllerName();
		// $action = $dispatcher->getActionName();

		// $this->logger->log('running beforeDispatch');
		// $this->logger->log($_SERVER['REQUEST_URI']);
		$url = $_SERVER['REQUEST_URI'];

		if (preg_match("/^\/clients/i", $url)) // customer login
		{
			$customerAuth = $this->session->get('customerAuth');
			if (!$customerAuth && !preg_match("/^\/clients\/login/i", $url)) {
				// $this->logger->log('customer not logged in, redirecting...');
				return $this->response->redirect('clients/login?url_after_login=' . urlencode($url));
			}
		} else // user login
		{

			// TODO: check $_SERVER[ 'REMOTE_ADDR'] vs $_SERVER[ 'HOST_ADDR' ] before allowing anything but login.
			$userAuth = $this->session->get('userAuth');
			if (
				!$userAuth && !preg_match("#^/login#", $url) &&
				!preg_match("#^/offer/expire#", $url) &&
				!preg_match("#^/lot/createdraft#", $url) &&
				!preg_match("#^/lot/archive#", $url) &&
				!preg_match("#^/vat/addvat#", $url) &&
				!preg_match("#^/offer/save#", $url) &&
				!preg_match("#^/offer/newitem#", $url) &&
				!preg_match("#^/offer/saveitemvats#", $url) &&
				!preg_match("#^/edidocument/generatex12#", $url) &&
				!preg_match("#^/edidocument/add#", $url) &&
				!preg_match("#^/edidocument/makex12#", $url)
			) {
				// $this->logger->log('user not logged in, redirecting...');
				return $this->response->redirect('login?url_after_login=' . urlencode($url));
			}

			if ($userAuth) {
				// get resource from url
				$urlParts = explode('/', $url);
				$resource = $urlParts[1];
				if (empty($resource)) {
					$resource = 'index';
				}

				// check if user is allowed to access the resource
				if (!$this->hasPermission('access', $resource)) {
					// $this->logger->log('user not allowed to access resource, redirecting...');
					if ($resource == 'index') {
						return $this->response->redirect('/logout');
					}

					return $this->response->redirect('/');
				}
			}
		}
	}
}
