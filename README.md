# flex-auth-provider
FlexAuth: Silex provider

FlexAuthProvider provider integration [FlexAuth](https://github.com/linkorb/flex-auth) library to [Silex](https://silex.symfony.com) framework.

Using
```php
use Silex\Application;

$app = new Application();

//....

$app->register(new \Silex\Provider\SessionServiceProvider());
$app->register(new \FlexAuthProvider\FlexAuthProvider());

// define login page for redirect if jwt authentication is failed via browser 
$app['flex_auth.jwt.redirect_login_page'] = "/login";

$app['security.user_provider.main'] = function ($app) {
    return $app['flex_auth.security.user_provider'];
};

$app->register(new Silex\Provider\SecurityServiceProvider(), [
    'security.firewalls' => [
        'main' => [
            # https://silex.symfony.com/doc/2.0/cookbook/guard_authentication.html
            'guard' => [
                'authenticators' => [
                    'flex_auth.type.jwt.security.authenticator'
                ],
            ],
            'form' => [
                'login_path' => '/login',
                'default_target_path' => '/',
                'check_path' => '/login_check'
            ],
            'logout' => [
                'logout_path' => '/logout',
                'target_url' => 'homepage',
                'invalidate_session' => true
            ],
            'anonymous' => true,
        ],
    ],
]);
$app['security.default_encoder'] = function ($app) {
    return $pimple['flex_auth.security.password_encoder'];
    // return new \Symfony\Component\Security\Core\Encoder\PlaintextPasswordEncoder();
};

```

### Links

[Silex demo](https://github.com/linkorb/flex-auth-provider-demo)

[The Security Component(Symfony Docs)](https://symfony.com/doc/current/components/security.html)