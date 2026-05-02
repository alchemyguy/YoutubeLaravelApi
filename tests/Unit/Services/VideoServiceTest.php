<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\Tests\Unit\Services;

use Alchemyguy\YoutubeLaravelApi\Auth\OAuthService;
use Alchemyguy\YoutubeLaravelApi\Services\VideoService;
use Alchemyguy\YoutubeLaravelApi\Tests\TestCase;
use Google\Client;
use Google\Service\YouTube;
use Google\Service\YouTube\Resource\Videos;
use Google\Service\YouTube\Resource\Search;
use Mockery;

final class VideoServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_list_by_id_returns_videos(): void
    {
        $videos = Mockery::mock(Videos::class);
        $videos->shouldReceive('listVideos')->once()
            ->with('snippet,contentDetails,id,statistics', ['id' => 'vid1'])
            ->andReturn(['items' => [['id' => 'vid1']]]);

        $youtube = Mockery::mock(YouTube::class);
        $youtube->videos = $videos;

        $svc = new class(new OAuthService(Mockery::mock(Client::class))) extends VideoService {
            public ?YouTube $injected = null;
            protected function youtube(): YouTube { return $this->injected; }
        };
        $svc->injected = $youtube;

        $r = $svc->listById(['id' => 'vid1']);
        $this->assertSame('vid1', $r['items'][0]['id']);
    }

    public function test_search_filters_empty_params(): void
    {
        $search = Mockery::mock(Search::class);
        $search->shouldReceive('listSearch')->once()
            ->with('snippet,id', ['q' => 'cats'])
            ->andReturn(['items' => []]);

        $youtube = Mockery::mock(YouTube::class);
        $youtube->search = $search;

        $svc = new class(new OAuthService(Mockery::mock(Client::class))) extends VideoService {
            public ?YouTube $injected = null;
            protected function youtube(): YouTube { return $this->injected; }
        };
        $svc->injected = $youtube;

        $svc->search(['q' => 'cats', 'pageToken' => '']);
    }

    public function test_delete_video_authorizes_and_deletes(): void
    {
        $videos = Mockery::mock(Videos::class);
        $videos->shouldReceive('delete')->once()->with('vid1')->andReturn(null);
        $youtube = Mockery::mock(YouTube::class);
        $youtube->videos = $videos;

        $oauth = Mockery::mock(OAuthService::class);
        $oauth->shouldReceive('setAccessToken')->once();

        $svc = new class($oauth) extends VideoService {
            public ?YouTube $injected = null;
            protected function youtube(): YouTube { return $this->injected; }
        };
        $svc->injected = $youtube;

        $svc->delete(['access_token' => 'tok'], 'vid1');
    }

    /** @bug regression for VideoService::videosRate (Section 6, bug 2) */
    public function test_rate_calls_videos_rate_with_enum_value(): void
    {
        $videos = Mockery::mock(Videos::class);
        $videos->shouldReceive('rate')->once()->with('vid1', 'like')->andReturn(null);
        $youtube = Mockery::mock(YouTube::class);
        $youtube->videos = $videos;

        $oauth = Mockery::mock(OAuthService::class);
        $oauth->shouldReceive('setAccessToken')->once();

        $svc = new class($oauth) extends VideoService {
            public ?YouTube $injected = null;
            protected function youtube(): YouTube { return $this->injected; }
        };
        $svc->injected = $youtube;

        $svc->rate(['access_token' => 'tok'], 'vid1', \Alchemyguy\YoutubeLaravelApi\Enums\Rating::Like);
    }

    public function test_upload_resets_defer_in_finally_even_on_exception(): void
    {
        $client = Mockery::mock(Client::class);
        $client->shouldReceive('setDefer')->ordered()->once()->with(true);
        $client->shouldReceive('setDefer')->ordered()->once()->with(false); // must be called even on failure
        $client->shouldReceive('isAccessTokenExpired')->andReturn(false);
        $client->shouldReceive('setAccessToken')->once();

        $videos = Mockery::mock(Videos::class);
        $videos->shouldReceive('insert')->andThrow(new \Google\Service\Exception('boom'));
        $youtube = Mockery::mock(YouTube::class);
        $youtube->videos = $videos;

        $svc = new class(new OAuthService($client)) extends VideoService {
            public ?YouTube $injected = null;
            protected function youtube(): YouTube { return $this->injected; }
        };
        $svc->injected = $youtube;

        $this->expectException(\Alchemyguy\YoutubeLaravelApi\Exceptions\YoutubeApiException::class);
        $svc->upload(
            ['access_token' => 'tok'],
            __DIR__ . '/../../Fixtures/test_video.txt',
            new \Alchemyguy\YoutubeLaravelApi\DTOs\VideoUploadData(
                'title', 'desc', '22',
                \Alchemyguy\YoutubeLaravelApi\Enums\PrivacyStatus::Public
            ),
        );
    }
}
