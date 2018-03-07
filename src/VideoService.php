<?php
namespace alchemyguy\YoutubeLaravelApi;

use alchemyguy\YoutubeLaravelApi\Auth\AuthService;
use Exception;

class VideoService extends AuthService {

	/**
	 * [videosListById description]
	 * @param  $part   [snippet,contentDetails,id,statistics](comma separated id's if you want to get more than 1 id details)
	 * @param  $params [regionCode,relevanceLanguage,videoCategoryId, videoDefinition, videoDimension]
	 * @return         [description]
	 */
	public function videosListById($part, $params) {
		try {

			$params = array_filter($params);

			$service = new \Google_Service_YouTube($this->client);
			return $service->videos->listVideos($part, $params);

		} catch (\Google_Service_Exception $e) {
			throw new Exception($e->getMessage(), 1);

		} catch (\Google_Exception $e) {
			throw new Exception($e->getMessage(), 1);

		} catch (Exception $e) {
			throw new Exception($e->getMessage(), 1);
		}
	}

	/**
	 * [searchListByKeyword -get youtube search results by keyoword ]
	 * @param  $part   [snippet,id]
	 * @param  $params ['maxResults','q','type','pageToken']
	 * @return         [json object or response]
	 */
	public function searchListByKeyword($part, $params) {
		try {

			$params = array_filter($params);

			$service = new \Google_Service_YouTube($this->client);
			return $service->search->listSearch($part, $params);

		} catch (\Google_Service_Exception $e) {
			throw new Exception($e->getMessage(), 1);

		} catch (\Google_Exception $e) {
			throw new Exception($e->getMessage(), 1);

		} catch (Exception $e) {
			throw new Exception($e->getMessage(), 1);
		}
	}

	/**
	 * [relatedToVideoId - gets realted videos to a particular video id]
	 * @param  $part   [ sinppet, id]
	 * @param  $params [ regionCode,relatedToVideoId,relevanceLanguage,videoCategoryId, videoDefinition, videoDimension,	type(video or channel)]
	 * @return         [json Object of response]
	 */
	public function relatedToVideoId($part, $params) {
		try {

			$params = array_filter($params);

			$service = new \Google_Service_YouTube($this->client);
			return $service->search->listSearch($part, $params);

		} catch (\Google_Service_Exception $e) {
			throw new Exception($e->getMessage(), 1);

		} catch (\Google_Exception $e) {
			throw new Exception($e->getMessage(), 1);

		} catch (Exception $e) {
			throw new Exception($e->getMessage(), 1);
		}
	}

	/**
	 * [uploadVideo upload a video to youtube channel]
	 * @param  $google_token [authorization token for the youtube channel ]
	 * @param  $videoPath    [path of the video to be uploaded][max video size 128 GB]
	 * @param  $data         [video details]
	 * @return               [boolean]
	 */
	public function uploadVideo($googleToken, $videoPath, $data) {
		try {

			if (!isset($data['title']) || !isset($data['description']) || !isset($data['tags']) || !isset($data['category_id']) || !isset($data['video_status'])) {
				throw new Exception($e->getMessage(), 1);
				return false;
			}

			/**
			 * [setAccessToken [setting accent token to client]]
			 */
			$setAccessToken = $this->setAccessToken($googleToken);
			if (!$setAccessToken) {
				return false;
			}

			/**
			 * [youtube [instance of Google_Service_YouTube] ]
			 */
			$youtube = new \Google_Service_YouTube($this->client);

			/**
			 * snippet [title, description, tags and category ID]
			 * asset resource [snippet metadata and type.]
			 */
			$snippet = new \Google_Service_YouTube_VideoSnippet();

			$snippet->setTitle($data['title']);
			$snippet->setDescription($data['description']);
			$snippet->setTags($data['tags']);
			$snippet->setCategoryId($data['category_id']);

			/**
			 * video status ["public", "private", "unlisted"]
			 */
			$status = new \Google_Service_YouTube_VideoStatus();
			$status->privacyStatus = $data['video_status'];

			/**
			 * snippet and status [link with new video resource.]
			 */
			$video = new \Google_Service_YouTube_Video();
			$video->setSnippet($snippet);
			$video->setStatus($status);

			/**
			 * size of chunk to be uploaded  in bytes [default  1 * 1024 * 1024] (Set a higher value for reliable connection as fewer chunks lead to faster uploads)
			 */
			if (isset($data['chunk_size'])) {
				$chunkSizeBytes = $data['chunk_size'];
			} else {
				$chunkSizeBytes = 1 * 1024 * 1024;
			}

			/**
			 * Setting the defer flag to true tells the client to return a request which can be called with ->execute(); instead of making the API call immediately
			 */
			$this->client->setDefer(true);

			/**
			 * request [API's videos.insert method] [ to create and upload the video]
			 */
			$insertRequest = $youtube->videos->insert("status,snippet", $video);

			/**
			 * MediaFileUpload object [resumable uploads]
			 */
			$media = new \Google_Http_MediaFileUpload(
				$this->client,
				$insertRequest,
				'video/*',
				null,
				true,
				$chunkSizeBytes
			);

			$media->setFileSize(filesize($videoPath));

			/**
			 * Read the media file [to upload chunk by chunk]
			 */
			$status = false;
			$handle = fopen($videoPath, "rb");
			while (!$status && !feof($handle)) {

				$chunk = fread($handle, $chunkSizeBytes);
				$status = $media->nextChunk($chunk);
			}

			fclose($handle);

			/**
			 * set defer to false [to make other calls after the file upload]
			 */
			$this->client->setDefer(false);
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
	 * [videosDelete delete a youtube video]
	 * @param  $google_token [auth token for the channel owning the video]
	 * @param  $id           [video id]
	 * @param  $params       [onbelhalf of owner]
	 * @return               [json obj response]
	 */
	public function deleteVideo($googleToken, $id, $params = []) {
		try {

			/**
			 * [setAccessToken [setting accent token to client]]
			 */
			$setAccessToken = $this->setAccessToken($googleToken);
			if (!$setAccessToken) {
				return false;
			}

			/**
			 * [$service (instance of Google_Service_YouTube)]
			 */
			$params = array_filter($params);

			$service = new \Google_Service_YouTube($this->client);
			return $service->videos->delete($id, $params);

		} catch (\Google_Service_Exception $e) {
			throw new Exception($e->getMessage(), 1);

		} catch (\Google_Exception $e) {
			throw new Exception($e->getMessage(), 1);

		} catch (Exception $e) {
			throw new Exception($e->getMessage(), 1);
		}
	}

	/*
	 * [adds like dislike or remove ratiing]
	 */
	public function videosRate($googleToken, $id, $rating = 'like', $params = []) {

		try {

			$setAccessToken = $this->setAccessToken($googleToken);
			if (!$setAccessToken) {
				return false;
			}

			$service = new Google_Service_YouTube($client);

			$params = array_filter($params);
			$response = $service->videos->rate(
				$id, $rating,
				$params
			);

		} catch (\Google_Service_Exception $e) {
			throw new Exception($e->getMessage(), 1);

		} catch (\Google_Exception $e) {
			throw new Exception($e->getMessage(), 1);

		} catch (Exception $e) {
			throw new Exception($e->getMessage(), 1);
		}
	}

}