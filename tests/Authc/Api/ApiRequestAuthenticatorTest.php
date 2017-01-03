<?php
/**
 * Copyright 2017 Stormpath, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 */

namespace Stormpath\Tests\Authc\Api;

use Stormpath\Authc\Api\ApiRequestAuthenticator;
use Stormpath\Authc\Api\Request;
use Stormpath\Tests\TestCase;

class ApiRequestAuthenticatorTest extends TestCase
{

    public static $account;

    private static $application;

    private static $apiKey;


    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        self::$application = \Stormpath\Resource\Application::instantiate(
            array(
                'name' => makeUniqueName('Application ApiRequestAuthenticatorTest'),
                'description' => 'Application for ApiRequestAuthenticatorTest',
                'status' => 'enabled'
            )
        );
        parent::createResource(
            \Stormpath\Resource\Application::PATH,
            self::$application,
            array('createDirectory' => true)
        );

        self::$account = \Stormpath\Resource\Account::instantiate(
            array(
                'givenName' => 'PHP',
                'middleName' => 'ApiRequestAuthenticatorTest',
                'surname' => 'Test',
                'username' => makeUniqueName('ApiRequestAuthenticatorTest'),
                'email' => makeUniqueName('ApiRequestAuthenticatorTest') .'@testmail.stormpath.com',
                'password' => 'superP4ss'

            )
        );
        self::$application->createAccount(self::$account);

        self::$apiKey = self::$account->createApiKey();

    }

    /**
     * @test
     */
    public function it_can_authenticate_a_basic_request()
    {
        $authorization = 'Basic ' . base64_encode(self::$apiKey->id . ':' . self::$apiKey->secret);
        $_SERVER['HTTP_AUTHORIZATION'] = $authorization;
        $_SERVER['REQUEST_URI'] = 'http://test.com/';
        $_SERVER['QUERY_STRING'] = '';


        self::$apiKey->setStatus('ENABLED');
        self::$apiKey->save();

        self::$account->setStatus('ENABLED');
        self::$account->save();

        $auth = new ApiRequestAuthenticator(self::$application);
        $result = $auth->authenticate(Request::createFromGlobals());

        $this->assertInstanceOf('Stormpath\Authc\Api\ApiAuthenticationResult', $result);

        $this->assertInstanceOf('Stormpath\Resource\Application', $result->getApplication());
        $this->assertInstanceOf('Stormpath\Resource\ApiKey', $result->getApiKey());

    }

    /**
     * @test
     */
    public function it_authorizes_with_client_credentials_request()
    {
        $authorization = 'Basic ' . base64_encode(self::$apiKey->id . ':' . self::$apiKey->secret);
        $_SERVER['HTTP_AUTHORIZATION'] = $authorization;
        $_SERVER['REQUEST_URI'] = 'http://test.com/?grant_type=client_credentials';
        $_SERVER['QUERY_STRING'] = 'grant_type=client_credentials';

        self::$apiKey->setStatus('ENABLED');
        self::$apiKey->save();

        self::$account->setStatus('ENABLED');
        self::$account->save();

        $auth = new ApiRequestAuthenticator(self::$application);
        $result = $auth->authenticate(Request::createFromGlobals());
        $token = json_decode($result->getAccessToken());

        $this->assertInstanceOf('Stormpath\Authc\Api\ApiAuthenticationResult', $result);

        $this->assertInstanceOf('Stormpath\Resource\Application', $result->getApplication());
        $this->assertInstanceOf('Stormpath\Resource\ApiKey', $result->getApiKey());
        $this->assertObjectHasAttribute('access_token', $token);
        $this->assertObjectHasAttribute('token_type', $token);
        $this->assertObjectHasAttribute('expires_in', $token);
    }

    /**
     * @test
     */
    public function it_authorizes_with_bearer_token()
    {
        $authorization = 'Basic ' . base64_encode(self::$apiKey->id . ':' . self::$apiKey->secret);
        $_SERVER['HTTP_AUTHORIZATION'] = $authorization;
        $_SERVER['REQUEST_URI'] = 'http://test.com/?grant_type=client_credentials';
        $_SERVER['QUERY_STRING'] = 'grant_type=client_credentials';

        self::$apiKey->setStatus('ENABLED');
        self::$apiKey->save();

        self::$account->setStatus('ENABLED');
        self::$account->save();

        $auth = new ApiRequestAuthenticator(self::$application);
        $result = $auth->authenticate(Request::createFromGlobals());
        $token = json_decode($result->getAccessToken());
        $accessToken = $token->access_token;

        $_SERVER['HTTP_AUTHORIZATION'] = "Bearer $accessToken";
        $auth = new ApiRequestAuthenticator(self::$application);
        $result = $auth->authenticate(Request::createFromGlobals());

        $this->assertInstanceOf('Stormpath\Authc\Api\ApiAuthenticationResult', $result);

        $this->assertInstanceOf('Stormpath\Resource\Application', $result->getApplication());
        $this->assertInstanceOf('Stormpath\Resource\ApiKey', $result->getApiKey());
    }


    protected function tearDown()
    {
        Request::tearDown();
    }

    public static function tearDownAfterClass()
    {
        if (self::$application)
        {
            $accountStoreMappings = self::$application->accountStoreMappings;

            if ($accountStoreMappings)
            {
                foreach($accountStoreMappings as $asm)
                {
                    $accountStore = $asm->accountStore;
                    $asm->delete();
                    $accountStore->delete();
                }
            }

            self::$application->delete();
        }

        parent::tearDownAfterClass();
    }

    /**
     * @return array
     */
    private function getAccessToken()
    {
        $authorization = 'Basic ' . base64_encode(self::$apiKey->id . ':' . self::$apiKey->secret);
        $_SERVER['HTTP_AUTHORIZATION'] = $authorization;
        $_SERVER['REQUEST_URI'] = 'http://test.com/?grant_type=client_credentials';
        $_SERVER['QUERY_STRING'] = 'grant_type=client_credentials';

        self::$apiKey->setStatus('ENABLED');
        self::$apiKey->save();

        self::$account->setStatus('ENABLED');
        self::$account->save();

        $auth = new OAuthClientCredentialsRequestAuthenticator(self::$application);
        $result = $auth->authenticate(Request::createFromGlobals());
        $token = json_decode($result->getAccessToken());
        $accessToken = $token->access_token;

        Request::tearDown();
        return $accessToken;
    }


}