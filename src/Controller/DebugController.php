<?php

namespace App\Controller;

use App\Repository\ScheduleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;


class DebugController extends AbstractController
{
    // Този контролер само "слуша" и връща каквото е получил, без да записва в базата.
    // Така ще разбереш дали връзката работи.
    #[Route('/api/schedules', methods: ['POST'])]
    public function saveSchedule(Request $request, EntityManagerInterface $em, ScheduleRepository $repo): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Търсим дали за тази дата този потребител вече има запис
        $entry = $repo->findOneBy([
            'date' => $data['date'],
            'userId' => $data['userId'] // Тук разделяме Асен от Севито!
        ]);

        // Ако няма такъв запис, създаваме нов
        if (!$entry) {
            $entry = new Schedule();
            $entry->setDate($data['date']);
            $entry->setUserId($data['userId']); // 'husband' или 'wife'
        }

        // Ако типът е 0 (none), значи човекът иска да ИЗТРИЕ дежурството
        if ($data['type'] == "0" && $data['isOnCall'] == false) {
            if ($entry->getId()) {
                $em->remove($entry);
                $em->flush();
            }
            return $this->json(['status' => 'deleted']);
        }

        // Обновяваме данните
        $entry->setType($data['type']);
        $entry->setIsOnCall($data['isOnCall'] ?? false);

        // Записваме в базата (MySQL)
        $em->persist($entry);
        $em->flush();

        return $this->json(['status' => 'saved', 'data' => $data]);
    }

    // --- 2. ЧЕТЕНЕ НА ВСИЧКИ ДЕЖУРСТВА ---
    #[Route('/api/schedules', methods: ['GET'])]
    public function getSchedules(ScheduleRepository $repo): JsonResponse
    {
        // Взимаме всичко от базата данни (И на Асен, и на Севито)
        $schedules = $repo->findAll();

        $result = [];
        foreach ($schedules as $s) {
            $result[] = [
                'date' => $s->getDate(),
                'userId' => $s->getUserId(),
                'type' => $s->getType(),
                'isOnCall' => $s->getIsOnCall()
            ];
        }

        // Връщаме го в същия формат 'hydra:member', който Flutter-а ти очаква
        return $this->json(['hydra:member' => $result]);
    }
}