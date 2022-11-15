<?php

namespace App\Controller;

use App\Entity\Picture;
use App\Repository\PictureRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\SerializerInterface;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Picture")
 */
class PictureController extends AbstractController
{
    #[Route('/picture', name: 'app_picture')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/PictureController.php',
        ]);
    }

    #[Route('api/pictures/{idPicture}', name:"picture.get", methods:['GET'])]
    public function getPicture(
    int $idPicture, 
    SerializerInterface $serializer, 
    PictureRepository $pictureRepository, 
    Request $request,
    UrlGeneratorInterface $urlGenerator
    ) :JsonResponse
    {
        $picture = $pictureRepository->find($idPicture);
        $relativePath = $picture->getPublicPath() . "/" . $picture->getRealPath();
        $location = $request->getUriForPath('/');
        $location = $location . str_replace("/assets", "assets", $relativePath);
        if ($picture) 
        {
            return new JsonResponse(($serializer->serialize($picture, 'json', ['groups' => 'getPicture'], JsonResponse::HTTP_OK, ["Location" => $location], true)));
        }
        return new JsonResponse(null, JsonResponse::HTTP_NOT_FOUND);
    }

    #[Route('api/pictures', name: 'pictures.create', methods:['POST'])]
    public function createPicture(
        Request $request,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        UrlGeneratorInterface $urlGenerator

    ): JsonResponse
    {
        $picture = new Picture();
        $files = $request->files->get('file');
        $picture->setFile($files);
        $picture->setMimeType($files->getClientMimeType());
        $picture->setRealName($files->getClientOriginalName());
        $picture->setPublicPath("/assets/picturess");
        $picture->setStatus('on');
        $entityManager->persist($picture);
        $entityManager->flush();

        $location = $urlGenerator->generate("picture.get", ["idPicture" => $picture->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        $jsonPictures = $serializer->serialize($picture, 'json', ['groups' => 'getPicture']);
        return new JsonResponse($jsonPictures, JsonResponse::HTTP_CREATED, ['Location' => $location], true);
    }
}
