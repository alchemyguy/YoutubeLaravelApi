<?php

declare(strict_types=1);

return [
    'app_name' => env('YOUTUBE_APP_NAME', 'YoutubeLaravelApi'),
    'client_id' => env('YOUTUBE_CLIENT_ID'),
    'client_secret' => env('YOUTUBE_CLIENT_SECRET'),
    'api_key' => env('YOUTUBE_API_KEY'),
    'redirect_url' => env('YOUTUBE_REDIRECT_URL'),

    /*
    |--------------------------------------------------------------------------
    | YouTube language codes
    |--------------------------------------------------------------------------
    | Map of human-readable language names to YouTube language codes used for
    | broadcast metadata (defaultLanguage, defaultAudioLanguage).
    */
    'languages' => [
        'Afrikaans' => 'af', 'Albanian' => 'sq', 'Amharic' => 'am', 'Arabic' => 'ar',
        'Armenian' => 'hy', 'Azerbaijani' => 'az', 'Bangla' => 'bn', 'Basque' => 'eu',
        'Belarusian' => 'be', 'Bosnian' => 'bs', 'Bulgarian' => 'bg', 'Catalan' => 'ca',
        'Chinese' => 'zh-CN', 'Chinese (Hong Kong)' => 'zh-HK', 'Chinese (Taiwan)' => 'zh-TW',
        'Croatian' => 'hr', 'Czech' => 'cs', 'Danish' => 'da', 'Dutch' => 'nl',
        'English' => 'en', 'English (United Kingdom)' => 'en-GB', 'Estonian' => 'et',
        'Filipino' => 'fil', 'Finnish' => 'fi', 'French' => 'fr', 'French (Canada)' => 'fr-CA',
        'Galician' => 'gl', 'Georgian' => 'ka', 'German' => 'de', 'Greek' => 'el',
        'Gujarati' => 'gu', 'Hebrew' => 'iw', 'Hindi' => 'hi', 'Hungarian' => 'hu',
        'Icelandic' => 'is', 'Indonesian' => 'id', 'Italian' => 'it', 'Japanese' => 'ja',
        'Kannada' => 'kn', 'Kazakh' => 'kk', 'Khmer' => 'km', 'Korean' => 'ko',
        'Kyrgyz' => 'ky', 'Lao' => 'lo', 'Latvian' => 'lv', 'Lithuanian' => 'lt',
        'Macedonian' => 'mk', 'Malay' => 'ms', 'Malayalam' => 'ml', 'Marathi' => 'mr',
        'Mongolian' => 'mn', 'Myanmar (Burmese)' => 'my', 'Nepali' => 'ne', 'Norwegian' => 'no',
        'Persian' => 'fa', 'Polish' => 'pl', 'Portuguese (Brazil)' => 'pt',
        'Portuguese (Portugal)' => 'pt-PT', 'Punjabi' => 'pa', 'Romanian' => 'ro',
        'Russian' => 'ru', 'Serbian' => 'sr', 'Serbian (Latin)' => 'sr-Latn',
        'Sinhala' => 'si', 'Slovak' => 'sk', 'Slovenian' => 'sl',
        'Spanish (Latin America)' => 'es-419', 'Spanish (Spain)' => 'es',
        'Spanish (United States)' => 'es-US', 'Swahili' => 'sw', 'Swedish' => 'sv',
        'Tamil' => 'ta', 'Telugu' => 'te', 'Thai' => 'th', 'Turkish' => 'tr',
        'Ukrainian' => 'uk', 'Urdu' => 'ur', 'Uzbek' => 'uz', 'Vietnamese' => 'vi',
        'Zulu' => 'zu',
    ],
];
