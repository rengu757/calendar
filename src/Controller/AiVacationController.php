<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Routing\Attribute\Route;

class AiVacationController extends AbstractController
{
    private $client;
    private $openAiKey;

    public function __construct(HttpClientInterface $client, string $openAiKey)
    {
        $this->client = $client;
        $this->openAiKey = $openAiKey;
    }

    #[Route('/api/ai/plan-vacation', name: 'api_ai_plan', methods: ['POST'])]
    public function plan(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $history = $data['history'] ?? [];
        $wifeHistory = $data['wifeHistory'] ?? [];

        // --- ЗАЩИТАТА Е ТУК ---
        // Проверяваме дали клиентът изрично е поискал AI анализ
        $useAi = $data['useAi'] ?? false;

        $startDate = $this->detectStartDate($history, $wifeHistory);
        $calcResult = $this->calculateCommonFreeDays($startDate, $history, $wifeHistory);
        $options = $calcResult['options'];

        if (empty($options)) {
            return new JsonResponse(['message' => 'Няма открити общи свободни дни.', 'options' => []]);
        }

        // Ако useAi е FALSE, връщаме само датите без да викаме OpenAI
        if (!$useAi) {
            return new JsonResponse([
                'message' => 'Открити са свободни периоди. Натиснете "AI Анализ" за идеи за пътуване.',
                'options' => $options
            ]);
        }

        // САМО АКО useAi Е TRUE, ХАРЧИМ ТОКЕНИ:
        $suggestion = $this->askGpt($options);

        return new JsonResponse([
            'message' => $suggestion,
            'options' => $options
        ]);
    }

    private function detectStartDate(array $h1, array $h2): \DateTime
    {
        $dates = array_merge(array_keys($h1), array_keys($h2));
        if (empty($dates)) return new \DateTime();
        usort($dates, function ($a, $b) {
            return strtotime($a) - strtotime($b);
        });
        return new \DateTime($dates[0]);
    }

    private function calculateCommonFreeDays(\DateTime $start, array $history, array $wifeHistory): array
    {
        $options = [];
        $currentStreak = [];

        $endOfMonth = (clone $start)->modify('last day of this month');
        $cursor = clone $start;

        while ($cursor <= $endOfMonth) {
            $key = $cursor->format('Y-n-j');
            $asenFree = !isset($history[$key]) || ($history[$key] != 1 && $history[$key] != 2);
            $seviFree = !isset($wifeHistory[$key]) || ($wifeHistory[$key] == 0 || $wifeHistory[$key] == 6);

            if ($asenFree && $seviFree) {
                $currentStreak[] = clone $cursor;
            } else {
                if (count($currentStreak) >= 3) {
                    $options[] = $this->createOption($currentStreak);
                }
                $currentStreak = [];
            }
            $cursor->modify('+1 day');
        }

        if (count($currentStreak) >= 3) {
            $options[] = $this->createOption($currentStreak);
        }

        return ['options' => $options];
    }

    private function createOption(array $streak): array
    {
        $displayStreak = count($streak) > 7 ? array_slice($streak, 0, 7) : $streak;
        $label = $displayStreak[0]->format('d.m') . ' - ' . end($displayStreak)->format('d.m');

        $dates = [];
        foreach ($displayStreak as $day) {
            $dates[] = $day->format('Y-n-j');
        }

        return [
            'label' => $label,
            'dates' => $dates
        ];
    }

    private function askGpt(array $options): string
    {
        $labels = array_map(fn($opt) => $opt['label'], $options);
        $optionsList = implode(", ", $labels);

        $prompt = "Ние сме двойка (Асен и Севи). Имаме следните възможни периоди за почивка този месец: $optionsList. " .
            "Твоята задача:
         1. Предложи по една интересна дестинация за всеки от тези периоди.
         2. ВАЖНО УСЛОВИЕ: Ако даден период е с продължителност 5 или повече дни, дестинацията ЗАДЪЛЖИТЕЛНО трябва да е в чужбина (Европа), достъпна с бюджетен полет от София.
         3. Ако периодът е под 5 дни, може да предложиш и дестинация в България (спа или планински туризъм).
    
    Бъди много кратък и конкретен. Отговори на Български език.";

        try {
            $response = $this->client->request('POST', 'https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->openAiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'gpt-3.5-turbo',
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                    'max_tokens' => 500
                ]
            ]);

            return $response->toArray()['choices'][0]['message']['content'];
        } catch (\Exception $e) {
            return "Грешка при AI: " . $e->getMessage();
        }
    }
}
