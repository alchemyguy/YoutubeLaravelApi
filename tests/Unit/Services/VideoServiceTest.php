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
}
