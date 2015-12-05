<?php
namespace site\services;
use Doctrine\ORM\EntityManager;

use site\adminBundle\Entity\tag;
use site\interfaceBundle\services\flashMessage;

class aeTags {

    // const DELIMITERS = "/[\n\r\s,]+/";
    const DELIMITERS = "/[\n\r,]+/";
    protected $em; // EntityManager
    protected $existingTags;

    public function __construct(EntityManager $em = null) {
        $this->em = $em;
        $this->existingTags = array();
    }

    /**
     * Crée un nouveau tag $nom
     * @param string $nom
     * @param boolean $persist
     * @return tag / false si erreur
     */
    public function createNewTag($nom, $persist = true) {
        $persist = (bool)$persist;
        $nom = trim(strip_tags($nom));
        if(strlen($nom) > 0) {
            // existe ?
            $tagexist = $this->em->getRepository('site\adminBundle\Entity\tag')->findByNom($nom);
            if(count($tagexist) == 0 && !in_array($nom, $this->existingTags)) {
                // ok on crée…
                $newtag = new tag();
                $newtag->setNom($nom);
                if($persist) {
                    $this->em->persist($newtag);
                    $this->em->flush();
                }
                $this->existingTags[] = $nom;
                return $newtag;
            }
        }
        return false;
    }

    /**
     * Crée les tags selon la liste fournie dans $stringList (séparés par ',')
     * @param string $stringList
     * @param boolean $persist
     * @return array
     */
    public function createNewMultiTags($stringList, $persist = true) {
        $items = preg_split(self::DELIMITERS, $stringList);
        $newtags = array();
        if(count($items) > 0) foreach ($items as $nom) {
            $r = $this->createNewTag($nom, $persist);
            if(is_object($r)) $newtags[] = $r;
        }
        $rep = array();
        $rep['count'] = count($newtags);
        $rep['tags'] = $newtags;
        if($rep['count'] > 0) {
            // message pour tags créés
            $rep['message'] = array(
                'title'     => 'Tags créés',
                'type'      => flashMessage::MESSAGES_SUCCESS,
                'text'      => $rep['count'].' tags ont été créés.',
            );
        } else {
            // message pour aucun tag créé
            $rep['message'] = array(
                'title'     => 'Tags créés',
                'type'      => flashMessage::MESSAGES_WARNING,
                'text'      => 'Aucun tag n\'a été créé (invalides, ou déjà existants).',
            );
        }
        // retour
        return $rep;
    }


}