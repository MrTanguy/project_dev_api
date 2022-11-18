<?php

namespace App\Controller;

use App\Entity\Company;
use OpenApi\Attributes as OA;
use function PHPSTORM_META\type;
use App\Repository\CompanyRepository;
use JMS\Serializer\SerializerInterface;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializationContext;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

#[OA\Tag(name: 'Company')]
class CompanyController extends AbstractController
{
    /**
     * Return all companies sorted by id with pagination
     *
     * @param CompanyRepository $repository
     * @param SerializerInterface $serializer
     * @param Request $request
     * @param TagAwareCacheInterface $cache
     * @return JsonResponse
     */
    #[OA\Response(
        response: 200,
        description: '',
        content: new Model(type: Company::class)
    )]
    #[OA\Parameter(
        name: 'page',
        in: 'query',
        description: 'The page you want that the data come from. Exemple : 1',
        schema: new OA\Schema(type: 'int', default: 1)
    )]
    #[OA\Parameter(
        name: 'limit',
        in: 'query',
        description: 'The limit of result you want. Exemple: 5',
        schema: new OA\Schema(type: 'int', default: 5)
    )]
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

    /**
     * Return all companies, from the closest to the farest according to your localisation.
     *
     * @param CompanyRepository $repository
     * @param SerializerInterface $serializer
     * @param Request $request
     * @param TagAwareCacheInterface $cache
     * @return JsonResponse
     */
    #[OA\Response(
        response: 200,
        description: '',
        content: new Model(type: Company::class)
    )]
    #[OA\Response(
        response: 400,
        description: '"Vous devez renseigner une latitude (lat) et une longitude (lon)."'
    )]
    #[OA\Parameter(
        name: 'lat',
        in: 'query',
        description: 'Your latitude.',
        schema: new OA\Schema(type: 'float', default: 45.7465014)
    )]
    #[OA\Parameter(
        name: 'lon',
        in: 'query',
        description: 'Your longitude',
        schema: new OA\Schema(type: 'float', default: 4.8381741)
    )]
    #[OA\Parameter(
        name: 'job',
        in: 'query',
        description: 'Name of the job',
        schema: new OA\Schema(type: 'string', default: "Menuisier")
    )]
    #[OA\Parameter(
        name: 'limit',
        in: 'query',
        description: 'The limit of result you want. Exemple: 5',
        schema: new OA\Schema(type: 'int', default: 5)
    )]
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
        $job = ucfirst($request->get('job'));
        echo($job);
        $limit = intval($request->get('limit', 5));
        
        $idCache = 'getNearestCompanies';
        $jsonNearestCompanies = $cache->get($idCache, function (ItemInterface $item) use ($companyRepository, $serializer, $lat, $lon, $job, $limit) {
            echo "MISE EN CACHE";
            $item->tag("nearCompaniesCache");
            $companies = $companyRepository->findNearestCompanyByJob($lat, $lon, $job, $limit);
            $context = SerializationContext::create()->setGroups(['getCompany']);
            return $serializer->serialize($companies, 'json', $context);
        });

        return new JsonResponse($jsonNearestCompanies, Response::HTTP_OK, ['accept' => 'json'], true);
    }

    /**
     * Update a company, according to data given in the body.
     *
     * @param TagAwareCacheInterface $cache
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $entityManager
     * @param UrlGeneratorInterface $urlGenerator
     * @param Company $company
     * @param ValidatorInterface $validator
     * 
     * @return JsonResponse
     */
    #[OA\Response(
        response: 200,
        description: '',
        content: new Model(type: Company::class)
    )]
    #[OA\RequestBody(
        request: 'company.update',
        description: 'Company json object that will be used to update the database, all properties aren\'t needed, send only one used.',
        required: true,
        content: new OA\JsonContent(
            type: 'object',
            ref: '#/components/schemas/postNewUpdateCompany'
        )
    )]
    #[Route('/api/companies/{idCompany}', name: 'company.update', methods: ['PUT'])]
    #[ParamConverter("company", options: ['id' => 'idCompany'], class: 'App\Entity\Company')]
    #[IsGranted('ROLE_ADMIN', message: "You need the admin role to modify a company.")]
    public function modifyCompany(
        TagAwareCacheInterface $cache,
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $entityManager,
        UrlGeneratorInterface $urlGenerator,
        Company $company,
        ValidatorInterface $validator
    ) : JsonResponse
    {
        $cache->invalidateTags(["companiesCache"]);

        $updatedCompany = $serializer->deserialize($request->getContent(), Company::class, 'json');

        $company->setName($updatedCompany->getName() ? $updatedCompany->getName() : $company->getName());
        $company->setJob($updatedCompany->getJob() ? ucfirst($updatedCompany->getJob()) : $company->getJob());
        $company->setLat($updatedCompany->getLat() ? $updatedCompany->getLat() : $company->getLat());
        $company->setLon($updatedCompany->getLon() ? $updatedCompany->getLon() : $company->getLon());

        $company->setStatus('on');

        
        $errors = $validator->validate($company);
        if($errors->count() > 0)
        {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        };

        $entityManager->persist($company);
        $entityManager->flush();

        $location = $urlGenerator->generate("company.get", ["idCompany" => $company->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        
        $context = SerializationContext::create()->setGroups(["getCompany"]);

        $jsonCompany = $serializer->serialize($company, "json", $context);
        return new JsonResponse($jsonCompany, JsonResponse::HTTP_CREATED, ["Location" => $location], true);
    }


     /**
     * Return the company matching a specific id
     * 
     * @param ProfessionalRepository $repository
     * @param SerializerInterface $serializer
     * @return JsonResponse
     */
    #[OA\Response(
        response: 200,
        description: 'Successful response',
        content: new Model(type: Company::class)
    )]
    #[Route('/api/companies/{idCompany}', name: 'company.get', methods: ['GET'])]
    #[ParamConverter("company", options: ['id' => 'idCompany'], class:'App\Entity\Company')]
    public function getCompanies(
        Company $company,
        SerializerInterface $serializer
    ) : JsonResponse
    {
        return new JsonResponse($serializer->serialize($company, 'json'), Response::HTTP_OK, ['accept' => 'json'], true);
    }


    /**
     * Delete the company matching the id in query
     * 
     * @param Company $company
     * @param EntityManagerInterface $entityManager
     * @param TagAwareCacheInterface $cache
     * 
     * @return JsonResponse
     */
    #[OA\Response(
        response: 200,
        description: '',
        content: new Model(type: Company::class)
    )]
    #[Route('/api/companies/{idCompany}', name: 'company.delete', methods: ['DELETE'])]
    #[ParamConverter("company", options: ['id' => 'idCompany'], class: 'App\Entity\Company')]
    #[IsGranted('ROLE_ADMIN', message: "You need the admin role to modify a company.")]
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
    


    /**
     * Create a new company, needs a json as parameter.
     *
     * @param TagAwareCacheInterface $cache
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $entityManager
     * @param UrlGeneratorInterface $urlGenerator
     * @param Company $company
     * @param ValidatorInterface $validator
     * 
     * @return JsonResponse
     */
    #[OA\Response(
        response: 200,
        description: '',
        content: new Model(type: Company::class)
    )]
    #[OA\RequestBody(
        request: 'company.update',
        description: 'Company json object that will be used to update the database, all properties aren\'t needed, send only one used.',
        required: true,
        content: new OA\JsonContent(
            type: 'object',
            ref: '#/components/schemas/postNewUpdateCompany'
        )
    )]
    #[Route('/api/companies', name: 'company.create', methods: ['POST'])]
    public function createCompany(
        Request $request,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        UrlGeneratorInterface $urlGenerator,
        TagAwareCacheInterface $cache,
        ValidatorInterface $validator
    ) : JsonResponse
    {
        $cache->invalidateTags(["companiesCache", "nearestCompaniesCache"]);

        $company = $serializer->deserialize($request->getContent(), Company::class, 'json');
        $company->setStatus('on');
        $job = $company->getJob();
        $company->setJob(ucfirst($job));

        $errors = $validator->validate($company);
        if($errors->count() > 0)
        {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        };

        $entityManager->persist($company);
        $entityManager->flush();

        $location = $urlGenerator->generate('company.get', ["idCompany" => $company->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        $context = SerializationContext::create()->setGroups(['getCompany']);
        $jsonCompany = $serializer->serialize($company, 'json', $context);
        return new JsonResponse($jsonCompany, JsonResponse::HTTP_CREATED, ["Location" => $location], true);
    }
}
