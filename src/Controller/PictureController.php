<?php

namespace App\Controller;

use App\Entity\Picture;
use OpenApi\Annotations as OA;
use App\Repository\PictureRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

/**
 * @OA\Tag(name="Picture")
 */
class PictureController extends AbstractController
{
    #[Route('api/pictures/{idPicture}', name:"picture.get", methods:['GET'])]
    public function getPicture(
    int $idPicture, 
    SerializerInterface $serializer, 
    PictureRepository $pictureRepository, 
    Request $request
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
    #[IsGranted("ROLE_ADMIN", message: "Admin rights needed.")]
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

    #[Route('/api/pictures/{idPicture}', name: 'pictures.delete', methods: ['DELETE'])]
    #[ParamConverter("picture", options:["id"=>"idPicture"], class:"App\Entity\Picture")]
    #[IsGranted("ROLE_ADMIN", message: "Admin rights needed.")]
    public function deletePicture(
        Picture $picture,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $entityManager->remove($picture);
        $entityManager->flush();
        return new JsonResponse(null,Response::HTTP_NO_CONTENT);
    }
}
