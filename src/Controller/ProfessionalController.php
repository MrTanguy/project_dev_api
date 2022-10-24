<?php

namespace App\Controller;

use App\Entity\Professional;
use App\Repository\CompanyRepository;
use App\Repository\ProfessionalRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

class ProfessionalController extends AbstractController
{
    #[Route('/professional', name: 'app_professional')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/ProfessionalController.php',
        ]);
    }

    #[Route('/api/professionals', name: 'professional.getAll', methods:['GET'])]
    public function getAllProfessionals(
        ProfessionalRepository $repository,
        SerializerInterface $serializer
    ) : JsonResponse
    {
        $professionals = $repository->findAll();
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
    #[ParamConverter("professional", options: ['id' => 'idProffesional'], class: 'App\Entity\Professional')]
    public function deleteProfesional
    (
        Professional $professional,
        EntityManagerInterface $entityManager
    ) : JsonResponse
    {
        $entityManager->remove($professional);
        $entityManager->flush();
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }

    #[Route('/api/professionals', name: 'professional.create', methods: ['POST'])]
    public function createProfessional
    (
        Request $request,
        EntityManagerInterface $entityManager,
        CompanyRepository $companyRepository,
        SerializerInterface $serializer,
        UrlGeneratorInterface $urlGenerator
    ) : JsonResponse
    {
        $professional = $serializer->deserialize($request->getContent(), Professional::class, 'json');
        $professional->setStatus('on');

        $content = $request->toArray();
        # DOUTE ICI
        $idCompany = $content["company_job_id"];

        $professional->setCompanyJobId($companyRepository->find($idCompany));

        $entityManager->persist($professional);
        $entityManager->flush();

        $location = $urlGenerator->generate('professionals.get', ["idProfessional" => $professional->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        $jsonProfessional = $serializer->serialize($professional, 'json', ['getProfessional']);
        return new JsonResponse($jsonProfessional, JsonResponse::HTTP_CREATED, ["Location" => $location], true);
    }

    #[Route('/api/professionals/{idProfessional}', name: 'professional.update', methods: ['PUT'])]
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

        $location = $urlGenerator->generate("professionals.get", ["idProfessional" => $professional->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        $jsonProfessional = $serializer->serialize($professional, "json", ["getProfessional"]);
        return new JsonResponse($jsonProfessional, JsonResponse::HTTP_CREATED, ["Location" => $location], true);
    }
}
