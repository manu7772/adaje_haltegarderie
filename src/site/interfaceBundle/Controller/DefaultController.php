<?php

namespace site\interfaceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

use \Exception;

class DefaultController extends Controller {

	const TYPE_SELF 			= '_self';
	const DEFAULT_ACTION 		= 'list';
	const TYPE_VALUE_JOINER 	= '___';

	public function indexAction() {
		$data = array();
		return $this->render('siteinterfaceBundle:Default:index.html.twig', $data);
	}

	public function supportAction() {
		$data = array();
		return $this->render('siteinterfaceBundle:Default:support.html.twig', $data);
	}

	//**************//
	// BLOCKS       //
	//**************//

	public function headerAction($option = null) {
		$data = array();
		$stack = $this->get('request_stack');
		$masterRequest = $stack->getMasterRequest();
		$data['infoRoute']['_route'] = $masterRequest->get('_route');
		$data['infoRoute']['_route_params'] = $masterRequest->get('_route_params');
		return $this->render('siteinterfaceBundle:blocks:header.html.twig', $data);
	}

	public function sidebarAction($option = null) {
		$data = array();
		// $data['roles'] = $this->get('labo_user_roles')->getListOfRoles();
		// variables diverses
		$data['typeSelf'] = self::TYPE_SELF;
		$data['type_value_joiner'] = self::TYPE_VALUE_JOINER;
		return $this->render('siteinterfaceBundle:blocks:sidebar.html.twig', $data);
	}


}
