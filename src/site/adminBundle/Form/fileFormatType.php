<?php

namespace site\adminBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
// Transformer
use Symfony\Component\Form\CallbackTransformer;
// User
use Symfony\Component\Security\Core\SecurityContext;
// Paramétrage de formulaire
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;

use site\adminBundle\Entity\fileFormat;

class fileFormatType extends AbstractType {

    private $controller;
    private $securityContext;
    private $parametres;
    
    public function __construct(Controller $controller, $parametres = null) {
        $this->controller = $controller;
        $this->securityContext = $controller->get('security.context');
        if($parametres === null) $parametres = array();
        $this->parametres = $parametres;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $fileFormat = new fileFormat();
        $iconChoice = $fileFormat->getTypeIcons();
        $builder
            ->add('nom', 'text', array(
                'label'     => 'form.nom',
                'required'  => true,
                ))
            ->add('icon', 'choice', array(
                'label'     => 'form.icone',
                'required'  => true,
                'multiple'  => false,
                'expanded'  => false,
                'choices'   => $iconChoice,
                ))
            ->add('contentType', 'text', array(
                'label' => 'Content Type',
                ))
            ->add('enabled', 'checkbox', array(
                'label' => 'actif',
                'required' => false,
                ))
        ;

        // ajoute les valeurs hidden, passés en paramètre
        $builder = $this->addHiddenValues($builder);

        // AJOUT SUBMIT
        $builder->add('submit', 'submit', array(
            'label' => 'form.enregistrer',
            'attr' => array(
                'class' => "btn btn-md btn-block btn-info",
                ),
            ))
        ;
    }
    
    /**
     * addHiddenValues
     * @param FormBuilderInterface $builder
     * @return FormBuilderInterface
     */
    public function addHiddenValues(FormBuilderInterface $builder) {
        $data = array();
        $nom = 'hiddenData';
        foreach($this->parametres as $key => $value) {
            if(is_string($value) || is_array($value) || is_bool($value)) {
                $data[$key] = $value;
            }
        }
        if($builder->has($nom)) $builder->remove($nom);
        $builder->add($nom, 'hidden', array(
            'data' => urlencode(json_encode($data, true)),
            'mapped' => false,
        ));
        return $builder;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'site\adminBundle\Entity\fileFormat'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'site_adminbundle_fileformat';
    }
}
