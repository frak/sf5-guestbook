<?php

namespace App\Tests\Security;

use App\Entity\Comment;
use App\Security\SpamChecker;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;

class SpamCheckerTest extends TestCase
{
    public function testAnExceptionIsRaisedWhenTheRequestIsInvalid(): void
    {
        $comment = new Comment();
        $comment->setCreatedAtValue();
        $context = [];

        $client = new MockHttpClient(
            [
                new MockResponse('invalid', ['response_headers' => ['x-akismet-debug-help: Invalid key']]),
            ]
        );
        $checker = new SpamChecker($client, 'abcde');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to check for spam: invalid (Invalid key).');

        $checker->getSpamScore($comment, $context);
    }

    /**
     * @dataProvider getComments
     */
    public function testTheScoreForACommentIsReturnedCorrectly(
        int $expectedScore,
        ResponseInterface $response,
        Comment $comment,
        array $context
    ): void {
        $client = new MockHttpClient([$response]);
        $checker = new SpamChecker($client, 'abcde');

        $score = $checker->getSpamScore($comment, $context);
        static::assertSame($expectedScore, $score);
    }

    public function getComments(): iterable
    {
        $comment = new Comment();
        $comment->setCreatedAtValue();
        $context = [];

        $response = new MockResponse('', ['response_headers' => ['x-akismet-pro-tip: discard']]);
        yield 'blatant_spam' => [2, $response, $comment, $context];

        $response = new MockResponse('true');
        yield 'spam' => [1, $response, $comment, $context];

        $response = new MockResponse('false');
        yield 'ham' => [0, $response, $comment, $context];
    }
}
