<?php

namespace FlexAuthProvider;

use FlexAuth\Security\FlexAuthPasswordEncoder;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Pimple\ServiceProviderInterface;
use Silex\Api\EventListenerProviderInterface;
use Silex\Api\BootableProviderInterface;
use Silex\Api\ControllerProviderInterface;

use FlexAuth\Type\Memory\MemoryUserProviderFactory;
use FlexAuth\Type\Entity\EntityUserProviderFactory;
use FlexAuth\Type\UserbaseClient\UserbaseClientUserProviderFactory;
use FlexAuth\Type\JWT\JWTUserProviderFactory;
use FlexAuth\FlexAuthTypeProviderFactory;
use FlexAuth\Type\JWT\JWTTokenAuthenticator;
use FlexAuth\Type\JWT\FlexTypeJWTEncoder;
use FlexAuth\Type\JWT\DefaultJWTUserFactory;

/**
 * Class FlexAuthProvider
 * @author Aleksandr Arofikin <sashaaro@gmail.com>
 */
class FlexAuthProvider implements ServiceProviderInterface, EventListenerProviderInterface, ControllerProviderInterface, BootableProviderInterface
{
    public const SERVICE_PREFIX_TYPE = 'flex_auth.type';

    public const SERVICE_PROVIDER = 'flex_auth.type_provider';
    public const SERVICE_USER_PROVIDER_FACTORY = 'flex_auth.user_provider_factory';
    public const SERVICE_SECURITY_USER_PROVIDER = 'flex_auth.security.user_provider';
    public const SERVICE_SECURITY_PASSWORD_ENCODER = 'flex_auth.security.password_encoder';
    public const SERVICE_SECURITY_JWT_AUTHENTICATOR = 'flex_auth.type.jwt.security.authenticator';
    public const SERVICE_JWT_ENCODER = 'flex_auth.type.jwt.jwt_encoder';
    public const SERVICE_JWT_USER_FACTORY = 'flex_auth.type.jwt.user_factory';

    public const DEFAULT_ENV_VAR = 'FLEX_AUTH';
    public const PARAM_JWT_LOGIN_PAGE_URL = 'flex_auth.type.jwt.login_page_url';

    public function boot(\Silex\Application $app)
    {
    }

    public function register(\Pimple\Container $pimple)
    {
        /* Flex auth type registration */

        $pimple[self::SERVICE_PREFIX_TYPE . '.' . MemoryUserProviderFactory::TYPE] = function () {
            return new MemoryUserProviderFactory();
        };

        if (isset($pimple['entity_manager'])) {
            $pimple[self::SERVICE_PREFIX_TYPE . '.' . EntityUserProviderFactory::TYPE] = function ($app) {
                return new EntityUserProviderFactory($app['entity_manager']);
            };
        }

        if (class_exists(\UserBase\Client\UserProvider::class)) {
            $pimple[self::SERVICE_PREFIX_TYPE . '.' . \FlexAuth\Type\UserbaseClient\UserbaseClientUserProviderFactory::TYPE] = function () {
                return new UserbaseClientUserProviderFactory();
            };
        }

        $pimple[self::SERVICE_PREFIX_TYPE . '.' . JWTUserProviderFactory::TYPE] = function () {
            return new JWTUserProviderFactory();
        };

        /* Common services */

        $pimple[self::SERVICE_PROVIDER] = function () {
            return FlexAuthTypeProviderFactory::fromEnv(self::DEFAULT_ENV_VAR);
        };

        $pimple[self::SERVICE_USER_PROVIDER_FACTORY] = function ($app) {
            $flexAuthUserProviderFactory = new \FlexAuth\UserProviderFactory($app[self::SERVICE_PROVIDER]);

            $flexAuthUserProviderFactory->addType(MemoryUserProviderFactory::TYPE, $app[self::SERVICE_PREFIX_TYPE . '.' . MemoryUserProviderFactory::TYPE]);
            if (isset($app[self::SERVICE_PREFIX_TYPE . '.' . EntityUserProviderFactory::TYPE])) {
                $flexAuthUserProviderFactory->addType(EntityUserProviderFactory::TYPE, $app[self::SERVICE_PREFIX_TYPE . '.' . EntityUserProviderFactory::TYPE]);
            }
            if (isset($app[self::SERVICE_PREFIX_TYPE . '.' . \FlexAuth\Type\UserbaseClient\UserbaseClientUserProviderFactory::TYPE])) {
                $flexAuthUserProviderFactory->addType(UserbaseClientUserProviderFactory::TYPE, $app[self::SERVICE_PREFIX_TYPE . '.' . UserbaseClientUserProviderFactory::TYPE]);
            }
            $flexAuthUserProviderFactory->addType(JWTUserProviderFactory::TYPE, $app[self::SERVICE_PREFIX_TYPE . '.' . JWTUserProviderFactory::TYPE]);

            return $flexAuthUserProviderFactory;
        };

        $pimple[self::SERVICE_SECURITY_USER_PROVIDER] = function ($app) {
            return new \FlexAuth\Security\FlexUserProvider($app[self::SERVICE_USER_PROVIDER_FACTORY]);
        };

        $pimple[self::SERVICE_SECURITY_PASSWORD_ENCODER] = function ($app) {
            return new FlexAuthPasswordEncoder($app[self::SERVICE_PROVIDER]);
        };

        $pimple[self::SERVICE_JWT_USER_FACTORY] = function ($app) {
            return new DefaultJWTUserFactory();
        };

        $pimple[self::SERVICE_JWT_ENCODER] = function ($app) {
            return new FlexTypeJWTEncoder($app[self::SERVICE_PROVIDER]);
        };

        $pimple[self::PARAM_JWT_LOGIN_PAGE_URL] = '/login';
        $pimple[self::SERVICE_SECURITY_JWT_AUTHENTICATOR] = function ($app) {
            return new JWTTokenAuthenticator(
                $app[self::SERVICE_JWT_USER_FACTORY],
                $app[self::SERVICE_JWT_ENCODER],
                $app[self::SERVICE_PROVIDER],
                $app[self::PARAM_JWT_LOGIN_PAGE_URL]
            );
        };
    }

    public function subscribe(\Pimple\Container $app, EventDispatcherInterface $dispatcher)
    {

    }


    public function connect(\Silex\Application $app)
    {
    }
}