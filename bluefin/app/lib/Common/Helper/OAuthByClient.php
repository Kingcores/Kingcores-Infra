<?php

namespace Common\Helper;

use WBT\Model\Weibotui\OAuthClient;
use WBT\Model\Weibotui\OAuthClientLevel;
use WBT\Model\Weibotui\OAuthToken;

require_once 'OAuth2/OAuth2.php';
require_once 'OAuth2/IOAuth2Storage.php';
require_once 'OAuth2/IOAuth2GrantClient.php';

class OAuthByClient implements \IOAuth2GrantClient
{
    public static function createHandler()
    {
        return new WBTOAuth2(new OAuthByClient(), [\OAuth2::CONFIG_ACCESS_LIFETIME => 86400]);
    }

    /**
     * @var \WBT\Model\Weibotui\OAuthClient
     */
    protected $_clientCache;

    /**
     * Make sure that the client credentials is valid.
     *
     * @param $client_id
     * Client identifier to be check with.
     * @param $client_secret
     * (optional) If a secret is required, check that they've given the right one.
     *
     * @return
     * TRUE if the client credentials are valid, and MUST return FALSE if it isn't.
     * @endcode
     *
     * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-20#section-3.1
     *
     * @ingroup oauth2_section_3
     */
    public function checkClientCredentials($client_id, $client_secret = NULL)
    {
        if (!is_int_val($client_id) || empty($client_secret) || strlen($client_secret) != 32)
            return false;

        isset($this->_clientCache) || ($this->_clientCache = new OAuthClient($client_id));

        if ($this->_clientCache->isEmpty()) return false;

        return $client_secret == $this->_clientCache->getSecret();
    }

    /**
     * Get client details corresponding client_id.
     *
     * OAuth says we should store request URIs for each registered client.
     * Implement this function to grab the stored URI for a given client id.
     *
     * @param $client_id
     * Client identifier to be check with.
     *
     * @return array
     * Client details. Only mandatory item is the "registered redirect URI", and MUST
     * return FALSE if the given client does not exist or is invalid.
     *
     * @ingroup oauth2_section_4
     */
    public function getClientDetails($client_id)
    {
        $client = new OAuthClient($client_id);
        if ($client->isEmpty()) return false;

        return [ OAuthClient::NAME => $client->getName(), OAuthClient::REDIRECT_URI => $client->getRedirectUri() ];
    }

    /**
     * Look up the supplied oauth_token from storage.
     *
     * We need to retrieve access token data as we create and verify tokens.
     *
     * @param $oauth_token
     * oauth_token to be check with.
     *
     * @return
     * An associative array as below, and return NULL if the supplied oauth_token
     * is invalid:
     * - client_id: Stored client identifier.
     * - expires: Stored expiration in unix timestamp.
     * - scope: (optional) Stored scope values in space-separated string.
     *
     * @ingroup oauth2_section_7
     */
    public function getAccessToken($oauth_token)
    {
        $token = new OAuthToken($oauth_token);
        if ($token->isEmpty()) return null;

        return ['access_token' => $oauth_token, 'client_id' => $token->getClient(), 'user_id' => $token->getUser(), 'expires' => strtotime($token->getExpires()), 'scope' => $token->getScope()];
    }

    /**
     * Store the supplied access token values to storage.
     *
     * We need to store access token data as we create and verify tokens.
     *
     * @param $oauth_token
     * oauth_token to be stored.
     * @param $client_id
     * Client identifier to be stored.
     * @param $user_id
     * User identifier to be stored.
     * @param $expires
     * Expiration to be stored.
     * @param $scope
     * (optional) Scopes to be stored in space-separated string.
     *
     * @ingroup oauth2_section_4
     */
    public function setAccessToken($oauth_token, $client_id, $user_id, $expires, $scope = NULL)
    {
        $token = new OAuthToken($oauth_token);
        $token->setAccessToken($oauth_token)
            ->setClient($client_id)
            ->setUser($user_id)
            ->setExpires($expires)
            ->setScope($scope)
            ->save();
    }

    /**
     * Check restricted grant types of corresponding client identifier.
     *
     * If you want to restrict clients to certain grant types, override this
     * function.
     *
     * @param $client_id
     * Client identifier to be check with.
     * @param $grant_type
     * Grant type to be check with, would be one of the values contained in
     * OAuth2::GRANT_TYPE_REGEXP.
     *
     * @return
     * TRUE if the grant type is supported by this client identifier, and
     * FALSE if it isn't.
     *
     * @ingroup oauth2_section_4
     */
    public function checkRestrictedGrantType($client_id, $grant_type)
    {
        return $grant_type == \OAuth2::GRANT_TYPE_CLIENT_CREDENTIALS && $this->_clientCache->getLevel() == 'private_client';
    }

    /**
     * Required for OAuth2::GRANT_TYPE_CLIENT_CREDENTIALS.
     *
     * @param $client_id
     * Client identifier to be check with.
     * @param $client_secret
     * (optional) If a secret is required, check that they've given the right one.
     *
     * @return
     * TRUE if the client credentials are valid, and MUST return FALSE if it isn't.
     * When using "client credentials" grant mechanism and you want to
     * verify the scope of a user's access, return an associative array
     * with the scope values as below. We'll check the scope you provide
     * against the requested scope before providing an access token:
     * @code
     * return array(
     * 'scope' => <stored scope values (space-separated string)>,
     * );
     * @endcode
     *
     * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-20#section-4.4.2
     *
     * @ingroup oauth2_section_4
     */
    public function checkClientCredentialsGrant($client_id, $client_secret)
    {
        return ['scope' => 'sso sna payment'];
    }
}
