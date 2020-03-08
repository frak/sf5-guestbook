<?php

namespace App\Tests\Controller;

use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ConferenceControllerTest extends WebTestCase
{
    public function testHomepageRenders(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        static::assertResponseIsSuccessful();
        static::assertSelectorTextContains('h2', 'Give your feedback');
    }

    public function testConferencePageRendersCorrectly(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        static::assertCount(3, $crawler->filter('h4'), 'Wrong number of conferences are visible');

        $client->clickLink('View');

        static::assertPageTitleContains('Amsterdam');
        static::assertResponseIsSuccessful();
        static::assertSelectorTextContains('h2', 'Amsterdam 2019');
        static::assertSelectorExists('div:contains("There are 1 comment(s)")');
    }

    public function testCommentsCanBeSubmitted()
    {
        $client = static::createClient();
        $client->request('GET', '/conference/amsterdam-2019');
        $client->submitForm('Submit', [
            'comment_form[author]' => 'Fabien',
            'comment_form[text]' => 'A test comment',
            'comment_form[email]' => $email = 'test@example.com',
            'comment_form[photo]' => dirname(__DIR__, 2).'/public/images/under-construction.gif',
        ]);

        $comment = self::$container->get(CommentRepository::class)->findOneByEmail($email);
        $comment->setState('published');
        self::$container->get(EntityManagerInterface::class)->flush();

        static::assertResponseRedirects();
        $client->followRedirect();
        static::assertSelectorExists('div:contains("There are 2 comment(s)")');
    }
}
