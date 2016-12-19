<?php namespace squiz\surveys\forms;

use Symfony\Component\Validator\Validation;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;

/**
 * Class AbstractForm
 * @package squiz\surveys\forms
 */
abstract class AbstractForm
{
    /**
     * @var \Symfony\Component\Form\FormFactoryInterface
     */
    private $factory;

    /**
     * AbstractForm constructor.
     */
    public function __construct()
    {
        // Set up the Validator component
        $validator = Validation::createValidator();
        $this->factory = Forms::createFormFactoryBuilder()
            ->addExtension(new ValidatorExtension($validator))
            ->addExtension(new HttpFoundationExtension())
            ->getFormFactory();
    }

    /**
     * @return \Symfony\Component\Form\FormFactoryInterface
     */
    protected function getFactory()
    {
        return $this->factory;
    }
}