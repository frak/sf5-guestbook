<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Conference;
use App\Form\CommentFormType;
use App\Message\CommentMessage;
use App\Repository\CommentRepository;
use App\Repository\ConferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class ConferenceController extends AbstractController
{
    /** @var Environment */
    private $twig;
    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var MessageBusInterface */
    private $bus;

    /**
     * ConferenceController constructor.
     *
     * @param Environment            $twig
     * @param EntityManagerInterface $entityManager
     * @param MessageBusInterface    $bus
     */
    public function __construct(Environment $twig, EntityManagerInterface $entityManager, MessageBusInterface $bus)
    {
        $this->twig = $twig;
        $this->entityManager = $entityManager;
        $this->bus = $bus;
    }


    /**
     * @Route("/", name="homepage")
     * @return Response
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function index(ConferenceRepository $conferenceRepository): Response
    {
        $response = new Response(
            $this->twig->render('conference/index.html.twig', ['conferences' => $conferenceRepository->findAll()])
        );
        $response->setSharedMaxAge(3600);

        return $response;
    }

    /**
     * @Route("/conference/{slug}", name="conference")
     * @param Request           $request
     * @param Conference        $conference
     * @param CommentRepository $commentRepository
     * @param string            $photoDir
     * @return Response
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function show(
        Request $request,
        Conference $conference,
        CommentRepository $commentRepository,
        NotifierInterface $notifier,
        string $photoDir
    ): Response {
        $comment = new Comment();
        $form = $this->createForm(CommentFormType::class, $comment);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setConference($conference);
            /** @var UploadedFile $photo */
            if ($photo = $form['photo']->getData()) {
                $filename = bin2hex(random_bytes(6)).'.'.$photo->guessExtension();
                $photo->move($photoDir, $filename);
                $comment->setPhotoFilename($filename);
            }
            $this->entityManager->persist($comment);
            $this->entityManager->flush();

            $context = [
                'user_ip' => $request->getClientIp(),
                'user_agent' => $request->headers->get('user-agent'),
                'referrer' => $request->headers->get('referer'),
                'permalink' => $request->getUri(),
            ];
            $reviewUrl = $this->generateUrl(
                'review_comment',
                ['id' => $comment->getId()],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
            $this->bus->dispatch(new CommentMessage($comment->getId(), $reviewUrl, $context));

            $notifier->send(
                new Notification(
                    'Thank you for the feedback; your comment will be posted after moderation.',
                    ['browser']
                )
            );

            return $this->redirectToRoute(
                'conference',
                ['slug' => $conference->getSlug()]
            );
        }

        if ($form->isSubmitted()) {
            $notifier->send(
                new Notification(
                    'Can you please check your submission? There are some problems with it.',
                    ['browser']
                )
            );
        }

        $offset = max(0, $request->query->getInt('offset', 0));
        $paginator = $commentRepository->getCommentPaginator($conference, $offset);
        $previous = $offset - CommentRepository::PAGINATOR_PER_PAGE;
        $next = min(count($paginator), $offset + CommentRepository::PAGINATOR_PER_PAGE);

        return new Response(
            $this->twig->render(
                'conference/show.html.twig',
                [
                    'conference' => $conference,
                    'comments' => $paginator,
                    'previous' => $previous,
                    'next' => $next,
                    'comment_form' => $form->createView(),
                ]
            )
        );
    }

    /**
     * @Route("/conference_header", name="conference_header")
     * @param ConferenceRepository $conferenceRepository
     * @return Response
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function conferenceHeader(ConferenceRepository $conferenceRepository)
    {
        $response = new Response(
            $this->twig->render('conference/header.html.twig', ['conferences' => $conferenceRepository->findAll()])
        );
        $response->setSharedMaxAge(3600);

        return $response;
    }
}
