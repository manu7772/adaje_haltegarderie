<?php

namespace site\siteBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

use site\adminBundle\Entity\message;
use site\adminBundle\Form\contactmessageType;
use \DateTime;

class DefaultController extends Controller {

	public function indexAction() {
		$this->em = $this->getDoctrine()->getManager();
		$this->repo = $this->em->getRepository('site\adminBundle\Entity\pageweb');
		$data['pageweb'] = $this->repo->findOneByHomepage(1);
		// chargement de la pageweb
		if(is_object($data['pageweb'])) {
			return $this->render($data['pageweb']->getTemplate(), $data);
		} else {
			// si aucune page web… chargement de la page par défaut…
			$userService = $this->get('service.users');
			$userService->usersExist(true);
			return $this->redirect($this->generateUrl('generate'));
		}
	}

	public function pagewebAction($pageweb, $params = null) {
		$this->em = $this->getDoctrine()->getManager();
		// if($params == null) $params = array();
		$data = $this->get('tools_json')->JSonExtract($params);
		$data['pageweb'] = $pageweb;
		$this->pagewebactions($data);
		// find $pageweb
		$this->repo = $this->em->getRepository('site\adminBundle\Entity\pageweb');
		$data['pageweb'] = $this->repo->findOneBySlug($pageweb);
		// chargement de la pageweb
		return $this->render($data['pageweb']->getTemplate(), $data);
	}

	protected function pagewebactions(&$data) {
		switch ($data['pageweb']) {
			case 'contact':
				// page contact
				$message = $this->getNewEntity('site\adminBundle\Entity\message');
				$form = $this->createForm(new contactmessageType($this, []), $message);
				// $this->repo = $this->em->getRepository('site\adminBundle\Entity\message');
				$request = $this->getRequest();
				if($request->getMethod() == 'POST') {
					// formulaire reçu
					$form->bind($request);
					if($form->isValid()) {
						// get IP & DateTime
						$message->setIp($request->getClientIp());
						$message->setCreation(new DateTime());
						// enregistrement
						$this->em->persist($message);
						$this->em->flush();
						$data['message_success'] = "message.success";
						// nouveau formulaire
						$new_message = new message();
						$new_message->setNom($message->getNom());
						$new_message->setEmail($message->getEmail());
						// $new_message->setObjet($message->getObjet());
						$form = $this->createForm(new contactmessageType($this, []), $new_message);
					} else {
						$data['message_error'] = "message.error";
					}
				}
				$data['message_form'] = $form->createView();
				break;
			
			default:
				# code...
				break;
		}
		return $data;
	}

	protected function getNewEntity($classname) {
		$newEntity = new $classname();
		$this->em = $this->getDoctrine()->getManager();
		if(method_exists($newEntity, 'setStatut')) {
			// si un champ statut existe
			$inactif = $this->em->getRepository('site\adminBundle\Entity\statut')->defaultVal();
			$newEntity->setStatut($inactif);
		}
		return $newEntity;
	}

	public function topmenuAction($levels = 0, $icons = true) {
		$data['menu'] = $this->get('aeMenus')->getMenu('site-menu');
		$data['options']['levels'] = $levels;
		$data['options']['icons'] = $icons;
		// récupération route/params requête MASTER
		$stack = $this->get('request_stack');
		$masterRequest = $stack->getMasterRequest();
		$data['infoRoute']['_route'] = $masterRequest->get('_route');
		$data['infoRoute']['_route_params'] = $masterRequest->get('_route_params');
		return $this->render('sitesiteBundle:blocks:topmenu.html.twig', $data);
	}

	public function sidemenuAction() {
		$user = $this->getUser();
		if(is_object($user)) {
			// récupération route/params requête MASTER
			$stack = $this->get('request_stack');
			$masterRequest = $stack->getMasterRequest();
			$data['infoRoute']['_route'] = $masterRequest->get('_route');
			$data['infoRoute']['_route_params'] = $masterRequest->get('_route_params');
			// menu
			$data['menu'] = $this->get('aeMenus')->getMenu('admin-sidemenu');
			$data['menu']['menu']['menu']['users']['path']['params']['name'] = $user->getUsername();
			return $this->render('sitesiteBundle:blocks:sidemenu.html.twig', $data);
		} else {
			return new Response(null);
		}
	}

}
