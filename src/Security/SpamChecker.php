<?php

namespace App\Security;

use App\Entity\Comment;
use RuntimeException;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SpamChecker
{
    /** @var HttpClientInterface */
    private $httpClient;
    /** @var string */
    private $endpoint;

    /**
     * SpamChecker constructor.
     *
     * @param HttpClientInterface $httpClient
     * @param string              $akismetKey
     */
    public function __construct(HttpClientInterface $httpClient, string $akismetKey)
    {
        $this->httpClient = $httpClient;
        $this->endpoint = "https://{$akismetKey}.rest.akismet.com/1.1/comment-check";
    }

    /**
     * Use the Akismet spam checking service to vet a comment.
     *
     * @return int Spam score: 0: not spam, 1: maybe spam, 2: blatant spam
     * @throws TransportExceptionInterface
     */
    public function getSpamScore(Comment $comment, array $context): int
    {
        $response = $this->httpClient->request(
            'POST',
            $this->endpoint,
            [
                'body' => array_merge(
                    $context,
                    [
                        'blog' => 'https://guestbook.wip/',
                        'comment_type' => 'comment',
                        'comment_author' => $comment->getAuthor(),
                        'comment_author_email' => $comment->getEmail(),
                        'comment_content' => $comment->getText(),
                        'comment_date_gmt' => $comment->getCreatedAt()->format('c'),
                        'blog_lang' => 'en',
                        'blog_charset' => 'UTF-8',
                        'is_test' => false,
                    ]
                ),
            ]
        );

        $headers = $response->getHeaders();
        $content = $response->getContent();

        if (isset($headers['x-akismet-debug-help'][0])) {
            throw new RuntimeException(sprintf('Unable to check for spam: %s
(%s).', $content, $headers['x-akismet-debug-help'][0]));
        }

        if ('discard' === ($headers['x-akismet-pro-tip'][0] ?? '')) {
            return 2;
        }

        return 'true' === $content ? 1 : 0;
    }
}
