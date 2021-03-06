<?php

namespace site\interfaceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

use site\interfaceBundle\services\flashMessage;

use \Exception;

class entiteController extends Controller {

	const TYPE_SELF 			= '_self';
	const DEFAULT_ACTION 		= 'list';
	const TYPE_VALUE_JOINER 	= '___';


	protected function getEntiteData($entite, $type_related = null, $type_field = null, $type_values = null, $action = null, $id = null) {
		if($action == null) $action = self::DEFAULT_ACTION;
		if(is_object($entite)) $entite = get_class($entite);
		$data = array();
		$exp = explode('\\', $entite);
		if(count($exp) > 1) {
			$data['classname'] = $entite;
			$data['entite_name'] = end($exp);
		} else {
			$data['classname'] = 'site\\adminBundle\\Entity\\'.$entite;			
			$data['entite_name'] = $entite;
		}
		$data['type']['type_related']	= $type_related;
		$data['type']['type_field']		= $type_field;
		$data['type']['type_values']	= $this->typeValuesToArray(urldecode($type_values));
		$data['action'] = $action;
		$data['entite'] = false;
		$data['entites'] = array();
		$data['id'] = $id;
		// EM
		$this->em = $this->getDoctrine()->getManager();
		$this->repo = $this->em->getRepository($data['classname']);
		// autres éléments…
		switch ($data['entite_name']) {
			case '...':
				# code...
				break;
			
			default:
				# code...
				break;
		}
		// variables diverses
		$data['typeSelf'] = self::TYPE_SELF;
		$data['type_value_joiner'] = self::TYPE_VALUE_JOINER;
		return $data;
	}

	protected function typeValuesToArray($type_values = null) {
		if($type_values != null) $type_values = explode(self::TYPE_VALUE_JOINER, $type_values);
		return $type_values;
	}
	protected function typeValuesToString($type_values = null) {
		if($type_values != null) $type_values = implode(self::TYPE_VALUE_JOINER, $type_values);
		return $type_values;
	}

