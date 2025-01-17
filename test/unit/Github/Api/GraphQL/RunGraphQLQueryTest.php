<?php

declare(strict_types=1);

namespace Laminas\AutomaticReleases\Test\Unit\Github\Api\GraphQL;

use Laminas\AutomaticReleases\Github\Api\GraphQL\RunGraphQLQuery;
use Laminas\Diactoros\Request;
use Laminas\Diactoros\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psl\SecureRandom;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;

final class RunGraphQLQueryTest extends TestCase
{
    /** @var ClientInterface&MockObject */
    private ClientInterface $httpClient;

    /** @psalm-var non-empty-string */
    private string $apiToken;

    private RunGraphQLQuery $runQuery;

    protected function setUp(): void
    {
        parent::setUp();

        $this->httpClient = $this->createMock(ClientInterface::class);
        $messageFactory   = $this->createMock(RequestFactoryInterface::class);

        $this->apiToken = 'apiToken' . SecureRandom\string(8);
        $this->runQuery = new RunGraphQLQuery(
            $messageFactory,
            $this->httpClient,
            $this->apiToken
        );

        $messageFactory
            ->method('createRequest')
            ->with('POST', 'https://api.github.com/graphql')
            ->willReturn(new Request('https://the-domain.com/the-path'));
    }

    public function testSuccessfulRequest(): void
    {
        $validResponse = new Response();

        $validResponse->getBody()->write(<<<'JSON'
{
    "data": {"foo": "bar"}
}
JSON
        );

        $this
            ->httpClient
            ->expects(self::once())
            ->method('sendRequest')
            ->with(self::callback(function (RequestInterface $request): bool {
                self::assertSame(
                    [
                        'Host'          => ['the-domain.com'],
                        'Content-Type'  => ['application/json'],
                        'User-Agent'    => ['Ocramius\'s minimal GraphQL client - stolen from Dunglas'],
                        'Authorization' => ['bearer ' . $this->apiToken],
                    ],
                    $request->getHeaders()
                );

                self::assertSame(
                    '{"query":"the-query","variables":{"a":"b"}}',
                    $request->getBody()->__toString()
                );

                return true;
            }))
            ->willReturn($validResponse);

        self::assertSame(
            ['foo' => 'bar'],
            $this->runQuery->__invoke('the-query', ['a' => 'b'])
        );
    }
}
