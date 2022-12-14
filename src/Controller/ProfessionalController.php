<?php

namespace App\Controller;

use App\Entity\Professional;
use App\Repository\ProfessionalRepository;
use App\Repository\CompanyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use JMS\Serializer\SerializerInterface;
use JMS\Serializer\SerializationContext;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;


#[OA\Tag(name: 'Professional')]
class ProfessionalController extends AbstractController
{

    /**
     * Return all professionals sorted by id with pagination
     *
     * @param ProfessionalRepository $repository
     * @param SerializerInterface $serializer
     * @param Request $request
     * @param TagAwareCacheInterface $cache
     * @return JsonResponse
     */
    #[OA\Response(
        response: 200,
        description: '',
        content: new Model(type: Professional::class)
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
    #[Route('/api/professionals', name: 'professional.getAll', methods:['GET'])]
    public function getAllProfessionals(
        ProfessionalRepository $repository,
        SerializerInterface $serializer,
        Request $request,
        TagAwareCacheInterface $cache
    ) : JsonResponse
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 5);
        $limit = $limit > 20 ? 20 : $limit;

        $idCache = 'getAllProfessionals';
        $jsonProfessionals = $cache->get($idCache, function (ItemInterface $item) use ($repository, $serializer, $page, $limit) {
            echo "MISE EN CACHE";
            $item->tag("professionalCache");
            $professionals = $repository->findWithPagination($page, $limit);
            $context = SerializationContext::create()->setGroups(['getAllProfessionals']);
            return $serializer->serialize($professionals, 'json', $context);
        });
       
        return new JsonResponse($jsonProfessionals, Response::HTTP_OK, [], true);
    }



    /**
     * Return the professional matching a specific id
     * 
     * @param ProfessionalRepository $repository
     * @param SerializerInterface $serializer
     * @return JsonResponse
     */
    #[OA\Response(
        response: 200,
        description: 'Successful response',
        content: new Model(type: Professional::class)
    )]
    #[Route('/api/professionals/{idProfessional}', name: 'professional.get', methods: ['GET'])]
    #[ParamConverter("professional", options: ['id' => 'idProfessional'], class:'App\Entity\Professional')]
    public function getProfessionals(
        Professional $professional,
        SerializerInterface $serializer
    ) : JsonResponse
    {
        $context = SerializationContext::create()->setGroups(["getProfessionals"]);
        $jsonProfessional = $serializer->serialize($professional, 'json', $context);
        return new JsonResponse($jsonProfessional, Response::HTTP_OK, ['accept' => 'json'], true);
    }



    /**
     * Delete the professional matching a specific id
     * 
     * @param ProfessionalRepository $repository
     * @param EntityManagerInterface $entityManager
     * @param TagAwareCacheInterface $cache
     * @return JsonResponse
     */
    #[OA\Response(
        response: 200,
        description: 'The professional have been succesfully deleted'
    )]
    #[Route('/api/professionals/{idProfessional}', name: 'professional.delete', methods: ['DELETE'])]
    #[ParamConverter("professional", options: ['id' => 'idProfessional'], class: 'App\Entity\Professional')]
    #[IsGranted('ROLE_ADMIN', message: "Admin rights needed.")]
    public function deleteProfesional(
        Professional $professional,
        EntityManagerInterface $entityManager,
        TagAwareCacheInterface $cache
    ) : JsonResponse
    {
        $cache->invalidateTags(["professionalCache"]);
        $entityManager->remove($professional);
        $entityManager->flush();
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }

    

    /**
     * Create a new professional with a json file given as a body parameter
     * 
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param SerializerInterface $serializer
     * @param ValidatorInterface $validator
     * @param UrlGeneratorInterface $urlGenerator
     * @param TagAwareCacheInterface $cache
     * @return JsonResponse
     */
    #[OA\Response(
        response: 200,
        description: '',
        content: new Model(type: Professional::class)
    )]
    #[OA\RequestBody(
        request: 'professional.create',
        description: 'Professional json object that will be used to create a new professional in the database, all properties are needed',
        required: true,
        content: new OA\JsonContent(
            type: 'object',
            ref: '#/components/schemas/postNewUpdateProfessional'
        )
    )]
    #[Route('/api/professionals', name: 'professional.create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: "You need the admin role to update a professional")]
    public function createProfessional(
        Request $request,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        UrlGeneratorInterface $urlGenerator,
        TagAwareCacheInterface $cache
    ) : JsonResponse
    {
        $cache->invalidateTags(["professionalCache"]);

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
        $context = SerializationContext::create()->setGroups(["getProfessionals"]);
        $jsonProfessional = $serializer->serialize($professional, "json", $context);
        return new JsonResponse($jsonProfessional, JsonResponse::HTTP_CREATED, ["Location" => $location], true);
    }

    

    /**
     * Update the professional (id) according to the json file given as parameter
     *
     * @param Professional $professional
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param SerializerInterface $serializer
     * @param UrlGeneratorInterface $urlGenerator
     * @param TagAwareCacheInterface $cache
     * @return JsonResponse
     */  
    #[OA\Response(
        response: 200,
        description: 'Update the professional (id) according to the json file given as parameter',
        content: new Model(type : Professional::class)
    )]
    #[OA\Response(
        response: 500,
        description: '"Could not decode JSON, syntax error - malformed JSON."'
    )]
    #[OA\RequestBody(
        request: 'professional.update',
        description: 'Professional json object that will be used to update the database, all properties aren\'t needed, send only one used.',
        required: true,
        content: new OA\JsonContent(
            type: 'object',
            ref: '#/components/schemas/postNewUpdateProfessional'
        )
    )]
    #[Route('/api/professionals/{idProfessional}', name: 'professional.update', methods: ['PUT'])]
    #[ParamConverter("professional", options: ['id' => 'idProfessional'], class: 'App\Entity\Professional')]
    #[IsGranted('ROLE_ADMIN', message: "You need the admin role to modify a professional")]
    public function updateProfessional(
        Professional $professional,
        Request $request,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        UrlGeneratorInterface $urlGenerator,
        TagAwareCacheInterface $cache,
        ValidatorInterface $validator
    ) : JsonResponse
    {
        $cache->invalidateTags(["professionalCache"]);

        $updatedProfessional = $serializer->deserialize($request->getContent(), Professional::class, 'json');

        
        $professional->setFirstname($updatedProfessional->getFirstname() ? $updatedProfessional->getFirstname() : $professional->getFirstname());
        $professional->setLastname($updatedProfessional->getLastname() ? $updatedProfessional->getLastname() : $professional->getLastname());
        $professional->setJob($updatedProfessional->getJob() ? $updatedProfessional->getJob() : $professional->getJob());
        $professional->setCompanyJobId($updatedProfessional->getCompanyJobId() ? $updatedProfessional->getCompanyJobId() : $professional->getCompanyJobId());
        $professional->setNoteCount($updatedProfessional->getNoteCount() ? $updatedProfessional->getNoteCount() : $professional->getNoteCount());
        $professional->setNoteAvg($updatedProfessional->getNoteAvg() ? $updatedProfessional->getNoteAvg() : $professional->getNoteAvg());
        
        $professional->setStatus('on');
        
        $errors = $validator->validate($professional);
        if($errors->count() > 0)
        {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        };

        $entityManager->persist($professional);
        $entityManager->flush();
        
        $location = $urlGenerator->generate("professional.get", ["idProfessional" => $professional->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        
        $context = SerializationContext::create()->setGroups(["getProfessionals"]);

        $jsonProfessional = $serializer->serialize($professional, "json", $context);
        return new JsonResponse($jsonProfessional, JsonResponse::HTTP_CREATED, ["Location" => $location], true);
    }



    /**
     * Add a new note to the professional
     *
     * @param Request $request
     * @param Professional $professional
     * @param EntityManagerInterface $entityManager
     * @param UrlGeneratorInterface $urlGenerator
     * @param SerializerInterface $serializer
     * @param ProfessionalRepository $professionalRepository
     * @param CompanyRepository $companyRepository
     * @param TagAwareCacheInterface $cache
     * @return JsonResponse
     */ 
    #[OA\Response(
        response: 200,
        description: 'Add a new note to NoteCount, update Note',
        content: new Model(type : Professional::class)
    )]
    #[OA\RequestBody(
        request: 'professional.addNote',
        description: 'The note added is in the json given as parameter, the note must be between 0 and 10',
        required: true,
        content: new OA\JsonContent(
            type: 'object',
            ref: '#/components/schemas/postAddNoteProfessional'
        )
    )]
    #[Route('/api/professionals/note/{idProfessional}', name: 'professional.addNote', methods: ['POST'])]
    #[ParamConverter("professional", options: ['id' => 'idProfessional'], class:'App\Entity\Professional')]
    public function addNoteProfessionals(
        Request $request,
        Professional $professional,
        EntityManagerInterface $entityManager,
        UrlGeneratorInterface $urlGenerator,
        SerializerInterface $serializer,
        ProfessionalRepository $professionalRepository,
        CompanyRepository $companyRepository,
        TagAwareCacheInterface $cache
    ) : JsonResponse
    {
        $cache->invalidateTags(["professionalCache"]);

        # r??cup??ration de la note, argument
        $newNote = $request->get('note', 1);

        # calcul de la nouvelle moyenne de l'employ?? (arrondi au premier d??cimale)
        # $newNoteAvg = round((Nombre de note * note moyenne + $newNote)/(nombre de note +1), 1)
        $newNoteAvg = round(($professional->getNoteCount()*$professional->getNoteAvg()+$newNote)/($professional->getNoteCount()+1), 1);
        $professional->setNoteAvg($newNoteAvg);
        
        # incr??mentation de la variable NoteCount car une note est rajout??
        $professional->setNoteCount($professional->getNoteCount()+1);

        # persist + flush pour mettre ?? jour la table
        $entityManager->persist($professional);
        $entityManager->flush();

        $location = $urlGenerator->generate('professional.get', ["idProfessional" => $professional->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        $context = SerializationContext::create()->setGroups(["getProfessionals"]);
        $jsonProfessional = $serializer->serialize($professional, 'json', $context);
        return new JsonResponse($jsonProfessional, JsonResponse::HTTP_CREATED, ["Location" => $location], true);
    }
    


    /**
     * Return the the number of note (NoteCount) and the note average (NoteAvg) of the professional, id given as a query parameter
     *
     * @param Professional $professional
     * @param SerializerInterface $serializer
     * @return JsonResponse
     */  
    #[OA\Response(
        response: 200,
        description: 'Update the professional (id) according to the json file given as parameter',
        content: new Model(type : Professional::class)
    )]
    #[Route('/api/professionals/note/{idProfessional}', name: 'professional.getNote', methods: ['GET'])]
    #[ParamConverter("professional", options: ['id' => 'idProfessional'], class:'App\Entity\Professional')]
    public function getNoteProfessionals(
        Professional $professional,
        SerializerInterface $serializer
    ) : JsonResponse
    {
        //R??cup??ration de la note moyenne
        $note = $professional->getNoteAvg();
        $noteCount = $professional->getNoteCount();

        return new JsonResponse($serializer->serialize([$note, $noteCount], 'json'), Response::HTTP_OK, ['accept' => 'json'], true);
    }
    


    /**
     * Return all professionals of a company ordered by note.
     *
     * @param ProfessionalRepository $professionalRepository
     * @param SerializerInterface $serializer
     * @return JsonResponse
     */  
    #[OA\Response(
        response: 200,
        description: '',
        content: new Model(type : Professional::class)
    )]
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
    


    // R??cup??re la liste des professionnels exercant un job class?? par note.
    /**
     * Return all professionals having a special job, ordered by note.
     *
     * @param ProfessionalRepository $professionalRepository
     * @param SerializerInterface $serializer
     * @return JsonResponse
     */  
    #[OA\Response(
        response: 200,
        description: '',
        content: new Model(type : Professional::class)
    )]
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
