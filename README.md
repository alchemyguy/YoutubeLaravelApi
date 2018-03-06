#  Youtube Laravel Api
 `PHP (Laravel) Package for Google / YouTube API V3 with Google Auth`

## Features
```
- Google Auth
- Full Live Streaming API for Youtube 
- Full Youtube Channel API
- Full Youtube Video API
```

## Installation
 
```shell
composer require alchemyguy/YoutubeLaravelApi
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

	- Add Code to call the api class

```php

<?php
namespace Your\App\NameSpace;

use  alchemyguy\YoutubeLaravelApi\AuthenticateService;	

```

	- Generating an Auth-Url
```php

$authObject  = new AuthenticateService;

# Replace the identifier with a unqiue identifier for account or channel
$authUrl = $authObject->getLoginUrl('email','identifier'); 

```

	- Fetching the Auth Code and Identifier
Now once the user authorizes by visiting the url, the authcode will be redirected to the redirect_url specified in .env with params as code( this will be auth code) and  state (this will be identifier we added during making the loginUrl)

```php
$code = Input::get('code');
$identifier = Input::get('state');

```

	-  Auth-Token and Details For Channel

```php
$authObject  = new AuthenticateService;
$authResponse = $authObject->authChannelWithCode($code);
```

	- This will return an array: 
```
$authResponse['token'] (Channel Token)
$authResponse['channel_details']
$authResponse['live_streaming_status'] (enabled or disabled)
```

