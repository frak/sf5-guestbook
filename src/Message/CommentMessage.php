<?php

namespace App\Message;

class CommentMessage
{
    /** @var int */
    private $id;
    /** @var string */
    private $reviewUrl;
    /** @var array */
    private $context;

    /**
     * CommentMessage constructor.
     *
     * @param int    $id
     * @param string $reviewUrl
     * @param array  $context
     */
    public function __construct(int $id, string $reviewUrl, array $context = [])
    {
        $this->id = $id;
        $this->reviewUrl = $reviewUrl;
        $this->context = $context;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getReviewUrl(): string
    {
        return $this->reviewUrl;
    }

    /**
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }
}
