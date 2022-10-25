<?php

namespace App\Controller;

use App\Entity\Company;
use App\Repository\CompanyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;


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
    /*
    #[Route('/api/companies', name: 'company.create', methods: ['POST'])]
    public function createCompany
    (
        Request $request,
        EntityManagerInterface $entityManager,
        ProfessionalRepository $companyRepository,
        SerializerInterface $serializer,
        UrlGeneratorInterface $urlGenerator
    ) : JsonResponse
    {
        $company = $serializer->deserialize($request->getContent(), Company::class, 'json');
        $company->setStatus('on');

        $content = $request->toArray();
        # DOUTE ICI
        $idProfessional = $content["id"];

        $company->set
        $professional->setCompanyJobId($companyRepository->find($idCompany));

        $entityManager->persist($professional);
        $entityManager->flush();

        $location = $urlGenerator->generate('professionals.get', ["idProfessional" => $professional->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        $jsonProfessional = $serializer->serialize($professional, 'json', ['getProfessional']);
        return new JsonResponse($jsonProfessional, JsonResponse::HTTP_CREATED, ["Location" => $location], true);
    }
    */
}
