<?php

namespace site\adminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Doctrine\Common\Collections\ArrayCollection;
// Slug
use Gedmo\Mapping\Annotation as Gedmo;

use site\services\aeImages;

use site\adminBundle\Entity\pageweb;
use site\adminBundle\Entity\fileFormat;

use \DateTime;
use \SplFileInfo;

/**
 * media
 *
 * @ORM\Table()
 * @ORM\Entity
 * @ORM\Entity(repositoryClass="mediaRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class media {

	/**
	 * @var integer
	 * @ORM\Column(name="id", type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	private $id;

	/**
	 * @var string
	 * @ORM\Column(name="nom", type="string", length=255)
	 * @Gedmo\Slug(fields={"originalnom"})
	 * @ORM\Column(length=128, unique=true)
	 */
	private $nom;

	/**
	 * @var string
	 * @ORM\Column(name="formatnom", type="string", length=16, nullable=true, unique=false)
	 */
	private $format_nom;

	/**
	 * @var string
	 * @ORM\Column(name="formaticon", type="string", length=64, nullable=true, unique=false)
	 */
	private $format_icon;

	/**
	 * @var string
	 * @ORM\Column(name="formatcontentType", type="string", length=255, nullable=true, unique=false)
	 */
	private $format_contentType;

	/**
	 * @var string
	 * @ORM\Column(name="originalnom", type="string", length=255, nullable=true, unique=false)
	 */
	private $originalnom;
		
	/**
	 * @var string
	 * @ORM\Column(name="extension", type="string", length=8, nullable=true, unique=false)
	 */
	private $extension;
		
	/**
	 * @var string
	 * @ORM\Column(name="binaryFile", type="blob", nullable=false, unique=false)
	 */
	private $binaryFile;
	
	/**
	 * @ORM\ManyToOne(targetEntity="fileFormat", inversedBy="medias")
	 * @ORM\JoinColumn(nullable=true, unique=false, name="fileFormat_id", referencedColumnName="id",  onDelete="SET NULL")
	 */
	private $format;

	/**
	 * @ORM\OneToOne(targetEntity="pageweb", inversedBy="background")
	 * @ORM\JoinColumn(nullable=true, unique=true, name="pageweb_id", referencedColumnName="id", onDelete="SET NULL")
	 */
	private $pagewebBackground;

	public $upload_file;
	
	/**
	 * @var int
	 * @ORM\Column(name="file_size", type="integer", length=10, nullable=true, unique=false)
	 */
	private $fileSize;

	/**
	 * @var DateTime
	 * @ORM\Column(name="created", type="datetime", nullable=false, unique=false)
	 */
	protected $dateCreation;

	/**
	 * @var DateTime
	 * @ORM\Column(name="updated", type="datetime", nullable=true, unique=false)
	 */
	protected $dateMaj;

	private $rawCodeFile;
	
	public function __construct() {
		
		$this->dateCreation = new DateTime();
		$this->dateMaj = null;
		$this->rawCodeFile = null;
		$date = new DateTime();
		$this->extension = null;
		$this->format_nom = null;
		$this->format_icon = null;
		$this->format_contentType = null;
	}

	public function __toString() {
		return $this->getNom;
	}

	/**
	 * @Assert\True(message="fileFormat.notsupported")
	 */
	public function isAuthorizedFileFormat() {
		if($this->getFormat() != null) return $this->format->getEnabled();
		return false;
	}

	/**
	 * @ORM\PrePersist()
	 * @ORM\PreUpdate()
	 */
	public function check() {
		// IMPORTANT
		$this->upload();
		// copie info fileFormat
		if($this->getFormat() != null) {
			$this->setFormat_nom($this->format->getNom());
			$this->setFormat_icon($this->format->getIcon());
			$this->setFormat_contentType($this->format->getContentType());
		}
	}

	public function upload(){
		if (null === $this->upload_file) return;
		
		$strm = fopen($this->upload_file->getRealPath(),'rb');
		$this->setBinaryFile(stream_get_contents($strm));
		$this->setFileSize(filesize($this->upload_file->getRealPath()));
		$this->setOriginalnom($this->upload_file->getClientOriginalName());
		$this->setExtension($this->getUploadFile_extension());
	}
	
	/**
	 * @ORM\PostLoad()
	 * @return string - code brut du fichier / null si aucun code
	 */
	public function getRawCodeFile(){
		if(null === $this->binaryFile) return null;
		if($this->rawCodeFile == null)
			$this->rawCodeFile = stream_get_contents($this->binaryFile);
		return $this->rawCodeFile;
	}

	/**
	 * Retourne le code en Base64 du fichier / null si aucun
	 * @return string
	 */
	public function getB64File(){
		$rawCodeFile = $this->getRawCodeFile();
		return $rawCodeFile !== null ? base64_encode($rawCodeFile) : null;
	}

	/**
	 * Retourne un thumbnail du fichier / null si aucun
	 * @param integer $x - taille X
	 * @param integer $y - taille Y
	 * @param string $mode = 'cut'
	 * @return string
	 */
	public function getThumbnail($x = 256, $y = 256, $mode = 'cut') {
		$thumbnail = null;
		if($this->isImage()) {
			$aeImages = new aeImages();
			// $codeImg = base64_decode($this->getB64File());
			$codeImg = $this->getRawCodeFile();
			$imageOrigin = @imagecreatefromstring($codeImg);
			if($imageOrigin != false) {
				// echo('Thumb file : '.$this->getId().'<br>');
				$image = $aeImages->thumb_image($imageOrigin, $x, $y, $mode);
				ob_start();
				imagepng($image);
				$thumbnail = ob_get_contents();
				ob_end_clean();
				imagedestroy($image);
			}
		}
		return $thumbnail;
	}

	/**
	 * Retourne un thumbnail en base64 du fichier / null si aucun
	 * @param integer $x - taille X
	 * @param integer $y - taille Y
	 * @param string $mode = 'cut'
	 * @return string
	 */
	public function getThumbnailB64($x = 256, $y = 256, $mode = 'cut') {
		$b64 = $this->getThumbnail($x, $y, $mode);
		return $b64 === null ? null : base64_encode($b64);
	}

	/**
	 * Get id
	 * @return integer 
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * Get upload file name
	 * @return string 
	 */
	public function getUploadFile_typemime() {
		if (null === $this->upload_file) return false;
		// http://api.symfony.com/2.0/Symfony/Component/HttpFoundation/File/UploadedFile.html
		return $this->upload_file->getMimeType();
	}

	/**
	 * Get upload file extension
	 * @return string 
	 */
	public function getUploadFile_extension() {
		if (null === $this->upload_file) return false;
		// http://api.symfony.com/2.0/Symfony/Component/HttpFoundation/File/UploadedFile.html
		return $this->upload_file->guessExtension();
	}

	/**
	 * Get upload file extension
	 * @return string 
	 */
	public function getUploadFile_isRealyAFile() {
		if (null === $this->upload_file) return false;
		$fileInfo = new SplFileInfo($this->upload_file->getRealPath());
		return ($fileInfo->isFile() && !($fileInfo->isDir()) && !($fileInfo->isExecutable()) && !($fileInfo->isLink()));
	}

	/**
	 * Set binaryFile
	 * @param string $binaryFile
	 * @return media
	 */
	public function setBinaryFile($binaryFile) {
		$this->binaryFile = $binaryFile;

		return $this;
	}

	/**
	 * Get binaryFile
	 * @return string 
	 */
	public function getBinaryFile() {
		return $this->getRawCodeFile();
	}

	/**
	 * Set pagewebBackground
	 * @param pageweb $pagewebBackground
	 * @return media
	 */
	public function setPagewebBackground(pageweb $pagewebBackground = null) {
		$this->pagewebBackground = $pagewebBackground;
		if($pagewebBackground != null) $pagewebBackground->setBackground_reverse($this);
			else $pagewebBackground->setBackground_reverse(null);
		return $this;
	}

	/**
	 * Set pagewebBackground
	 * @param pageweb $pagewebBackground
	 * @return media
	 */
	public function setPagewebBackground_reverse(pageweb $pagewebBackground = null) {
		$this->pagewebBackground = $pagewebBackground;

		return $this;
	}

	/**
	 * Get pagewebBackground
	 * @return pageweb 
	 */
	public function getPagewebBackground() {
		return $this->pagewebBackground;
	}

	/**
	 * Set nom
	 * @param string $nom
	 * @return media
	 */
	public function setNom($nom) {
		$this->nom = $nom;

		return $this;
	}

	/**
	 * Get nom
	 * @return string
	 */
	public function getNom() {
		return $this->nom;
	}

	/**
	 * Set extension
	 * @param string $extension
	 * @return media
	 */
	public function setExtension($extension) {
		$this->extension = $extension;

		return $this;
	}

	/**
	 * Get extension
	 * @return string
	 */
	public function getExtension() {
		return $this->extension;
	}

	/**
	 * Set format_nom
	 * @param string $format_nom
	 * @return media
	 */
	public function setFormat_nom($format_nom) {
		$this->format_nom = $format_nom;

		return $this;
	}

	/**
	 * Get format_nom
	 * @return string
	 */
	public function getFormat_nom() {
		if($this->getFormat() != null)
			return $this->getFormat()->getNom();
			else return $this->format_nom;
	}

	/**
	 * Set format_icon
	 * @param string $format_icon
	 * @return media
	 */
	public function setFormat_icon($format_icon) {
		$this->format_icon = $format_icon;

		return $this;
	}

	/**
	 * Get format_icon
	 * @return string
	 */
	public function getFormat_icon() {
		if($this->getFormat() != null)
			return $this->getFormat()->getIcon();
			else return $this->format_icon;
	}

	/**
	 * Set format_contentType
	 * @param string $format_contentType
	 * @return media
	 */
	public function setFormat_contentType($format_contentType) {
		$this->format_contentType = $format_contentType;

		return $this;
	}

	/**
	 * Get format_contentType
	 * @return string
	 */
	public function getFormat_contentType() {
		if($this->getFormat() != null)
			return $this->getFormat()->getContentType();
			else return $this->format_contentType;
	}

	/**
	 * Set originalnom
	 * @param string $originalnom
	 * @return media
	 */
	public function setOriginalnom($originalnom) {
		$this->originalnom = $originalnom;

		return $this;
	}

	/**
	 * Get originalnom
	 * @return string
	 */
	public function getOriginalnom() {
		return $this->originalnom;
	}

	/**
	 * Set format
	 * @param fileFormat $format
	 * @return media
	 */
	public function setFormat(fileFormat $format = null) {
		$this->format = $format;
		if($format != null) $format->addMedia_reverse($this);
			else $format->removeMedia_reverse($this);
		return $this;
	}

	/**
	 * Set format
	 * @param fileFormat $format
	 * @return media
	 */
	public function setFormat_reverse(fileFormat $format = null) {
		$this->format = $format;
		return $this;
	}

	/**
	 * Get format
	 * @return fileFormat
	 */
	public function getFormat() {
		return $this->format;
	}

	/**
	 * Set fileSize
	 * @param integer $fileSize
	 * @return media
	 */
	public function setFileSize($fileSize) {
		$this->fileSize = $fileSize;

		return $this;
	}

	/**
	 * Get fileSize
	 * @return integer
	 */
	public function getFileSize() {
		return $this->fileSize;
	}

	/**
	 * Get first part of content-type (type)
	 * @return string
	 */
	public function getType() {
		if($this->getFormat() != null)
			return $this->getFormat()->getType();
			else return explode('/', $this->getFormat_contentType())[0];
	}

	/**
	 * is version an IMAGE type ?
	 * @return boolean
	 */
	public function isImage() {
		if($this->getFormat() != null)
			return $this->getFormat()->isImage();
			else return strtolower($this->getType()) == "image";
	}

	/**
	 * is version a screenable IMAGE type ?
	 * @return boolean
	 */
	public function isScreenableImage() {
		return ($this->isImage() && ($this->getRawCodeFile() !== null));
	}

	/**
	 * is version a PDF type ?
	 * @return boolean
	 */
	public function isPdf() {
		if($this->getFormat() != null)
			return $this->getFormat()->isPdf();
			else {
				$isPdf = explode('/', $this->getFormat_contentType())[1];
				return strtolower($isPdf) == "pdf";
			}
	}

	/**
	 * Set dateCreation
	 * @param DateTime $dateCreation
	 * @return pageweb
	 */
	public function setDateCreation(DateTime $dateCreation) {
		$this->dateCreation = $dateCreation;
		return $this;
	}

	/**
	 * Get dateCreation
	 * @return DateTime 
	 */
	public function getDateCreation() {
		return $this->dateCreation;
	}

	/**
	 * @ORM\PreUpdate
	 */
	public function updateDateMaj() {
		$this->setDateMaj(new DateTime());
	}

	/**
	 * Set dateMaj
	 * @param DateTime $dateMaj
	 * @return pageweb
	 */
	public function setDateMaj(DateTime $dateMaj) {
		$this->dateMaj = $dateMaj;
		return $this;
	}

	/**
	 * Get dateMaj
	 * @return DateTime 
	 */
	public function getDateMaj() {
		return $this->dateMaj;
	}


}
