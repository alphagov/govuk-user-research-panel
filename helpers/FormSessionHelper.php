<?php namespace squiz\surveys\helpers;

use squiz\surveys\lib\SessionFactory;
use Symfony\Component\HttpFoundation\Request;

class FormSessionHelper
{
    public $formContentPrefix = 'form_content';

    /**
     * FormSessionHelper constructor.
     * @param string $formKey
     */
    public function __construct($formKey)
    {
        $this->key = $formKey;
        $this->session = SessionFactory::getInstance();
    }

    /**
     * Remember submitted form values in session
     * @param Request $request
     */
    public function saveFormValuesFromRequest(Request $request)
    {
        foreach ($request->request->all() as $key => $value) {
            $this->rememberFormValue($key, $value);
        }
    }

    public function rememberFormValue($key, $value)
    {
        $this->set($this->formContentPrefix.'/'.$key, $value);
    }

    /**
     * Remove surveys related data from session
     * @param string $field_name
     */
    public function forgetFormContent($field_name = null)
    {
        if (!$field_name) {
            $this->session->remove($this->key);
        } else {
            $this->session->remove($this->key.'/'.$this->formContentPrefix.'/'.$field_name);
        }
    }

    /**
     * Return all remembered form fields values or a value of the specified field
     * @param null|string $field_name
     * @return array|string
     */
    public function getFormContent($field_name = null)
    {
        if (!$field_name) {
            return $this->get($this->formContentPrefix, array()) ?: array();
        } else {
            return $this->get($this->formContentPrefix.'/'.$field_name);
        }
    }

    /**
     * Set prefixed session variable
     * @param $name
     * @param $value
     */
    public function set($name, $value)
    {
        $this->session->set($this->key.'/'.$name, $value);
    }

    /**
     * Get prefixed session variable
     * @param $name
     * @param mixed $default
     * @return mixed
     */
    public function get($name, $default = null)
    {
        return $this->session->get($this->key.'/'. $name, $default);
    }

    /**
     * Remove prefixed session variable
     * @param $name
     * @return mixed
     */
    public function remove($name)
    {
        return $this->session->remove($this->key.'/'. $name);
    }
}