	public function entitePageAction($entite, $type_related = null, $type_field = null, $type_values = null, $action = null, $id = null) {
		if($action == null) $action = self::DEFAULT_ACTION;
		
		$data = $this->getEntiteData($entite, $type_related, $type_field, $type_values, $action, $id);
		// EM
		$this->em = $this->getDoctrine()->getManager();
		$this->repo = $this->em->getRepository($data['classname']);
		// actions selon action…
		switch ($data['action']) {
			case 'create' :
				$data['entite'] = $this->getNewEntity($data['classname']);
				$data[$data['action'].'_form'] = $this->getEntityFormView($data);
				break;
			case 'show' :
				$data['entite'] = $this->repo->find($id);
				if(!is_object($data['entite'])) throw new Exception($data['entite_name'].'.not_found', 1);
				break;
			case 'edit' :
				$data['entite'] = $this->repo->find($id);
				if(!is_object($data['entite'])) throw new Exception($data['entite_name'].'.not_found', 1);
				$data[$data['action'].'_form'] = $this->getEntityFormView($data);
				break;
			case 'check' :
				// autres actions selon entité…
				switch ($data['entite_name']) {
					case 'media' :
						// media : service check
						$check_media = $this->get('aetools.media')->check_media($data);
						$message = $this->get('flash_messages')->send($check_media);
						$data['action'] = null;
						$data['id'] = null;
						break;
					default:
						// toutes autres entités
						if($data['id'] == null) {
							if($data['type']['type_values'] != null) {
								$data['entites'] = $this->repo->findByField($data['type'], self::TYPE_SELF, true);
							} else {
								$data['entites'] = $this->repo->findAll();
							}
						} else {
							array_push($data['entites'], $this->repo->find($data['id']));
						}
						if(count($data['entites']) > 0) {
							foreach ($data['entites'] as $key => $entite) {
								// test de check sur l'entité directement
								if(method_exists($entite, "check")) $entite->check();
							}
							$this->em->flush();
							if(count($data['entites']) == 1) {
								$nn = reset($data['entites']);
								if(method_exists($nn, 'getNom')) $nn = $nn->getNom();
									else if(method_exists($nn, 'getSlug')) $nn = $nn->getSlug();
									else $nn = $nn->getId();
							} else $nn = $data['entite_name']." (".count($data['entites'])." éléments)";
							$message = $this->get('flash_messages')->send(array(
								'title'		=> 'Check terminé',
								'type'		=> flashMessage::MESSAGES_WARNING,
								'text'		=> 'L\'opération de checking sur '.$nn.' est terminée.',
							));
						} else {
							$message = $this->get('flash_messages')->send(array(
								'title'		=> 'Check non effectué',
								'type'		=> flashMessage::MESSAGES_ERROR,
								'text'		=> 'Auncun élément à checker.',
							));
						}
						$data['action'] = null;
						$data['id'] = null;
						break;
				}
				return $this->redirect($this->generateEntityUrl($data));
				break;
			case 'delete' :
				$data['entite'] = $this->repo->find($id);
				if(!is_object($data['entite'])) {
					$message = $this->get('flash_messages')->send(array(
						'title'		=> 'Élément introuvable',
						'type'		=> flashMessage::MESSAGES_ERROR,
						'text'		=> 'L\'élément est introuvable et ne peut être supprimé.',
					));
					$data['action'] = null;
					$data['id'] = null;
				} else {
					if(method_exists($data['entite'], 'setStatut')) {
						// si un champ statut existe
						$inactif = $this->em->getRepository('site\adminBundle\Entity\statut')->findInactif();
						$data['entite']->setStatut($inactif);
					} else {
						// sinon on la supprime
						$this->em->remove($data['entite']);
					}
					$this->em->flush();
					$message = $this->get('flash_messages')->send(array(
						'title'		=> 'Élément supprimé',
						'type'		=> flashMessage::MESSAGES_WARNING,
						'text'		=> 'L\'élément a été supprimé.',
					));
					$data['action'] = null;
					$data['id'] = null;
				}
				return $this->redirect($this->generateEntityUrl($data));
				break;
			case 'active' :
				$data['entite'] = $this->repo->find($id);
				if(!is_object($data['entite'])) {
					$message = $this->get('flash_messages')->send(array(
						'title'		=> 'Message introuvable',
						'type'		=> flashMessage::MESSAGES_ERROR,
						'text'		=> 'Le message est introuvable et ne peut être supprimé.',
					));
					$data['action'] = null;
					$data['id'] = null;
				} else {
					if(method_exists($data['entite'], 'setStatut')) {
						// si un champ statut existe
						$actif = $this->em->getRepository('site\adminBundle\Entity\statut')->findActif();
						$data['entite']->setStatut($actif);
					}
					$this->em->flush();
					$message = $this->get('flash_messages')->send(array(
						'title'		=> 'Message activé',
						'type'		=> flashMessage::MESSAGES_SUCCESS,
						'text'		=> 'Le message a été activé.',
					));
					$data['action'] = 'show';
				}
				return $this->redirect($this->generateEntityUrl($data));
				break;
			default:
				// DEFAULT_ACTION
				$data['entites'] = $this->repo->findAll();
				// selon entité
				switch ($data['entite_name']) {
					case 'tag':
						$memAction = $data['action'];
						$data['action'] = 'create';
						$data['entite'] = $this->getNewEntity($data['classname']);
						$data[$data['action'].'_form'] = $this->getEntityFormView($data);
						$data['action'] = $memAction;
						break;
					default:
						# code...
						break;
				}
				break;
		}

		$template = 'siteinterfaceBundle:entites:'.$entite.ucfirst($data['action']).'.html.twig';
		if(!$this->get('templating')->exists($template)) {
			$template = 'siteinterfaceBundle:entites:'.'entite'.ucfirst($data['action']).'.html.twig';
		}
		return $this->render($template, $data);
	}

	/**
	 * Renvoie une URL selon les paramètres de $data
	 * @param array $data
	 * @return string
	 */
	protected function generateEntityUrl($data) {
		if($data['type']['type_related'] != null) {
			// avec type
			return $this->generateUrl('siteadmin_entite_type', array('entite' => $data['entite_name'], 'type_related' => $data['type_related'], 'type_field' => $data['type_field'], 'type_values' => $this->typeValuesToString($data['type_values']), 'action' => $data['action'], 'id' => $data['id']));
		} else {
			// sans type
			return $this->generateUrl('siteadmin_entite', array('entite' => $data['entite_name'], 'action' => $data['action'], 'id' => $data['id']));
		}
	}

