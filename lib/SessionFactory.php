<?php namespace squiz\surveys\lib;

use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Attribute\NamespacedAttributeBag;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class SessionFactory
 * @package squiz\utils
 */
class SessionFactory
{
    private static $instance;

    /**
     * Returns session object instance
     * @return Session
     */
    public static function getInstance()
    {
        if (!self::$instance) {
            $request = Request::createFromGlobals();
            $settings = array(
                'cookie_httponly' => true,
            );
            if ($request->isSecure()) {
                $settings['cookie_secure'] = true;
            }
            $storage = new NativeSessionStorage($settings);
            self::$instance = new Session($storage, new NamespacedAttributeBag());
            self::$instance->setName('survey');
        }
        return self::$instance;
    }
}