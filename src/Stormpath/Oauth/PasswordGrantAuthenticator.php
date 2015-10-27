<?php


namespace Stormpath\Oauth;

use Stormpath\Resource\Application;

class PasswordGrantAuthenticator
{
    private $application;

    const OAUTH_TOKEN_PATH  = '/oauth/token';

    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    public function authenticate(PasswordGrantRequest $passwordGrantRequest)
    {
        $createGrantAuthenticationAttempt = new CreateGrantAuthenticationAttempt();
        $createGrantAuthenticationAttempt->setLogin($passwordGrantRequest->getLogin())
                                         ->setPassword($passwordGrantRequest->getPassword())
                                         ->setGrantType($passwordGrantRequest->getGrantType());
        if($passwordGrantRequest->getAccountStore() != null)
            $createGrantAuthenticationAttempt->setAccountStore($passwordGrantRequest->getAccountStore());

        $grantResult = $this->application->dataStore->create(
            $this->application->getHref() . self::OAUTH_TOKEN_PATH,
            $createGrantAuthenticationAttempt,
            \Stormpath\Stormpath::GRANT_AUTHENTICATION_TOKEN
        );

        $builder = new OauthGrantAuthenticationResultBuilder($grantResult);
        return $builder->build();

    }
}