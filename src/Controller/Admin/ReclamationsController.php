<?php

namespace App\Controller\Admin;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Controller\DatatablesController;


#[Route('/admin/reclamations')]
class ReclamationsController extends AbstractController
{

    private $em;
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->em = $doctrine->getManager();
    }

    #[Route('/', name: 'app_admin_reclamations')]
    public function index(): Response
    {
        return $this->render('admin/reclamations/index.html.twig');
    }

    
}
