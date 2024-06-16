<?php

namespace App\Controller;

use App\Entity\Task;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @Route("/api/tasks")
 */
class TaskController extends AbstractController
{
    private $entityManager;
    private $taskRepository;

    // Constructor to inject EntityManagerInterface and TaskRepository
    public function __construct(EntityManagerInterface $entityManager, TaskRepository $taskRepository)
    {
        $this->entityManager = $entityManager;
        $this->taskRepository = $taskRepository;
    }

    /**
     * @Route("/list", name="task_list", methods={"GET"})
     * @IsGranted("ROLE_USER") // Ensures user is authenticated
     */
    public function list(): Response
    {
        // Retrieve the authenticated user
        $user = $this->getUser();

        // Retrieve tasks owned by the user
        $tasks = $this->taskRepository->findBy(['owner' => $user]);

        // Format tasks for response
        $formattedTasks = [];
        foreach ($tasks as $task) {
            $formattedTasks[] = [
                'id' => $task->getId(),
                'name' => $task->getName(),
                'description' => $task->getDescription(),
            ];
        }

        // Prepare response with tasks and owner's email
        $response = [
            'tasks' => $formattedTasks,
            'owner' => $user->getEmail(), // Assuming getEmail() returns the user's email
        ];

        // Return JSON response
        return $this->json($response);
    }

    /**
     * @Route("/create", name="task_create", methods={"POST"})
     */
    public function create(Request $request): Response
    {
        // Decode JSON request data
        $data = json_decode($request->getContent(), true);

        // Retrieve the authenticated user
        $user = $this->getUser();

        // Check if a task with the same name and owner already exists
        $existingTask = $this->taskRepository->findOneBy(['name' => $data['name'], 'owner' => $user]);
        if ($existingTask) {
            throw new BadRequestHttpException('Task already exists.');
        }

        // Create a new Task entity and set its properties
        $task = new Task();
        $task->setName($data['name']);
        $task->setDescription($data['description']);
        $task->setOwner($user);

        // Persist the new task entity to the database
        $this->entityManager->persist($task);
        $this->entityManager->flush();

        // Prepare response with details of the created task
        return $this->json([
            'message' => 'Task created successfully',
            'task' => [
                'id' => $task->getId(),
                'name' => $task->getName(),
                'description' => $task->getDescription(),
                'owner' => $task->getOwner()->getEmail(),
            ]
        ], Response::HTTP_CREATED);
    }

    /**
     * @Route("/{id}", name="task_show", methods={"GET"})
     * @IsGranted("ROLE_USER") // Ensures user is authenticated
     */
    public function show(Task $task): Response
    {
        // Retrieve the authenticated user
        $user = $this->getUser();

        // Check if the authenticated user owns the requested task
        if ($task->getOwner() !== $user) {
            throw $this->createAccessDeniedException('You do not own this task.');
        }

        // Prepare response with details of the requested task
        return $this->json([
            'id' => $task->getId(),
            'name' => $task->getName(),
            'description' => $task->getDescription(),
            'owner' => $task->getOwner()->getEmail(),
        ]);
    }

    /**
     * @Route("/update/{id}", name="task_update", methods={"PUT"})
     * @IsGranted("ROLE_USER") // Ensures user is authenticated
     */
    public function update(Request $request, Task $task): Response
    {
        // Retrieve the authenticated user
        $user = $this->getUser();

        // Check if the authenticated user owns the task being updated
        if ($task->getOwner() !== $user) {
            throw $this->createAccessDeniedException('You do not own this task.');
        }

        // Decode JSON request data
        $data = json_decode($request->getContent(), true);

        // Update task properties
        $task->setName($data['name']);
        $task->setDescription($data['description']);

        // Persist changes to the database
        $this->entityManager->flush();

        // Prepare response with details of the updated task
        return $this->json([
            'message' => 'Task updated successfully',
            'task' => [
                'id' => $task->getId(),
                'name' => $task->getName(),
                'description' => $task->getDescription(),
                'owner' => $task->getOwner()->getEmail(),
            ]
        ]);
    }

    /**
     * @Route("/delete/{id}", name="task_delete", methods={"DELETE"})
     * @IsGranted("ROLE_USER") // Ensures user is authenticated
     */
    public function delete(Task $task): Response
    {
        // Retrieve the authenticated user
        $user = $this->getUser();

        // Check if the authenticated user owns the task being deleted
        if ($task->getOwner() !== $user) {
            throw $this->createAccessDeniedException('You do not own this task.');
        }

        // Remove the task from the database
        $this->entityManager->remove($task);
        $this->entityManager->flush();

        // Prepare response indicating successful deletion
        return $this->json(['message' => 'Task deleted successfully']);
    }
}
