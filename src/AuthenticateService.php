<?php

namespace alchemyguy\YoutubeLaravelApi;

use alchemyguy\YoutubeLaravelApi\Auth\AuthService;
use Carbon\Carbon;
use Exception;

class AuthenticateService extends AuthService {

	protected $googleLiveBroadcastSnippet;
	protected $googleLiveBroadcastStatus;
	protected $googleYoutubeLiveBroadcast;

	public function __construct() {
		parent::__construct();
		$this->googleLiveBroadcastSnippet = new \Google_Service_YouTube_LiveBroadcastSnippet;
		$this->googleLiveBroadcastStatus = new \Google_Service_YouTube_LiveBroadcastStatus;
		$this->googleYoutubeLiveBroadcast = new \Google_Service_YouTube_LiveBroadcast;
	}

	public function authChannelWithCode($code) {
		$authResponse = [];

		$token = $this->getToken($code);
		if (!$token) {
			$authResponse['error'] = 'invalid token';
			return $authResponse;
		}
		$authResponse['token'] = $token;
		$this->setAccessToken($authResponse['token']);
		$authResponse['channel_details'] = $this->channelDetails();
		$authResponse['live_streaming_status'] = $this->liveStreamTest($token) ? 'enabled' : 'disbaled';

		return $authResponse;
	}

	protected function channelDetails() {
		$params = array('mine' => true);
		$params = array_filter($params);
		$part = 'snippet';
		$service = new \Google_Service_YouTube($this->client);
		return $service->channels->listChannels($part, $params);
	}

	protected function liveStreamTest($token) {
		try {
			$response = [];
			/**
			 * [setAccessToken [setting accent token to client]]
			 */
			$setAccessToken = $this->setAccessToken($token);
			if (!$setAccessToken) {
				return false;
			}

			/**
			 * [$service [instance of Google_Service_YouTube ]]
			 * @var [type]
			 */
			$youtube = new \Google_Service_YouTube($this->client);

			$title = "test";
			$description = "test live event";
			$startdt = Carbon::now("Asia/Kolkata");
			$startdtIso = $startdt->toIso8601String();

			$privacy_status = "public";
			$language = 'English';

			/**
			 * Create an object for the liveBroadcast resource [specify snippet's title, scheduled start time, and scheduled end time]
			 */
			$this->googleLiveBroadcastSnippet->setTitle($title);
			$this->googleLiveBroadcastSnippet->setDescription($description);
			$this->googleLiveBroadcastSnippet->setScheduledStartTime($startdtIso);

			/**
			 * object for the liveBroadcast resource's status ["private, public or unlisted"]
			 */
			$this->googleLiveBroadcastStatus->setPrivacyStatus($privacy_status);

			/**
			 * API Request [inserts the liveBroadcast resource]
			 */
			$this->googleYoutubeLiveBroadcast->setSnippet($this->googleLiveBroadcastSnippet);
			$this->googleYoutubeLiveBroadcast->setStatus($this->googleLiveBroadcastStatus);
			$this->googleYoutubeLiveBroadcast->setKind('youtube#liveBroadcast');

			/**
			 * Execute Insert LiveBroadcast Resource Api [return an object that contains information about the new broadcast]
			 */
			$broadcastsResponse = $youtube->liveBroadcasts->insert('snippet,status', $this->googleYoutubeLiveBroadcast, array());
			$response['broadcast_response'] = $broadcastsResponse;
			$youtubeEventId = isset($broadcastsResponse['id']) ? $broadcastsResponse['id'] : false;

			if (!$youtubeEventId) {
				return false;
			}

			$this->deleteEvent($youtubeEventId);
			return true;

		} catch (\Google_Service_Exception $e) {
			/**
			 *  This error is thrown if the Service is 
			 * 	either not available or not enabled for the specific account
			 */
			return false;

		} catch (\Google_Exception $e) {

			throw new Exception($e->getMessage(), 1);

		} catch (Exception $e) {

			throw new Exception($e->getMessage(), 1);
		}

	}

	public function deleteEvent($youtubeEventId) {
		try {

			/**
			 * [$service [instance of Google_Service_YouTube]]
			 */
			$youtube = new \Google_Service_YouTube($this->client);
			$deleteBroadcastsResponse = $youtube->liveBroadcasts->delete($youtubeEventId);
			return true;

		} catch (\Google_Service_Exception $e) {

			throw new Exception($e->getMessage(), 1);

		} catch (\Google_Exception $e) {

			throw new Exception($e->getMessage(), 1);

		} catch (Exception $e) {

			throw new Exception($e->getMessage(), 1);
		}
	}

}