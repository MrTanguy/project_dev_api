<?php

namespace App\Controller;

use App\Entity\Company;
use App\Repository\CompanyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


class CompanyController extends AbstractController
{
    #[Route('/company', name: 'app_company')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/CompanyController.php',
        ]);
    }

    #[Route('/api/companies', name: 'company.getAll', methods:['GET'])]
    public function getAllCompanies(
        CompanyRepository $repository,
        SerializerInterface $serializer
    ) : JsonResponse
    {
        $companies = $repository->findAll();
        $jsonCompanies = $serializer->serialize($companies, 'json');
        return new JsonResponse($jsonCompanies, Response::HTTP_OK, [], true);
    }

    #[Route('/api/companies/{idCompany}', name: 'company.get', methods: ['GET'])]
    #[ParamConverter("company", options: ['id' => 'idCompany'], class:'App\Entity\Company')]
    public function getCompanies(
        Company $company,
        SerializerInterface $serializer
    ) : JsonResponse
    {
        return new JsonResponse($serializer->serialize($company, 'json'), Response::HTTP_OK, ['accept' => 'json'], true);
    }

    #[Route('/api/companies/{idCompany}', name: 'company.delete', methods: ['METHODE'])]
    #[ParamConverter("company", options: ['id' => 'idCompany'], class: 'App\Entity\Company')]
    public function deleteCompany
    (
        Company $company,
        EntityManagerInterface $entityManager
    ) : JsonResponse
    {
        $entityManager->remove($company);
        $entityManager->flush();
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
    
    #[Route('/api/companies', name: 'company.create', methods: ['POST'])]
    public function createCompany(
        Request $request,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        UrlGeneratorInterface $urlGenerator
    ) : JsonResponse
    {
        $company = $serializer->deserialize($request->getContent(), Company::class, 'json');
        $company->setStatus('on');
        $company->setNoteCount(0);


        $entityManager->persist($company);
        $entityManager->flush();

        $location = $urlGenerator->generate('company.get', ["idCompany" => $company->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        $jsonCompany = $serializer->serialize($company, 'json', ['getCompany']);
        return new JsonResponse($jsonCompany, JsonResponse::HTTP_CREATED, ["Location" => $location], true);
    }

    // Récupère la note de l'entreprise.
    #[Route('/api/companies/note/{idCompany}', name: ' company.getNote', methods: ['GET'])]
    #[ParamConverter("company", options: ['id' => 'idCompany'], class:'App\Entity\Company')]
    public function getNoteProfessionals(
        Company $company,
        SerializerInterface $serializer
    ) : JsonResponse
    {
        //Récupération de la note moyenne
        $note = $company->getNoteAvg();
        $noteCount = $company->getNoteCount();

        return new JsonResponse($serializer->serialize([$note, $noteCount], 'json'), Response::HTTP_OK, ['accept' => 'json'], true);
    }
    
}
