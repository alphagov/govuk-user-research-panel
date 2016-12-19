<?php namespace squiz\surveys\helpers;

use squiz\surveys\lib\SessionFactory;

class SecurityHelper
{
    /**
     * Generate csfr token
     * @return string
     */
    public static function generateCSRFToken()
    {
        $session = SessionFactory::getInstance();
        if (!$session->get('token')) {
            if (function_exists('mcrypt_create_iv')) {
                $session->set('token', bin2hex(mcrypt_create_iv(32, MCRYPT_DEV_URANDOM)));
            } else {
                $session->set('token', bin2hex(openssl_random_pseudo_bytes(32)));
            }
        }
        return $session->get('token');
    }

    /**
     * Get csfr token
     * @return mixed
     */
    public static function getCSFRToken()
    {
        return SessionFactory::getInstance()->get('token');
    }
}