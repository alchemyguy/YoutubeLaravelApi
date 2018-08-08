<?php

namespace alchemyguy\YoutubeLaravelApi;

use alchemyguy\YoutubeLaravelApi\Auth\AuthService;
use Exception;

class ChannelService extends AuthService {
	/**
	 * [channelsListById -gets the channnel details and ]
	 * @param  $part    [id,snippet,contentDetails,status, statistics, contentOwnerDetails, brandingSettings]
	 * @param  $params  [array channels id(comma separated ids ) or you can get ('forUsername' => 'GoogleDevelopers')]
	 * @return          [json object of response]
	 */
	public function channelsListById($part, $params) {
		try {

			$params = array_filter($params);

			/**
			 * [$service instance of Google_Service_YouTube]
			 * [$response object of channel lists][making api call to list channels]
			 * @var [type]
			 */

			$service = new \Google_Service_YouTube($this->client);
			$respone = $service->channels->listChannels($part, $params);

			return $service->channels->listChannels($part, $params);

		} catch (\Google_Service_Exception $e) {
			throw new Exception($e->getMessage(), 1);

		} catch (\Google_Exception $e) {
			throw new Exception($e->getMessage(), 1);

		} catch (Exception $e) {
			\Log::info(json_encode($e->getMessage()));
			throw new Exception(json_encode($e->getMessage()), 1);
		}
	}

	public function getChannelDetails($token) {
		try {
			if (!$this->setAccessToken($token)) {
				return false;
			}
			$part = "snippet,contentDetails,statistics,brandingSettings";
			$params = array('mine' => true);
			$service = new \Google_Service_YouTube($this->client);
			$response = $service->channels->listChannels($part, $params);

			$response = json_decode(json_encode($response), true);
			return $response['items'][0];

		} catch (\Google_Service_Exception $e) {
			throw new Exception($e->getMessage(), 1);

		} catch (\Google_Exception $e) {
			throw new Exception($e->getMessage(), 1);

		} catch (Exception $e) {
			throw new Exception($e->getMessage(), 1);
		}
	}
	/**
	 * [updateChannelBrandingSettings update channel details]
	 * @param  $google_token [auth token for the channel]
	 * @param  $properties   ['id' => '',
	 *						          'brandingSettings.channel.description' => '',
	 *						          'brandingSettings.channel.keywords' => '',
	 *						          'brandingSettings.channel.defaultLanguage' => '',
	 *						          'brandingSettings.channel.defaultTab' => '',
	 *						          'brandingSettings.channel.moderateComments' => '',
	 *						          'brandingSettings.channel.showRelatedChannels' => '',
	 *						          'brandingSettings.channel.showBrowseView' => '',
	 *						          'brandingSettings.channel.featuredChannelsTitle' => '',
	 *						          'brandingSettings.channel.featuredChannelsUrls[]' => '',
	 *						          'brandingSettings.channel.unsubscribedTrailer' => '')
	 *						         ]
	 * @param  $part         [ brandingSettings ]
	 * @param  $params       ['onBehalfOfContentOwner' => '']
	 * @return               [boolean ]
	 */
	public function updateChannelBrandingSettings($googleToken, $properties, $part, $params) {
		try {
			$params = array_filter($params);

			/**
			 * [$service description]
			 * @var [type]
			 */
			$service = new \Google_Service_YouTube($this->client);
			$propertyObject = $this->createResource($properties);

			$resource = new \Google_Service_YouTube_Channel($propertyObject);
			$service->channels->update($part, $resource, $params);

			return true;

		} catch (\Google_Service_Exception $e) {
			throw new Exception($e->getMessage(), 1);

		} catch (\Google_Exception $e) {
			throw new Exception($e->getMessage(), 1);

		} catch (Exception $e) {
			throw new Exception($e->getMessage(), 1);
		}
	}

	/**
	 * [parseSubscriptions modified for backUp]
	 * @param  [type] $part
	 * @return [type] $params          array('channelId'= '', "maxResults"='' )
	 */
	public function subscriptionByChannelId($params, $part = 'snippet') {
		try {

			$params = array_filter($params);

			$service = new \Google_Service_YouTube($this->client);
			return $service->subscriptions->listSubscriptions($part, $params);

		} catch (\Google_Service_Exception $e) {
			throw new Exception($e->getMessage(), 1);

		} catch (\Google_Exception $e) {
			throw new Exception($e->getMessage(), 1);

		} catch (Exception $e) {
			throw new Exception($e->getMessage(), 1);
		}
	}

	/**
	 * [parseSubscriptions modified for backUp]
	 * @param  [type] $channelId [description]
	 * @return [type]            [description]
	 */
	public function parseSubscriptions($channelId, $token) {

		try {
			if (!$this->setAccessToken($token)) {
				return false;
			}

			$service = new \Google_Service_YouTube($this->client);
			$part = "snippet";
			$params = array('channelId' => $channelId, "maxResults" => 20);
			$nextPageToken = 1;
			$subscriptions = [];
			while ($nextPageToken) {
				$response = $service->subscriptions->listSubscriptions($part, $params);
				$response = json_decode(json_encode($response), true);
				$sub = array_column($response['items'], 'snippet');
				$sub2 = array_column($sub, 'resourceId');
				$subscriptions = array_merge($subscriptions, $sub2);

				$nextPageToken = isset($response["nextPageToken"]) ? $response['nextPageToken'] : false;
				$params['pageToken'] = $nextPageToken;
			}

			return $subscriptions;

		} catch (\Google_Service_Exception $e) {
			throw new Exception($e->getMessage(), 1);

		} catch (\Google_Exception $e) {
			throw new Exception($e->getMessage(), 1);

		} catch (Exception $e) {
			throw new Exception($e->getMessage(), 1);
		}

	}

	/**
	 *
	 * properties -  array('snippet.resourceId.kind' => 'youtube#channel','snippet.resourceId.channelId' => 'UCqIOaYtQak4-FD2-yI7hFkw'),
	 * part  = 'snippet'
	 * @param string $value [description]
	 */
	public function addSubscriptions($properties, $token, $part = 'snippet', $params = []) {
		try {

			$setAccessToken = $this->setAccessToken($token);

			if (!$setAccessToken) {
				return false;
			}

			$service = new \Google_Service_YouTube($this->client);

			$params = array_filter($params);
			$propertyObject = $this->createResource($properties);

			$resource = new \Google_Service_YouTube_Subscription($propertyObject);
			$response = $service->subscriptions->insert($part, $resource, $params);
			return $response;

		} catch (\Google_Service_Exception $e) {
			throw new Exception($e->getMessage(), 1);

		} catch (\Google_Exception $e) {
			throw new Exception($e->getMessage(), 1);

		} catch (Exception $e) {
			throw new Exception($e->getMessage(), 1);
		}

	}

	public function removeSubscription($token, $subscriptionId, $params = []) {
		try {

			$setAccessToken = $this->setAccessToken($token);

			if (!$setAccessToken) {
				return false;
			}

			$service = new \Google_Service_YouTube($this->client);

			$params = array_filter($params);

			$response = $service->subscriptions->delete($subscriptionId, $params);

		} catch (\Google_Service_Exception $e) {
			throw new Exception($e->getMessage(), 1);

		} catch (\Google_Exception $e) {
			throw new Exception($e->getMessage(), 1);

		} catch (Exception $e) {
			throw new Exception($e->getMessage(), 1);
		}

	}

}
