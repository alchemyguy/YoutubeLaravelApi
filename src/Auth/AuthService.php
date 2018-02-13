<?php 
namespace AlchemyGuy\YoutubeLaravelApi\Auth;

use Exception;

/**
*  Api Service For Auth
*/
class AuthService 
{	
	protected $client;
	protected $yt_language;

	public function __construct()
	{
		$this->client = new \Google_Client;

		$this->client->setClientId(\Config::get('google-config.client_id'));
		$this->client->setClientSecret(\Config::get('google-config.client_secret'));
		$this->client->setDeveloperKey(\Config::get('google-config.api_key'));
		$this->client->setRedirectUri(\Config::get('google-config.redirect_url'));

		$this->client->setScopes([
		                             'https://www.googleapis.com/auth/youtube',
		                         ]);

		$this->client->setAccessType('offline');
		$this->client->setPrompt('consent');
		$this->yt_language = \Config::get('google.yt_language');

	}

	/**	
	 * [getToken -generate token from response code recived on visiting the login url generated]
	 * @param  [type] $code [code for auth]
	 * @return [type]       [authorization token]
	 */
	public function getToken($code)
	{
		try {
			
			$this->client->authenticate($code);
			$token = $this->client->getAccessToken();
			return $token;

		} catch ( \Google_Service_Exception $e ) {

			throw new Exception($e->getMessage(), 1);

		} catch ( \Google_Exception $e ) {

			throw new Exception($e->getMessage(), 1);

		} catch ( Exception $e ) {
			
			throw new Exception($e->getMessage(), 1);

		} 
	}

	/**
	 * [getLoginUrl - generates the url login url to generate auth token]
	 * @param  [type] $youtube_email [account to be authenticated]
	 * @param  [type] $channelId     [return identifier]
	 * @return [type]                [auth url to generate]
	 */
	public function getLoginUrl( $youtube_email, $channelId = null )
	{	
		try
		{	
			if(!empty($channelId))
				$this->client->setState($channelId);

			$this->client->setLoginHint($youtube_email);
			$authUrl = $this->client->createAuthUrl();
			return $authUrl;

		} catch ( \Google_Service_Exception $e ) {

			throw new Exception($e->getMessage(), 1);

		} catch ( \Google_Exception $e ) {

			throw new Exception($e->getMessage(), 1);

		} catch ( Exception $e ) {

			throw new Exception($e->getMessage(), 1);
		} 
		
	}

	/**
     * [setAccessToken -setting the access token to the client]
     * @param [type] $google_token [googel auth token]
     */
    public function setAccessToken($google_token = null)
    {
        try {
            
            if (!is_null($google_token))
                $this->client->setAccessToken($google_token);

            if (!is_null($google_token) && $this->client->isAccessTokenExpired()) {
                $refreshed_token = $this->client->getRefreshToken();
                $this->client->fetchAccessTokenWithRefreshToken($refreshed_token);
                $newToken = $this->client->getAccessToken();
                $newToken = json_encode($newToken);
            }

            return !$this->client->isAccessTokenExpired();

        } catch ( \Google_Service_Exception $e ) {
            
            throw new Exception($e->getMessage(), 1);

        } catch ( \Google_Exception $e ) {

            throw new Exception($e->getMessage(), 1);
        
        } catch(Exception $e) {

            throw new Exception($e->getMessage(), 1);
        }
    }


}