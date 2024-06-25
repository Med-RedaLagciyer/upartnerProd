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
use App\Entity\User;


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

        $query = "SELECT id, nom, prenom, societe, adresse, pays, ville, tel1, tel2, mail1, mail2, contact1, contact2, ice_o FROM `u_p_partenaire` WHERE ice_o = '" . $this->getUser()->getUsername() . "'";
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

    #[Route('/await', name: 'app_fournisseur_await')]
    public function await(Security $security, ManagerRegistry $doctrine): Response
    {
        return $this->render('fournisseur/await.html.twig', [
        ]);
    }

    #[Route('/valider', name: 'app_fournisseur_valider')]
    public function ajouter(Request $request, ManagerRegistry $doctrine): Response
    {

        // dd($request);
        if ($request->get("tel1") && $request->get("mail1") && $request->get("ville") && $request->get("contact1")) {
            // if (strlen($request->get('ice')) !== 15) {
            //     return new JsonResponse('ICE DOIT AVOIR PLUS DE 15 CARACTÈRES', 500);
            // }
            $partenaire = $this->em->getRepository(PartenaireValide::class)->findby(["partenaireId" => $request->get("idfrs")]);
            // dd(!$partenaire);
            $user = $this->em->getRepository(User::class)->find($this->getUser());

            // dd($partenaire);
            if (!$partenaire) {
                $partenaire = new PartenaireValide();


                $partenaire->setPartenaireId(intval($request->get("idfrs")));
                if($request->get("nom"))$partenaire->setNom($request->get("nom"));
                if($request->get("prenom"))$partenaire->setPrenom($request->get("prenom"));
                if($request->get("tel1"))$partenaire->setTel1($request->get("tel1"));
                if($request->get("contact1"))$partenaire->setContact1($request->get("contact1"));
                if($request->get("tel2"))$partenaire->setTel2($request->get("tel2"));
                if($request->get("contact2"))$partenaire->setContact2($request->get("contact2"));
                if($request->get("mail1"))$partenaire->setMail1($request->get("mail1"));
                if($request->get("mail2"))$partenaire->setMail2($request->get("mail2"));
                if($request->get("ville"))$partenaire->setVille($request->get("ville"));
                if($request->get("adresse"))$partenaire->setAdresse($request->get("adresse"));
                $partenaire->setUserCreated($this->getUser());
                $partenaire->setCreated(new \DateTime());

                $this->em->persist($partenaire);

                $user->setValide(1);

                $this->em->flush();
            } else {
                $PartenaireValide = $this->em->getRepository(PartenaireValide::class)->find($partenaire[0]->getId());
                if($request->get("nom"))$PartenaireValide->setNom($request->get("nom"));
                if($request->get("prenom"))$PartenaireValide->setPrenom($request->get("prenom"));
                if($request->get("tel1"))$PartenaireValide->setTel1($request->get("tel1"));
                if($request->get("contact1"))$PartenaireValide->setContact1($request->get("contact1"));
                if($request->get("tel2"))$PartenaireValide->setTel2($request->get("tel2"));
                if($request->get("contact2"))$PartenaireValide->setContact2($request->get("contact2"));
                if($request->get("mail1"))$PartenaireValide->setMail1($request->get("mail1"));
                if($request->get("mail2"))$PartenaireValide->setMail2($request->get("mail2"));
                if($request->get("ville"))$PartenaireValide->setVille($request->get("ville"));
                if($request->get("adresse"))$PartenaireValide->setAdresse($request->get("adresse"));
                $PartenaireValide->setUserUpdated($this->getUser());
                $PartenaireValide->setUpdated(new \DateTime());
                $user->setValide(1);
                $this->em->flush();
            }

            // dd("done");
            return new JsonResponse('Vos informations ont été mises à jour.', 200);
        } else {
            return new JsonResponse('MERCI DE REMPLIR TOUS LES CHAMPS', 500);
        }
    }
}
