<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ConferenceController extends AbstractController
{
    /**
     * @Route("/", name="homepage")
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        $greeting = '';
        if ($name = $request->query->get('hello')) {
            $name = htmlspecialchars($name, ENT_COMPAT);
            $greeting = "<h1>Hello {$name}!</h1>";
        }
        return new Response(<<<EOF
<html>
    <body>
        {$greeting}
        <img src="/images/under-construction.gif" />
    </body>
</html>
EOF
        );
    }
}