	/**
	 * Actions en retour de formulaire entity
	 * @param string $classname
	 * @param string $action
	 * @param string $id
	 * @return Response
	 */
	public function entitePostFormPageAction($classname) {
		$data = array();
		$classname = urldecode($classname);
		$entiteType = str_replace('Entity', 'Form', $classname.'Type');
		$typeTmp = new $entiteType($this);
		// REQUEST
		$request = $this->getRequest();
		// récupération hiddenData
		$req = $request->request->get($typeTmp->getName());
		// if(!isset($req["hiddenData"])) throw new Exception("entitePostFormPageAction : hiddenData absent ! (".$typeTmp->getName().")", 1);
		$data = json_decode(urldecode($req["hiddenData"]), true);
		// echo('<pre>');
		// var_dump($req);
		// EntityManager
		$this->em = $this->getDoctrine()->getManager();
		$this->repo = $this->em->getRepository($classname);

		if($data['action'] == "create") {
			// create
			$imsg = '';
			$data['entite'] = $this->getNewEntity($classname);
		} else {
			// edit / delete
			$imsg = ' (id:'.$data['id'].')';
			$data['entite'] = $this->repo->find($data['id']);
		}
		if(!is_object($data['entite'])) {
			// entité invalide
			$message = $this->get('flash_messages')->send(array(
				'title'		=> 'Entité introuvable',
				'type'		=> flashMessage::MESSAGES_ERROR,
				'text'		=> 'L\'entité "'.$data['entite_name'].'"'.$imsg.' n\'a pas été trouvée.',
			));
		} else {
			switch ($data['action']) {
				case 'delete':
					if(method_exists($data['entite'], 'setStatut')) {
						// si un champ statut existe
						$inactif = $this->em->getRepository('site\adminBundle\Entity\statut')->findInactif();
						$data['entite']->setStatut($inactif);
					} else {
						// sinon on la supprime
						$this->em->remove($data['entite']);
					}
					$this->em->flush();
					if(isset($data['onSuccess'])) return $this->redirect($data['onSuccess']);
					break;

				default:
					$form = $this->getEntityForm($data);
					$form->bind($request);
					if($form->isValid()) {
						// formulaire valide -> enregistrement -> renvoi à l'url de success
						if($this->checkEntityBeforePersist($data) == true) {
							if($data['entite'] != null) $this->em->persist($data['entite']);
							foreach ($data['entites'] as $entite) {
								if(is_object($entite)) $this->em->persist($entite);
							}
							$this->em->flush();
							if($data['action'] == "create") {
								$data['id'] = $data['entite']->getId();
								unset($data['onSuccess']);
								$message = $this->get('flash_messages')->send(array(
									'title'		=> 'Saisie enregistrée',
									'type'		=> flashMessage::MESSAGES_SUCCESS,
									'text'		=> 'Le nouvel élément a bien été enregistré.',
								));
							} else {
								$message = $this->get('flash_messages')->send(array(
									'title'		=> 'Saisie enregistrée',
									'type'		=> flashMessage::MESSAGES_SUCCESS,
									'text'		=> 'Les modification de cet élément ont bien été enregistrées.',
								));
							}
						}
						$this->addContextActionsToData($data);
						if(isset($data['onSuccess'])) return $this->redirect($data['onSuccess']);
					} else {
						// formulaire invalide -> url echec
						$message = $this->get('flash_messages')->send(array(
							'title'		=> 'Erreurs de saisie',
							'type'		=> flashMessage::MESSAGES_ERROR,
							'text'		=> 'La saisie de vos données contient des erreurs. Veuillez les corriger, svp.',
						));
						if(isset($data['onError'])) {
							if(is_string($data['onError'])) return $this->redirect($data['onError']);
						}
						// retour au formulaire…
						$template = 'siteinterfaceBundle:entites:'.$data['entite_name'].ucfirst($data['action']).'.html.twig';
						if(!$this->get('templating')->exists($template)) {
							$template = 'siteinterfaceBundle:entites:'.'entite'.ucfirst($data['action']).'.html.twig';
						}
						$data[$data['action'].'_form'] = $form->createView();
						return $this->render($template, $data);
					}
					break;
			}

		}
		return $this->redirect($this->generateUrl('siteadmin_entite', $data['entite_name']));
	}

