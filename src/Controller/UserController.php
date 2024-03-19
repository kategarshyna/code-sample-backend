<?php

namespace App\Controller;

use App\Entity\Photo;
use App\Entity\User;
use App\Form\LoginType;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Service\FileUploader;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Nelmio\ApiDocBundle\Annotation\Security as SecurityDoc;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="User")
 */
class UserController extends BaseController
{
    const KEY_AVATAR = 'avatar';
    const KEY_PHOTOS = 'photos';
    const IMAGE_FIELDS = [
        self::KEY_AVATAR,
        self::KEY_PHOTOS
    ];

    private ParameterBagInterface $parameters;

    public function __construct(ParameterBagInterface $parameters)
    {
        $this->parameters = $parameters;
    }


    /**
     * @Route("/api/users/register", name="user_register", methods={"POST"})
     *
     * @SecurityDoc(name="")
     *
     * @OA\RequestBody(
     *     required=true,
     *     @OA\MediaType(
     *         mediaType="multipart/form-data",
     *         @OA\Schema(
     *             required={"data"},
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 description="JSON object",
     *                 example={"firstName": "John", "lastName": "Doe", "email": "john@example.com", "password": "password"}
     *             ),
     *             @OA\Property(
     *                 property="avatar",
     *                 type="string",
     *                 format="binary",
     *                 description="Файл зображення"
     *             ),
     *             @OA\Property(
     *                 property="photos[]",
     *                 type="array",
     *                 @OA\Items(
     *                     type="string",
     *                     format="binary"
     *                 ),
     *                 description="Photos array"
     *             )
     *         )
     *     )
     * ),
     *
     * @OA\Response(response=200, description="User registered successfully")
     *
     * @OA\Response(
     *     response=400,
     *     description="Registration failed",
     *     @OA\JsonContent(
     *         @OA\Property(property="message", type="string", description="Error message"),
     *         @OA\Property(property="errors", type="object", description="Validation errors")
     *     )
     * )
     */
    public function register(
        Request $request,
        UserRepository $userRepository,
        FileUploader $fileUploader,
        UserPasswordEncoderInterface $passwordEncoder
    ): JsonResponse
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);

        $data = $request->request->get('data');
        $files = $request->files->all();

        $payload = json_decode($data, true);

        /** Set images to payload if exist */
        foreach (self::IMAGE_FIELDS as $field) {
            $payload[$field] = !empty($files) && array_key_exists($field, $files)
                ? $files[$field]
                : null;
        }

        $form->submit($payload);

        if ($form->isValid()) {

            $user->setSalt(mb_substr(base64_encode(random_bytes(12)), 0, 16));
            $user->setPassword($passwordEncoder->encodePassword($user, $user->getPassword()));

            $avatarImage = $form->get(self::KEY_AVATAR)->getData();
            if ($avatarImage) {
                $user->setAvatar($fileUploader->upload($avatarImage, $user));
            }

            $photoImages = $form->get(self::KEY_PHOTOS)->getData();
            if ($photoImages) {
                foreach ($photoImages as $photo) {
                    $user->addPhoto($fileUploader->upload($photo, $user));
                }
            }

            $userRepository->add($user);

            return new JsonResponse(['message' => 'User registered successfully'], Response::HTTP_OK);
        }

        return new JsonResponse([
            'message' => 'Registration failed',
            'errors' => $this->getErrorsFromForm($form)
        ], Response::HTTP_BAD_REQUEST);
    }

    /**
     * @Route("/api/users/login", name="user_login", methods={"POST"})
     *
     * @SecurityDoc(name="")
     *
     * @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *          type="object",
     *          @OA\Property(property="email", type="string"),
     *          @OA\Property(property="password", type="string")
     *     )
     * ),
     *
     * @OA\Response(response=200, description="Returns a JWT token")
     * @OA\Response(response=400, description="Invalid credentials")
     * @OA\Response(response=401, description="Unauthorized")
     */
    public function login(
        Request $request,
        UserRepository $userRepository,
        UserPasswordEncoderInterface $encoder,
        JWTTokenManagerInterface $jwtManager
    ): JsonResponse
    {
        $user = new User();
        $form = $this->createForm(LoginType::class, $user);
        $form->submit(json_decode($request->getContent(), true));

        if ($form->isValid()) {
            $user = $userRepository->loadUserByUsername($form->get('email')->getData());
            if (!$user || !$encoder->isPasswordValid($user, $form->get('password')->getData())) {
                return $this->json(['message' => 'Invalid credentials'], Response::HTTP_UNAUTHORIZED);
            }

            $token = $jwtManager->create($user);

            return $this->json(['token' => $token]);
        }

        return new JsonResponse([
            'message' => 'Invalid credentials',
            'errors' => $this->getErrorsFromForm($form)
        ], Response::HTTP_BAD_REQUEST);
    }

    /**
     * @Route("/api/users/me", name="get_user_data", methods={"GET"})
     *
     * @OA\Response(
     *     response=200,
     *     description="User data retrieved successfully",
     *     @OA\Schema(
     *         type="object",
     *         @OA\Property(property="id", type="integer"),
     *         @OA\Property(property="fullName", type="string"),
     *         @OA\Property(property="email", type="string"),
     *         @OA\Property(property="avatar", type="object"),
     *         @OA\Property(property="photos", type="object")
     *     )
     * )
     *
     * @OA\Response(response=404, description="User not found")
     */
    public function getAuthenticatedUserData(Security $security): JsonResponse
    {
        $user = $security->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        if(!$user->getAvatar()) {
            $user->setAvatar((new Photo())->setUrl($this->parameters->get('default_avatar')));
        }

        return $this->json($user, Response::HTTP_OK, [], [AbstractNormalizer::GROUPS => ['user']]);
    }
}
