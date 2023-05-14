<?php

namespace App\Controller\Fournisseur;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class facturesController extends AbstractController
{
    #[Route('/fournisseur/factures', name: 'app_fournisseur_factures')]
    public function index(): Response
    {
        return $this->render('fournisseur/factures/index.html.twig', [
            'controller_name' => 'facturesController',
        ]);
    }
}
