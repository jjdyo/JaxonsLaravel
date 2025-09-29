<?php

namespace Tests\Feature\Controllers;

use App\Services\Slack\SlashCommandService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery as m;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class SlackSlashControllerTest extends TestCase
{
    use RefreshDatabase;

    private function bindServiceMock(string $expectedNormalized, array $payload = [], array $postResult = null): void
    {
        $defaultPayload = [
            'response_type' => 'in_channel',
            'text' => 'stub-text',
        ];
        $built = [
            'payload' => $payload ?: $defaultPayload,
            'text' => $payload['text'] ?? 'stub-text',
            'response_type' => 'in_channel',
            'has_blocks' => isset($payload['attachments']),
        ];

        $mock = m::mock(SlashCommandService::class);
        $mock->shouldReceive('routeAndBuild')
            ->once()
            ->with($expectedNormalized, m::any())
            ->andReturn($built);

        if ($postResult !== null) {
            $mock->shouldReceive('postToResponseUrl')
                ->once()
                ->andReturn($postResult);
        } else {
            $mock->shouldNotReceive('postToResponseUrl');
        }

        $this->app->instance(SlashCommandService::class, $mock);
    }

    public function test_handles_null_or_missing_command_gracefully(): void
    {
        $this->bindServiceMock('');

        $response = $this->post('/api/slack/slash', [
            // intentionally omit 'command'
        ]);

        $response->assertOk()
            ->assertJson([
                'ok' => true,
                'acknowledged' => true,
            ]);
    }

    public function test_handles_non_string_command_by_treating_as_empty(): void
    {
        $this->bindServiceMock('');

        $response = $this->post('/api/slack/slash', [
            'command' => ['not-a-string'],
        ]);

        $response->assertOk()
            ->assertJson([
                'ok' => true,
                'acknowledged' => true,
            ]);
    }

    public function test_handles_invalid_command(): void
    {
        $this->bindServiceMock('/invalid');

        $response = $this->post('/api/slack/slash', [
            'command' => '/invalid',
        ]);

        $response->assertOk()
            ->assertJson([
                'ok' => true,
                'acknowledged' => true,
            ]);
    }

    #[DataProvider('commandNormalizationProvider')]
    public function test_command_normalization(string $input, string $expected): void
    {
        $this->bindServiceMock($expected);
        $response = $this->post('/api/slack/slash', ['command' => $input]);
        $response->assertOk()->assertJson(['ok' => true, 'acknowledged' => true]);
    }

    public static function commandNormalizationProvider(): array
    {
        return [
            'internal whitespace' => ['/hand book', '/handbook'],
            'trailing spaces' => ['/handbook  ', '/handbook'],
            'leading spaces' => ['  /handbook', '/handbook'],
            'multiple spaces' => ['/hand  book', '/handbook'],
        ];
    }

    public function test_posts_to_response_url_when_provided(): void
    {
        $payload = [
            'response_type' => 'in_channel',
            'text' => 'example2',
        ];

        $this->bindServiceMock('/example2', $payload, [
            'status' => 200,
            'ok' => true,
            'error' => null,
            'body' => 'ok',
        ]);

        $response = $this->post('/api/slack/slash', [
            'command' => '/example2',
            'response_url' => 'https://hooks.slack.com/commands/T000/B000/XXX',
        ]);

        $response->assertOk()->assertJson([
            'ok' => true,
            'acknowledged' => true,
        ]);
    }

    public function test_handles_service_exception_gracefully(): void
    {
        $mock = m::mock(SlashCommandService::class);
        $mock->shouldReceive('routeAndBuild')
            ->once()
            ->andThrow(new \Exception('Service error'));

        $this->app->instance(SlashCommandService::class, $mock);

        $response = $this->post('/api/slack/slash', ['command' => '/test']);

        $response->assertOk()
            ->assertJson([
                'ok' => false,
                'acknowledged' => true,
            ])
            ->assertJsonFragment([
                'error' => 'Unable to process command at this time.',
            ]);
    }
}
