<?php

namespace App\Controller;

use App\Entity\Company;
use App\Entity\Professional;
use Doctrine\ORM\EntityManager;
use Doctrine\DBAL\Types\StringType;
use App\Repository\CompanyRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ProfessionalRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Serializer\Serializer;

class ProfessionalController extends AbstractController
{
    #[Route('/professional', name: 'app_professional')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new test controller!',
            'path' => 'src/Controller/ProfessionalController.php',
        ]);
    }

    #[Route('/api/professionals', name: 'professional.getAll', methods:['GET'])]
    public function getAllProfessionals(
        ProfessionalRepository $repository,
        SerializerInterface $serializer,
        Request $request
    ) : JsonResponse
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 5);
        $limit = $limit > 20 ? 20 : $limit;
        $professionals = $repository->findWithPagination($page, $limit);
        $jsonProfessionals = $serializer->serialize($professionals, 'json');
        return new JsonResponse($jsonProfessionals, Response::HTTP_OK, [], true);
    }

    #[Route('/api/professionals/{idProfessional}', name: 'professional.get', methods: ['GET'])]
    #[ParamConverter("professional", options: ['id' => 'idProfessional'], class:'App\Entity\Professional')]
    public function getProfessionals(
        Professional $professional,
        SerializerInterface $serializer
    ) : JsonResponse
    {
        return new JsonResponse($serializer->serialize($professional, 'json'), Response::HTTP_OK, ['accept' => 'json'], true);
    }

    #[Route('/api/professionals/{idProfessional}', name: 'professional.delete', methods: ['METHODE'])]
    #[ParamConverter("professional", options: ['id' => 'idProfessional'], class: 'App\Entity\Professional')]
    #[IsGranted('ROLE_ADMIN', message: "Hanhanhan vous n'avez pas dit le mot magiqueuuuh")]
    public function deleteProfesional(
        Professional $professional,
        EntityManagerInterface $entityManager
    ) : JsonResponse
    {
        $entityManager->remove($professional);
        $entityManager->flush();
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }

    
    #[Route('/api/professionals', name: 'professional.create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: "Hanhanhan vous n'avez pas dit le mot magiqueuuuh")]
    public function createProfessional
    (
        Request $request,
        EntityManagerInterface $entityManager,
        CompanyRepository $companyRepository,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        UrlGeneratorInterface $urlGenerator
    ) : JsonResponse
    {
        $professional = $serializer->deserialize($request->getContent(), Professional::class, 'json');
        $professional->setStatus('on');

        $content = $request->toArray();
        $idCompany = $content["companyJobId"];

        $professional->setCompanyJobId($idCompany);

        $errors = $validator->validate($professional);
        if($errors->count() > 0)
        {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        };

        $entityManager->persist($professional);
        $entityManager->flush();

        $location = $urlGenerator->generate('professional.get', ["idProfessional" => $professional->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        $jsonProfessional = $serializer->serialize($professional, 'json', ['getProfessional']);
        return new JsonResponse($jsonProfessional, JsonResponse::HTTP_CREATED, ["Location" => $location], true);
    }

    #[Route('/api/professionals/{idProfessional}', name: 'professional.update', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: "Hanhanhan vous n'avez pas dit le mot magiqueuuuh")]
    public function updateProfessional
    (
        Professional $professional,
        Request $request,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        UrlGeneratorInterface $urlGenerator
    ) : JsonResponse
    {
        $professional = $serializer->deserialize($request->getContent(), Professional::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $professional]);
        $professional->setStatus('on');

        $entityManager->persist($professional);
        $entityManager->flush();

        $location = $urlGenerator->generate("professional.get", ["idProfessional" => $professional->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        $jsonProfessional = $serializer->serialize($professional, "json", ["getProfessional"]);
        return new JsonResponse($jsonProfessional, JsonResponse::HTTP_CREATED, ["Location" => $location], true);
    }


    #[Route('/api/professionals/note/{idProfessional}', name: 'professional.addNote', methods: ['POST'])]
    #[ParamConverter("professional", options: ['id' => 'idProfessional'], class:'App\Entity\Professional')]
    public function addNoteProfessionals(
        Request $request,
        Professional $professional,
        EntityManagerInterface $entityManager,
        UrlGeneratorInterface $urlGenerator,
        SerializerInterface $serializer
    ) : JsonResponse
    {
        # récupération de la note, argument
        $newNote = $request->get('note', 1);

        # calcul de la nouvelle moyenne
        # $newNoteAvg = (Nombre de note * note moyenne + $newNote)/(nombre de note +1)
        $newNoteAvg = ($professional->getNoteCount()*$professional->getNoteAvg()+$newNote)/($professional->getNoteCount()+1);
        $professional->setNoteAvg($newNoteAvg);


        # incrémentation de la variable NoteCount car une note est rajouté
        $professional->setNoteCount($professional->getNoteCount()+1);


        # persist + flush pour mettre à jour la table
        $entityManager->persist($professional);
        $entityManager->flush();

        $location = $urlGenerator->generate('professional.get', ["idProfessional" => $professional->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        $jsonProfessional = $serializer->serialize($professional, 'json', ['getProfessional']);
        return new JsonResponse($jsonProfessional, JsonResponse::HTTP_CREATED, ["Location" => $location], true);
    }
    
    // Récupère la note du professionnel.
    #[Route('/api/professionals/note/{idProfessional}', name: 'professional.getNote', methods: ['GET'])]
    #[ParamConverter("professional", options: ['id' => 'idProfessional'], class:'App\Entity\Professional')]
    public function getNoteProfessionals(
        Professional $professional,
        SerializerInterface $serializer
    ) : JsonResponse
    {
        //Récupération de la note moyenne
        $note = $professional->getNoteAvg();
        $noteCount = $professional->getNoteCount();

        return new JsonResponse($serializer->serialize([$note, $noteCount], 'json'), Response::HTTP_OK, ['accept' => 'json'], true);
    }
    
    // Récupère la liste des professionnels de l'entreprise classé par note.
    #[Route('/api/professionals/company/{idCompany}', name: 'professional.getByCompany', methods: ['GET'])]
    public function getProfessionalsByCompany(
        ProfessionalRepository $professionalRepository,
        SerializerInterface $serializer,
        Int $idCompany
    ) : JsonResponse
    {
        $professionals = $professionalRepository->findBy(['company_job_id' => $idCompany], ['noteAvg' => 'DESC']);
        return new JsonResponse($serializer->serialize($professionals, 'json'), Response::HTTP_OK, ['accept' => 'json'], true);
    }
    
    // Récupère la liste des professionnels exercant un job classé par note.
    #[Route('/api/professionals/job/{job}', name: 'professional.getByJob', methods: ['GET'])]
    public function getProfessionalsByJob(
        ProfessionalRepository $professionalRepository,
        SerializerInterface $serializer,
        String $job
    ) : JsonResponse
    {
        $job = str_replace("_", " ", $job);
        $professionals = $professionalRepository->findBy(['job' => $job], ['noteAvg' => 'DESC']);
        return new JsonResponse($serializer->serialize($professionals, 'json'), Response::HTTP_OK, ['accept' => 'json'], true);
    }
}
