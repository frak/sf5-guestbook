<?php

namespace App\DataFixtures;

use App\Entity\Admin;
use App\Entity\Comment;
use App\Entity\Conference;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

class AppFixtures extends Fixture
{
    /** @var EncoderFactoryInterface */
    private $encoderFactory;

    /**
     * AppFixtures constructor.
     *
     * @param EncoderFactoryInterface $encoderFactory
     */
    public function __construct(EncoderFactoryInterface $encoderFactory)
    {
        $this->encoderFactory = $encoderFactory;
    }

    public function load(ObjectManager $manager)
    {
        $amsterdam = new Conference();
        $amsterdam->setCity('Amsterdam')->setYear('2019')->setIsInternational(true);
        $manager->persist($amsterdam);

        $paris = new Conference();
        $paris->setCity('Paris')->setYear('2020')->setIsInternational(false);
        $manager->persist($paris);

        $london = new Conference();
        $london->setCity('London')->setYear('2020')->setIsInternational(true);
        $manager->persist($london);

        $comments = [
            [$paris, 'Mikey', 'mikey@new-signal.com', 'The best conference I have been to in Paris', new DateTime('2019-06-01 10:23:00')],
            [$paris, 'Monsieur Croque', 'not-happy@example.com', 'Meh, the speakers didn\'t shine', new DateTime('2019-06-01 15:07:00')],
            [$paris, 'Dude', 'happy@example.com', 'Simplement le meilleur', new DateTime('2019-08-12 09:11:00')],
            [$amsterdam, 'Mikey', 'mikey@new-signal.com', 'Didn\'t make it past the coffee shop', new DateTime('2020-09-21 15:54:00')],
        ];
        foreach ($comments as $comment) {
            $entity = new Comment();
            $entity->setConference($comment[0])->setAuthor($comment[1])
                ->setEmail($comment[2])->setText($comment[3])
                ->setCreatedAt($comment[4])->setPhotoFilename('327a45496dd9.jpeg');
            $manager->persist($entity);
        }

        $admin = new Admin();
        $admin->setRoles(['ROLE_ADMIN'])->setUsername('admin')
            ->setPassword(
                $this->encoderFactory->getEncoder(Admin::class)->encodePassword('Password123', null)
            );
        $manager->persist($admin);

        $manager->flush();
    }
}