	protected function checkEntityBeforePersist(&$data) {
		$confirmPersist = true;
		switch ($data['entite_name']) {
			case 'tag':
				// gestion tags multiples
				if($data['action'] == "create") {
					$aeTags = $this->get('aetools.tag');
					$newTags = $aeTags->createNewMultiTags($data['entite']->getNom());
					$message = $this->get('flash_messages')->send($newTags['message']);
					$data['entites'] = $newTags['tags'];
					$data['entite'] = null;
					if(!is_array($data['entites'])) $data['entites'] = array();
					$data['action'] = null;
					$data['id'] = null;
					unset($data['onSuccess']);
					$confirmPersist = false;
				}
				break;
			default:
				// entités contenant des champs media
				if(method_exists($data['entite'], 'getBackground')) {
					$bg = $data['entite']->getBackground();
					$this->em->remove($bg);
					// echo('<h3>Media persisté</h3>');
				}
				break;
		}
		return $confirmPersist;
	}

	/**
	 * Renvoie la vue du formulaire de l'entité $entite
	 * @param object $entite
	 * @param string $action
	 * @param array $data
	 * @return Symfony\Component\Form\FormView
	 */
	public function getEntityFormView(&$data) {
		return $this->getEntityForm($data, true);
	}

	/**
	 * Renvoie le formulaire de l'entité $entite
	 * @param object $entite
	 * @param string $action
	 * @param array $data
	 * @return Symfony\Component\Form\Form
	 */
	public function getEntityForm(&$data, $getViewNow = false) {
		if(!is_array($data)) throw new Exception("getEntityForm : data doit être défini !", 1);
		$types_valid = array('create', 'edit', 'copy', 'delete');
		if(!in_array($data['action'], $types_valid)) {
			// throw new Exception("Action ".$action." invalide, doit être ".json_encode($types_valid, true), 1);
			throw new Exception("getEntityForm => type d'action invalide : ".$data['action'], 1);
		}
		// récupère les directions en fonction des résultats
		$this->addContextActionsToData($data);
		$viewForm = false;
		$form = false;
		// define Type
		$entiteType = str_replace('Entity', 'Form', $data['classname'].'Type');
		switch ($data['action']) {
			case 'create':
				$form = $this->createForm(new $entiteType($this, $data), $data['entite']);
				break;
			case 'edit':
				$form = $this->createForm(new $entiteType($this, $data), $data['entite']);
				break;
			case 'delete':
				# code...
				break;
			case 'copy':
				if(method_exists($data['entite'], 'getClone')) {
					$data['entite'] = $data['entite']->getClone();
				} else {
					// copie impossible
					$message = $this->get('flash_messages')->send(array(
						'title'		=> 'Copie impossible',
						'type'		=> flashMessage::MESSAGES_ERROR,
						'text'		=> 'Cet élément <strong>"'.$data['entite']->getNom().'"</strong> ne peut être copié.',
					));
				}
				$form = $this->createForm(new $entiteType($this, $data), $data['entite']);
				break;
			
			default:
				$message = $this->get('flash_messages')->send(array(
					'title'		=> 'Erreur formulaire',
					'type'		=> flashMessage::MESSAGES_ERROR,
					'text'		=> 'Ce type de formulaire <strong>"'.$type.'"</strong> n\'est pas reconnu.',
				));
				break;
		}
		if($form != false) $viewForm = $form->createView();
		if($getViewNow) return $viewForm;
		return $form;
	}

