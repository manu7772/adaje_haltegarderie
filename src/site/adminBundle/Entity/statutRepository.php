<?php

namespace site\adminBundle\Entity;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * statutRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class statutRepository extends EntityRepository {

	/** Renvoie la(les) valeur(s) par défaut --> ATTENTION : dans un array()
	* @param $defaults = liste des éléments par défaut
	*/
	public function defaultVal($defaults = null) {
		if($defaults === null) $defaults = array("Actif");
		$qb = $this->createQueryBuilder('element');
		$qb->where($qb->expr()->in('element.nom', $defaults));
		return $qb->getQuery()->getOneOrNullResult();
	}

	public function findActif() {
		$qb = $this->createQueryBuilder('element');
		$qb->where('element.nom = :nom')
			->setParameter('nom', 'Actif');
		return $qb->getQuery()->getOneOrNullResult();
	}

	public function findInactif() {
		$qb = $this->createQueryBuilder('element');
		$qb->where('element.nom = :nom')
			->setParameter('nom', 'Inactif');
		return $qb->getQuery()->getOneOrNullResult();
	}

	public function defaultValClosure($role = "ROLE_USER") {
		$list = null;
		switch($role) {
			case "ROLE_EDITOR":
				$list = array("Actif", "Inactif");
				break;
			case "ROLE_USER":
				$list = array("Actif", "Inactif");
				break;
			case "ROLE_ADMIN":
				$list = array("Actif", "Inactif");
				break;
			case "ROLE_SUPER_ADMIN":
				// all
				break;
			default:
				$list = array("Actif", "Inactif");
				break;
		}
		$qb = $this->createQueryBuilder('element');
		if(is_array($list)) $qb->where($qb->expr()->in('element.nom', $list));
		return $qb;
	}

}
