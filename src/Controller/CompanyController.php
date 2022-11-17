<?php

namespace App\Controller;

use App\Entity\Company;
use JMS\Serializer\Serializer;
use App\Repository\CompanyRepository;
use JMS\Serializer\SerializerInterface;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializationContext;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Company")
 */
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
        SerializerInterface $serializer,
        Request $request,
        TagAwareCacheInterface $cache
    ) : JsonResponse
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 5);
        $limit = $limit > 20 ? 20 : $limit;

        $idCache = 'getAllCompanies';
        $jsonProfessionals = $cache->get($idCache, function (ItemInterface $item) use ($repository, $serializer, $page, $limit) {
            echo "MISE EN CACHE";
            $item->tag("companiesCache");
            $companies = $repository->findWithPagination($page, $limit);
            $context = SerializationContext::create()->setGroups(['getAllCompanies']);
            return $serializer->serialize($companies, 'json', $context);
        });
       
        return new JsonResponse($jsonProfessionals, Response::HTTP_OK, [], true);
    }

    #[Route('/api/companies/near', name: 'company.getNearest', methods: ['GET'])]
    public function getNearestCompanies(
        Request $request,
        SerializerInterface $serializer,
        companyRepository $companyRepository,
        TagAwareCacheInterface $cache
    ) : JsonResponse
    {
        $lat = $request->get('lat');
        $lon = $request->get('lon');
        if (empty($lat) || empty($lon)) {
            return new JsonResponse("Vous devez renseigner une latitude (lat) et une longitude (lon).", Response::HTTP_BAD_REQUEST);
        }
        if (!is_numeric($lat) || !is_numeric($lon) || $lat < -90 || $lat > 90 || $lon < -180 || $lon > 180) {
            return new JsonResponse("La latitude doit être une valeur numérique comprise entre -90 et 90 et la longitude doit être une valeur numérique comprise entre -180 et 180.", Response::HTTP_BAD_REQUEST);
        }
        $job = $request->get('job');
        $limit = intval($request->get('limit', 5));

        $companies = $companyRepository->findNearestCompanyByJob($lat, $lon, $job, $limit);
        $context = SerializationContext::create()->setGroups(['getCompany']);
        $jsonNearestCompanies = $serializer->serialize($companies, 'json', $context);

        return new JsonResponse($jsonNearestCompanies, Response::HTTP_OK, ['accept' => 'json'], true);
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

    #[Route('/api/companies/{idCompany}', name: 'company.delete', methods: ['DELETE'])]
    #[ParamConverter("company", options: ['id' => 'idCompany'], class: 'App\Entity\Company')]
    public function deleteCompany(
        Company $company,
        EntityManagerInterface $entityManager,
        TagAwareCacheInterface $cache
    ) : JsonResponse
    {
        $cache->invalidateTags(["companiesCache", "nearestCompaniesCache"]);

        $entityManager->remove($company);
        $entityManager->flush();
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
    
    #[Route('/api/companies', name: 'company.create', methods: ['POST'])]
    public function createCompany(
        Request $request,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        UrlGeneratorInterface $urlGenerator,
        TagAwareCacheInterface $cache
    ) : JsonResponse
    {
        $cache->invalidateTags(["companiesCache", "nearestCompaniesCache"]);

        $company = $serializer->deserialize($request->getContent(), Company::class, 'json');
        $company->setStatus('on');
        $company->setNoteCount(0);


        $entityManager->persist($company);
        $entityManager->flush();

        $location = $urlGenerator->generate('company.get', ["idCompany" => $company->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        $context = SerializationContext::create()->setGroups(['getCompany']);
        $jsonCompany = $serializer->serialize($company, 'json', $context);
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

        return new JsonResponse($serializer->serialize($note, 'json'), Response::HTTP_OK, ['accept' => 'json'], true);
    }
}
