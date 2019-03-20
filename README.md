#  Youtube Laravel Api
 `PHP (Laravel) Package for Google / YouTube API V3 with Google Auth`

## Features

- [x] [Google Auth](#google-auth)
- [x] [Full Live Streaming API for Youtube](#full-live-streaming-api) 
- [x] [Full Youtube Channel API](#full-youtube-channel-api)
- [x] [Full Youtube Video API](#full-youtube-video-api)


## Installation
 
```shell
composer require alchemyguy/youtube-laravel-api
```

Add Service provider to config/app.php provider's array:
```php
alchemyguy\YoutubeLaravelApi\YoutubeLaravelApiServiceProvider::class
```

Execute the following command to get the configurations:
```shell
php artisan vendor:publish --tag='youtube-config'
```

## Steps to create your google oauth credentials:

1. Goto `https://console.developers.google.com`
2. Login with your credentials & then create a new project.
3. Enable the following features while creating key
	- Youtube Data API
	- Youtube Analytics API
	- Youtube Reporting API
4. Then create `API key` from credentials tab.
5. Then in OAuth Consent Screen enter the `product name`(your site name). 
6. create credentials > select OAuth Client ID. (here you will get client_id and client_secret)
7. Then in the Authorized Javascript Origins section add `you site url`.
8. In the Authorized Redirect URLs section add `add a url which you want the auth code to return`(login callback)
9. You will get values (to be exact - client_id, client_secret & api_key) 
10. Then add these values - client_id, client_secret, api_key and redirect_url in the env file and you can start using the package now.


## Usage :

### Google Auth 

- **Add Code to call the api class**

```php

<?php
namespace Your\App\NameSpace;

use  alchemyguy\YoutubeLaravelApi\AuthenticateService;	

```

- **Generating an Auth-Url**
```php

$authObject  = new AuthenticateService;

# Replace the identifier with a unqiue identifier for account or channel
$authUrl = $authObject->getLoginUrl('email','identifier'); 

```

- **Fetching the Auth Code and Identifier**
	Now once the user authorizes by visiting the url, the authcode will be redirected to the redirect_url specified in .env with params as code( this will be auth code) and  state (this will be identifier we added during making the loginUrl)

```php
$code = Input::get('code');
$identifier = Input::get('state');

```

-  **Auth-Token and Details For Channel**

```php
$authObject  = new AuthenticateService;
$authResponse = $authObject->authChannelWithCode($code);
```

- **This will return an array**: 
```
$authResponse['token'] (Channel Token)
$authResponse['channel_details']
$authResponse['live_streaming_status'] (enabled or disabled)
```

### Full Live Streaming API 

- **Add Code to call the api class**

```php
<?php
namespace Your\App\NameSpace;

use  alchemyguy\YoutubeLaravelApi\LiveStreamService;	
```

- **Creating a Youtube Event**

```php
# data format creating live event
$data = array(
	"title" => "",
	"description" => "",
	"thumbnail_path" => "",				// Optional
	"event_start_date_time" => "",
	"event_end_date_time" => "",			// Optional
	"time_zone" => "",
	'privacy_status' => "",				// default: "public" OR "private"
	"language_name" => "",				// default: "English"
	"tag_array" => ""				// Optional and should not be more than 500 characters
);

$ytEventObj = new LiveStreamService();
/**
 * The broadcast function returns array of details from YouTube.
 * Store this information & will be required to supply to youtube 
 * for live streaming using encoder of your choice. 
 */
$response = $ytEventObj->broadcast($authToken, $data);
if ( !empty($response) ) {

	$youtubeEventId = $response['broadcast_response']['id'];
	$serverUrl = $response['stream_response']['cdn']->ingestionInfo->ingestionAddress;
	$serverKey = $response['stream_response']['cdn']->ingestionInfo->streamName;
}

```

- **Updating a Youtube Event**

```php
$ytEventObj = new LiveStreamService();
/**
* The updateBroadcast response give details of the youtube_event_id,server_url and server_key. 
* The server_url & server_key gets updated in the process. (save the updated server_key and server_url).
*/
$response = $ytEventObj->updateBroadcast($authToken, $data, $youtubeEventId);

// $youtubeEventId = $response['broadcast_response']['id'];
// $serverUrl = $response['stream_response']['cdn']->ingestionInfo->ingestionAddress;
// $serverKey = $response['stream_response']['cdn']->ingestionInfo->streamName
```

- **Deleting a Youtube Event**

```php
$ytEventObj = new LiveStreamService();

# Deleting the event requires authentication token for the channel in which the event is created and the youtube_event_id
$ytEventObj->deleteEvent($authToken, $youtubeEventId);
```

- Starting a Youtube Event Stream:

```php
$ytEventObj = new LiveStreamService();
/**
 * $broadcastStatus - ["testing", "live"]
 * Starting the event takes place in 3 steps
 * 1. Start sending the stream to the server_url via server_key recieved as a response in creating the event via the encoder of your choice.
 * 2. Once stream sending has started, stream test should be done by passing $broadcastStatus="testing" & it will return response for stream status.
 * 3. If transitioEvent() returns successfull for testing broadcast status, then start live streaming your video by passing $broadcastStatus="live" 
 * & in response it will return us the stream status.
 */ 
$streamStatus = $ytEventObj->transitionEvent($authToken, $youtubeEventId, $broadcastStatus);	
```

- **Stopping a Youtube Event Stream**

```php
$ytEventObj = new LiveStreamService();
/**
 * $broadcastStatus - ["complete"]
 * Once live streaming gets started succesfully. We can stop the streaming the video by passing broadcastStatus="complete" and in response it will give us the stream status.
 */
$ytEventObj->transitionEvent($authToken, $youtubeEventId, $broadcastStatus);	// $broadcastStatus = ["complete"]
```


### Full Youtube Channel API

- **Add Code to call the api class**

```php
<?php
namespace Your\App\NameSpace;

use  alchemyguy\YoutubeLaravelApi\ChannelService;
```

- **Channel details By Channel Id**
	If you want channel details for multiple channels add channel id saperated by commas(,) in param
	`NOTE: This doesn't require authorization token`

```php
/**
 * [channelsListById -gets the channnel details and ]
 *   $part    'id,snippet,contentDetails,status, statistics, contentOwnerDetails, brandingSettings'
 *  $params  [array channels id(comma separated ids ) or you can get ('forUsername' => 'GoogleDevelopers')]
 */

$part = 'id,snippet';
$params = array('id'=> 'channel_1_id,channel_2_id');
$channelServiceObject  = new ChannelService;
$channelDetails = $channelServiceObject->channelsListById($part, $params);

```

- **Channel Detail by Token**
	Channel Details of the users channel which has authorized token

```php 
$channelServiceObject  = new ChannelService;
$channelDetails = $channelServiceObject->getChannelDetails($authToken);
```

- **Channel Subscription List**
	List of subscriptions of the channel

```php

/*
* $params array('channel_id'=>'',
*				'max_results'= 10)
*/
$channelServiceObject  = new ChannelService;
$channelDetails = $channelServiceObject->subscriptionByChannelId($params);
```

- **Add Subscriptions For Authorized Channel**

```php 
/*
* properties  array('snippet.resourceId.kind' => 'youtube#channel','snippet.resourceId.channelId' => 'UCqIOaYtQak4-FD2-yI7hFkw')
*/
$channelServiceObject  = new ChannelService;
$response = $channelServiceObject->addSubscriptions($properties, $token, $part='snippet', $params=[]);

```

-**Remove Subscriptions For Authorized Channel**
To remove subscription we need subscription id which can be found from subscription list.
```php
$response = $channelServiceObject->removeSubscription( $token, $subscriptionId);

```

- **Update Channel Branding Settings**
	Updates the channel details and preferences.

```php
/*
 *      $properties array('id' => '',
 *					'brandingSettings.channel.description' => '',
 *					'brandingSettings.channel.keywords' => '',
 *					'brandingSettings.channel.defaultLanguage' => '',
 *					'brandingSettings.channel.defaultTab' => '',
 *					'brandingSettings.channel.moderateComments' => '',
 *					'brandingSettings.channel.showRelatedChannels' => '',
 *					'brandingSettings.channel.showBrowseView' => '',
 *					'brandingSettings.channel.featuredChannelsTitle' => '',
 *					'brandingSettings.channel.featuredChannelsUrls[]' => '',
 *					'brandingSettings.channel.unsubscribedTrailer' => '')
 */

$channelServiceObject  = new ChannelService;
$response = $channelServiceObject->updateChannelBrandingSettings($googleToken, $properties);
```

### Full Youtube Video API

- **Add Code to call the api class**

```php
<?php
namespace Your\App\NameSpace;

use  alchemyguy\YoutubeLaravelApi\VideoService;
```

- **List Video By Id**

```php
$part ='snippet,contentDetails,id,statistics';
$params =array('id'=>'xyzgh');
$videoServiceObject  = new VideoService;
$response = $videoServiceObject->videosListById($part, $params);
```

- **Upload Video To Your Channel**
```php

/*
* $videoPath  	path to the video
* $data   		array('title'=>"",
*					'description'=>"",
*					'tags'=>"",
*					'category_id'=>"",
*					'video_status'=>"")
*/

$videoServiceObject  = new VideoService;
$response = $videoServiceObject->uploadVideo($googleToken, $videoPath, $data);
```

- **Delete Video To Your Channel**
```php
$videoServiceObject  = new VideoService;
$response = $videoServiceObject->deleteVideo($googleToken, $videoId);
```


- **Rate Video**
	Adding a like, dislike or removing the response from video

```php
# rating  'like' or 'dislike' or 'none'
	$videoServiceObject  = new VideoService;
$response = $videoServiceObject->videosRate($googleToken, $videoId, $rating);

```







