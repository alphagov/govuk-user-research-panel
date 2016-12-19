<?php namespace squiz\surveys\forms;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\NotBlank;
use squiz\surveys\constraints\Email;
use Symfony\Component\Validator\Constraints\Regex;
use squiz\utils\FormUtils;

/**
 * Class Registration
 * @package squiz\surveys\forms
 */
class Registration extends AbstractForm
{
    /**
     * Get user registration form object
     * @return \Symfony\Component\Form\FormInterface
     */
    public function getForm()
    {
        return $this->getFactory()->createBuilder('Symfony\Component\Form\Extension\Core\Type\FormType', null, array(
            'action' => FormUtils::getOnboardingFormUrl().'/register',
            'method' => 'POST',
        ))
            ->add('full_name', get_class(new TextType()), array(
                'label' => 'Your full name',
                'constraints' => array(
                    new NotBlank(array(
                        'message' => 'Enter your name'
                    )),
                    new Regex(array(
                        'message' => 'Name can only contain letters a to z, spaces, hyphens and apostrophes',
                        'pattern' => '/^[a-zA-Z-’\' ]+$/',
                    )),
                ),
            ))
            ->add('email1', get_class(new TextType()), array(
                'label' => 'Email address',
                'constraints' => array(
                    new NotBlank(array(
                        'message' => 'Enter your email'
                    )),
                    new Email(array(
                        'message' => 'Enter a valid email',
                    ))
                ),
            ))
            ->getForm();
    }
}