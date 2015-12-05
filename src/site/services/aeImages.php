<?php
namespace site\services;
use Doctrine\ORM\EntityManager;

use site\adminBundle\Entity\media;
use site\adminBundle\Entity\fileFormat;
use site\interfaceBundle\services\flashMessage;

class aeImages {

    protected $em; // EntityManager

    public function __construct(EntityManager $em = null) {
        $this->em = $em;
    }

    public function createNewImage() {
        //
    }

    public function check_media(&$data) {
        $this->repo = $this->em->getRepository('site\adminBundle\Entity\media');
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
            // OK média(s) concernée(s)…
            foreach ($data['entites'] as $key => $entite) {
                if($entite->getFormat() == null) {
                    if($entite->getFormat_nom() != null) {
                        // selon format mémorisé…
                        $format = $this->em->getRepository('site\adminBundle\Entity\fileFormat')->findByNom($entite->getFormat_nom());
                        if(count($formats) > 0) $entite->setFormat(reset($formats));
                    } else {
                        // sinon selon extension…
                    }
                }
                // test de check sur l'entité directement
                if(method_exists($entite, "check")) $entite->check();
            }
            $this->em->flush();
            return array(
                'title'     => 'Check media',
                'type'      => flashMessage::MESSAGES_WARNING,
                'text'      => 'Les média ont été checkés.',
                );
        } else {
            // aucun média trouvé
            return array(
                'title'     => 'Check non effectué',
                'type'      => flashMessage::MESSAGES_ERROR,
                'text'      => 'Auncun élément à checker.',
                );
        }
    }

    /**
     * Crée un nouveau format de fichier
     * @param string $contentType
     * @param string $nom
     * @param boolean $enable
     * @return fileFormat
     */
    public function createNewFormat($contentType, $nom = null, $enable = true) {
        $newFormat = new fileFormat();
        $newFormat->setEnabled($enable);
        $newFormat->setContentType($contentType);
        if(!is_string($nom)) $nom = $contentType;
        $newFormat->setNom($nom);
        $this->em->persist($newFormat);
        $this->em->flush();
        return $newFormat;
    }

    /**
     * Renvoie la liste des formats
     * @return array
     */
    public function getAllFormats() {
        return $this->em->getRepository('site\adminBundle\Entity\fileFormat')->findAll();
    }

    /**
     * Efface tous les formats
     */
    public function eraseAllFormats() {
        $count = 0;
        $formats = $this->getAllFormats();
        foreach ($formats as $format) {
            $this->em->remove($format);
            $count++;
        }
        $this->em->flush();
        // remise à zéro de l'index de la table
        $this->em->getConnection()->executeUpdate("ALTER TABLE fileFormat AUTO_INCREMENT = 1;");
        return $count;
    }

    public function initiateFormats($eraseAll = true) {
        // $result = false;
        $formats = array(
            array(
                'nom'           => 'png',
                'icon'          => 'fa-file-image-o',
                'contentType'   => 'image/png',
                'enabled'       => true,
                ),
            array(
                'nom'           => 'jpg',
                'icon'          => 'fa-file-image-o',
                'contentType'   => 'image/jpg',
                'enabled'       => true,
                ),
            array(
                'nom'           => 'jpeg',
                'icon'          => 'fa-file-image-o',
                'contentType'   => 'image/jpeg',
                'enabled'       => true,
                ),
            array(
                'nom'           => 'gif',
                'icon'          => 'fa-file-image-o',
                'contentType'   => 'image/gif',
                'enabled'       => true,
                ),
            array(
                'nom'           => 'pdf',
                'icon'          => 'fa-file-pdf-o',
                'contentType'   => 'application/pdf',
                'enabled'       => true,
                ),
            array(
                'nom'           => 'doc',
                'icon'          => 'fa-file-word-o',
                'contentType'   => 'application/msword',
                'enabled'       => false,
                ),
            array(
                'nom'           => 'docx',
                'icon'          => 'fa-file-word-o',
                'contentType'   => 'application/msword',
                'enabled'       => false,
                ),
            array(
                'nom'           => 'xls',
                'icon'          => 'fa-file-excel-o',
                'contentType'   => 'application/vnd.ms-excel',
                'enabled'       => false,
                ),
            array(
                'nom'           => 'txt',
                'icon'          => 'fa-file-word-o',
                'contentType'   => 'text/plain',
                'enabled'       => false,
                ),
            );
        if($eraseAll) $this->eraseAllFormats();
        foreach ($formats as $key => $format) {
            $newFormat[$key] = new fileFormat();
            foreach ($format as $field => $value) {
                $add = "add".ucfirst($field);
                $set = "set".ucfirst($field);
                if(method_exists($newFormat[$key], $add)) $newFormat[$key]->$add($value);
                    else if(method_exists($newFormat[$key], $set)) $newFormat[$key]->$set($value);
            }
            $this->em->persist($newFormat[$key]);
        }
        $result = $this->em->flush();
        return $newFormat;
    }

    /**
    * thumb_image
    * Crée et enregistre un tumbnail de l'image
    * @param image $image
    * @param integer $Xsize = null
    * @param integer $Ysize = null
    * @param string $mode = "no"
    * @return image
    */
    public function thumb_image($image, $Xsize = null, $Ysize = null, $mode = "no") {
        // $mode =
        // cut      : remplit le format avec l'image et la coupe si besoin
        // in       : inclut l'image pour qu'elle soit entièrerement visible
        // deform   : déforme l'image pour qu'elle soit exactement à la taille
        // no       : ne modifie pas la taille de l'image
        // calcul…
        $x = imagesx($image);
        $y = imagesy($image);
        $ratio = $x / $y;

        if($Xsize == null && $Ysize == null) {
            $Xsize = $x;
            $Ysize = $y;
        }
        if($Xsize == null) $Xsize = $Ysize * $ratio;
        if($Ysize == null) $Ysize = $Xsize / $ratio;

        $Dratio = $Xsize / $Ysize;

        if(($x != $Xsize) || ($y != $Ysize)) {
            switch($mode) {
                case('deform') :
                    $nx = $Xsize;
                    $ny = $Ysize;
                    $posx = $posy = 0;
                break;
                case('cut') :
                    if($ratio > $Dratio) {
                        $posx = ($x - ($y * $Dratio)) / 2;
                        $posy = 0;
                        $x = $y * $Dratio;
                    } else {
                        $posx = 0;
                        $posy = ($y - ($x / $Dratio)) / 2;
                        $y = $x / $Dratio;
                    }
                    $nx = $Xsize;
                    $ny = $Ysize;
                break;
                case('in') :
                    if($x > $Xsize || $y > $Xsize) {
                        if($x > $y) {
                            $nx = $Xsize;
                            $ny = $y/($x/$Xsize);
                        } else {
                            $nx = $x/($y/$Xsize);
                            $ny = $Xsize;
                        }
                    } else {
                        $nx = $x;
                        $ny = $y;
                    }
                    $posx = $posy = 0;
                break;
                default: // "no" et autres…
                    $posx = $posy = 0;
                    $nx = $x;
                    $ny = $y;
                break;
            }
            $Rimage = imagecreatetruecolor($nx, $ny);
            imagealphablending($Rimage, false);
            imagesavealpha($Rimage, true);
            imagecopyresampled($Rimage, $image, 0, 0, $posx, $posy, $nx, $ny, $x, $y);
        } else {
            $Rimage = imagecreatetruecolor($x, $y);
            imagealphablending($Rimage, false);
            imagesavealpha($Rimage, true);
            imagecopy($Rimage, $image, 0, 0, 0, 0, $x, $y);
        }
        return $Rimage;
    }





}