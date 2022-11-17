<?php

namespace App\Controller;

use App\Helper\JitsiAdminController;
use App\Service\CreateHttpsUrl;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\Provider\Auth0Client;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Token\AccessTokenInterface;
use Stevenmaguire\OAuth2\Client\Provider\Keycloak;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class LoginController extends JitsiAdminController
{
    /**
     * @Route("/login/auth0_login", name="login_auth0")
     */
    public function index(ClientRegistry $clientRegistry): Response
    {
      return $clientRegistry->getClient('auth0_main')->redirect(['user']);
    }
    /**
     * @Route("/login/auth0_login/check", name="connect_auth0_check")
     */
    public function check(ClientRegistry $clientRegistry, Request $request)
    {
        // ** if you want to *authenticate* the user, then
        // leave this method blank and create a Guard authenticator
        // (read below)

        /** @var Auth0Client $client */
        $client = $clientRegistry->getClient('auth0_main');

        try {

            $user = $client->fetchUser();

            // do something with all this new power!
            // e.g. $name = $user->getFirstName();
            die;
            // ...
        } catch (IdentityProviderException $e) {
            // something went wrong!
            // probably you should return the reason to the user
            die;
        }
    }
    /**
     * @Route("/logout_keycloak", name="logout_keycloak")
     */
    public function logout(ClientRegistry $clientRegistry, Request $request, CreateHttpsUrl $createHttpsUrl, TokenStorageInterface $tokenStorage)
    {

        $provider = new Keycloak([
            'authServerUrl' => $this->getParameter('KEYCLOAK_URL'),
            'realm' => $this->getParameter('KEYCLOAK_REALM'),
            'clientId' => $this->getParameter('KEYCLOAK_ID'),
            'clientSecret' => $this->getParameter('KEYCLOAK_SECRET'),
        ]);
//
////        $test= $provider->get
////        dump($test);
//        $url = $provider->getBaseAccessTokenUrl(['scope' => ['openid']]);
//        dump($url);
//        $res = $provider->getHttpClient()->request('POST',$url);
//        dump($res);
//        return null;
//        return new RedirectResponse();
////        dump($url);
////        dump($token);
////       $token =  $provider->getAccessToken('id_token');
////        $provider->getAuthorizationUrl(['scope' => ['openid']]);
////        $httpClient->request('POST', $provider->getAuthorizationUrl(['scope' => ['openid']]));
////       $provider->getAuthorizationUrl();   $accesstoken->getToken();

        $url = $provider->getLogoutUrl([
//            'id_token_hint' => $token->getValues()['id_token'],
            'post_logout_redirect_uri' => $createHttpsUrl->createHttpsUrl('/'),
        ]);
        return $this->redirect($url);

    }

}
