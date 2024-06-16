<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

class UserController extends AbstractController
{
    private $jwtManager;
    private $taskRepository;

    // Constructor to initialize JWT manager and task repository
    public function __construct(JWTTokenManagerInterface $jwtManager, TaskRepository $taskRepository)
    {
        $this->jwtManager = $jwtManager;
        $this->taskRepository = $taskRepository;
    }

    // Route to handle user registration
    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $em): Response
    {
        // Decode JSON request data
        $data = json_decode($request->getContent(), true);

        // Create a new User entity and set its email and password
        $user = new User();
        $user->setEmail($data['email']);
        $user->setPassword($passwordHasher->hashPassword($user, $data['password']));

        // Persist the new user entity to the database
        $em->persist($user);
        $em->flush();

        // Generate JWT token for the registered user
        $token = $this->jwtManager->create($user);

        // Prepare the response data
        $responseData = [
            'message' => 'User registered successfully',
            'data' => [
                'email' => $user->getEmail(),
                'token' => $token,
            ],
        ];

        // Return JSON response with created status
        return $this->json($responseData, Response::HTTP_CREATED);
    }

    // Route to handle user login
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(Request $request, UserProviderInterface $userProvider, UserPasswordHasherInterface $passwordHasher): Response
    {
        // Capture and decode JSON request data
        $data = json_decode($request->getContent(), true);

        // Find the user by email
        $user = $userProvider->loadUserByIdentifier($data['email']);

        // Check if user exists and verify password
        if (!$user || !$passwordHasher->isPasswordValid($user, $data['password'])) {
            throw new BadCredentialsException('Invalid credentials');
        }

        // Generate JWT token for the authenticated user
        $token = $this->jwtManager->create($user);

        // Retrieve tasks for the authenticated user
        $tasks = $this->taskRepository->findBy(['owner' => $user]);

        // Format the tasks
        $formattedTasks = [];
        foreach ($tasks as $task) {
            $formattedTasks[] = [
                'id' => $task->getId(),
                'name' => $task->getName(),
                'description' => $task->getDescription(),
            ];
        }

        // Prepare the response data
        $responseData = [
            'message' => 'User logged in successfully',
            'data' => [
                'email' => $user->getEmail(),
                'token' => $token,
                'tasks' => $formattedTasks,
            ],
        ];

        // Return JSON response
        return $this->json($responseData);
    }
}
