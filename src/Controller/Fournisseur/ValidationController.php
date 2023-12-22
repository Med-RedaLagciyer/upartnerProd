<?php

namespace App\Controller\Fournisseur;

use App\Entity\PartenaireValide;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


#[Route('/fournisseur/validation')]
class ValidationController extends AbstractController
{

    private $em;
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->em = $doctrine->getManager();
    }

    #[Route('/', name: 'app_fournisseur_validation')]
    public function index(Security $security, ManagerRegistry $doctrine): Response
    {
        $entityManager = $doctrine->getManager('default')->getConnection();

        $query = "SELECT id, nom, prenom, societe, adresse, pays, ville, tel1, tel2, mail1, ice FROM `u_p_partenaire` WHERE ice_o = '" . $this->getUser()->getUsername() . "'";
        $statement = $entityManager->prepare($query);
        $result = $statement->executeQuery();
        $infos = $result->fetchAll();
        // dd($infos);

        if (!$infos) {
            return new JsonResponse("COMPTE PAS ENCORE CRÉE", 500);
        }

        return $this->render('fournisseur/valider.html.twig', [
            'infos' => $infos
        ]);
    }

    #[Route('/valider', name: 'app_fournisseur_valider')]
    public function ajouter(Request $request, ManagerRegistry $doctrine): Response
    {

        if ($request->get("societe") && $request->get("ice") && $request->get("nom") && $request->get("prenom") && $request->get("tel1") && $request->get("tel2") && $request->get("contact1") && $request->get("contact2") && $request->get("mail1") && $request->get("pays") && $request->get("ville") && $request->get("adresse")) {
            if (strlen($request->get('ice')) !== 15) {
                return new JsonResponse('ICE DOIT AVOIR PLUS DE 15 CARACTÈRES', 500);
            }
            $partenaire = $this->em->getRepository(PartenaireValide::class)->findby(["partenaireId" => $request->get("idfrs")]);
            // dd(!$partenaire);

            if (!$partenaire) {
                $partenaire = new PartenaireValide();

                // dd($partenaire);

                $partenaire->setPartenaireId(intval($request->get("idfrs")));
                $partenaire->setSociete($request->get("societe"));
                $partenaire->setICE($request->get("ice"));
                $partenaire->setNom($request->get("nom"));
                $partenaire->setPrenom($request->get("prenom"));
                $partenaire->setTel1($request->get("tel1"));
                $partenaire->setContact1($request->get("contact1"));
                $partenaire->setTel2($request->get("tel2"));
                $partenaire->setContact2($request->get("contact2"));
                $partenaire->setMail1($request->get("mail1"));
                $partenaire->setPays($request->get("pays"));
                $partenaire->setVille($request->get("ville"));
                $partenaire->setAdresse($request->get("adresse"));
                $partenaire->setUserCreated($this->getUser());
                $partenaire->setCreated(new \DateTime());

                $this->em->persist($partenaire);

                $this->em->flush();
            } else {
                $PartenaireValide = $this->em->getRepository(PartenaireValide::class)->find($partenaire[0]->getId());
                // dd($PartenaireValide);
                $PartenaireValide->setPartenaireId(intval($request->get("idfrs")));
                $PartenaireValide->setSociete($request->get("societe"));
                $PartenaireValide->setNom($request->get("nom"));
                $PartenaireValide->setPrenom($request->get("prenom"));
                $PartenaireValide->setICE($request->get("ice"));
                $PartenaireValide->setTel1($request->get("tel1"));
                $PartenaireValide->setContact1($request->get("contact1"));
                $PartenaireValide->setTel2($request->get("tel2"));
                $PartenaireValide->setContact2($request->get("contact2"));
                $PartenaireValide->setMail1($request->get("mail1"));
                $PartenaireValide->setPays($request->get("pays"));
                $PartenaireValide->setVille($request->get("ville"));
                $PartenaireValide->setAdresse($request->get("adresse"));
                $PartenaireValide->setUserUpdated($this->getUser());
                $PartenaireValide->setUpdated(new \DateTime());
                $this->em->flush();
            }


            return new JsonResponse('MISE À JOUR RÉALISÉE AVEC SUSSE', 200);
        } else {
            return new JsonResponse('MISE À JOUR RÉALISÉE AVEC SUSSE', 500);
        }
    }
}