	/**
	 * Renvoie les url selon résultats (pour formulaires)
	 * @param array $data = null
	 * @return array
	 */
	protected function addContextActionsToData(&$data) {
		if(!is_array($data)) throw new Exception("addContextActionsToData : data doit être défini !", 1);
		switch ($data['action']) {
			case 'create':
			case 'edit':
				if(!isset($data['form_action'])) {
					$data['form_action'] = $this->generateUrl('siteadmin_form_action', array(
						'classname'	=> $data['classname'],
						), true);
				}
				if(!isset($data['onSuccess'])) {
					if($data['type']['type_related'] != null) {
						$data['onSuccess'] = $this->generateUrl('siteadmin_entite_type', array(
							'entite'		=> $data['entite_name'],
							'type_related'	=> $data['type']['type_related'],
							'type_field'	=> $data['type']['type_field'],
							'type_values'	=> $this->typeValuesToString($data['type']['type_values']),
							'action'		=> 'show',
							'id'			=> $data['entite']->getId(),
							), true);
					} else {
						$data['onSuccess'] = $this->generateUrl('siteadmin_entite', array(
							'entite'	=> $data['entite_name'],
							'id'		=> $data['entite']->getId(),
							'action'	=> 'show',
							), true);
					}
				}
				if(!isset($data['onError'])) {
					$data['onError'] = null;
				}
				break;
			case 'copy':
				if(!isset($data['form_action'])) {
					$data['form_action'] = $this->generateUrl('siteadmin_form_action', array(
						'classname'	=> $data['classname'],
						), true);
				}
				if(!isset($data['onSuccess'])) {
					$data['onSuccess'] = $this->generateUrl('siteadmin_entite', array(
						'entite'	=> $data['entite_name'],
						'id'		=> null,
						'action'	=> 'show',
						), true);
				}
				if(!isset($data['onError'])) {
					$data['onError'] = null;
				}
				break;
			case 'delete':
				if(!isset($data['form_action'])) {
					$data['form_action'] = $this->generateUrl('siteadmin_form_action', array(
						'classname'	=> $data['classname'],
						), true);
				}
				if(!isset($data['onSuccess'])) {
					$data['onSuccess'] = $this->generateUrl('siteadmin_entite', array(
						'entite'	=> $data['entite_name'],
						), true);
				}
				if(!isset($data['onError'])) {
					$data['onError'] = $this->generateUrl('siteadmin_entite', array(
						'entite'	=> $data['entite_name'],
						'id'		=> $data['entite']->getId(),
						'action'	=> 'show',
						), true);
				}
				break;
			
			default:
				if($data['type']['type_related'] != null) {
					$data['onSuccess'] = $this->generateUrl('siteadmin_entite_type', array(
						'entite'		=> $data['entite_name'],
						'type_related'	=> $data['type']['type_related'],
						'type_field'	=> $data['type']['type_field'],
						'type_values'	=> $this->typeValuesToString($data['type']['type_values']),
						'action'		=> null,
						'id'			=> null,
						), true);
				} else {
					$data['onSuccess'] = $this->generateUrl('siteadmin_entite', array(
						'entite'	=> $data['entite_name'],
						'id'		=> null,
						'action'	=> null,
						), true);
				}
				break;
		}
		// return $data;
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

	public function pageweb_as_defaultAction($id, $redir) {
		$this->em = $this->getDoctrine()->getManager();
		$this->repo = $this->em->getRepository('site\adminBundle\Entity\pageweb');
		// entité à mettre en page web par défaut
		$page = $this->repo->find($id);
		if(!is_object($page)) {
			$message = $this->get('flash_messages')->send(array(
				'title'		=> 'Page web introuvable',
				'type'		=> flashMessage::MESSAGES_ERROR,
				'text'		=> 'Cette page <strong>#"'.$id.'"</strong> n\'a pu être touvée',
			));
		} else {
			$page->setHomepage(true);
			$this->em->persist($page);
			// on passe les autres pages en false s'il en existe
			$pages = $this->repo->findByHomepage(true);
			if(count($pages) > 0) foreach ($pages as $onepage) {
				$onepage->setHomepage(false);
			}
			$this->em->flush();
		}
		return $this->redirect(urldecode($redir));
	}

	public function resetFileFormatsAction($erase = false) {
		$fileFormats = $this->get('aetools.media')->initiateFormats($erase);
		// return new Response($fileFormats ? "ok" : "Erreur");
		return $this->redirect($this->generateUrl('siteadmin_entite', array('entite' => 'fileFormat')));
	}

}
