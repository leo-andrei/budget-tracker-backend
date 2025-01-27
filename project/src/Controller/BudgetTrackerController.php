<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\BudgetTrackerService;

class BudgetTrackerController extends AbstractController
{
    private BudgetTrackerService $tracker;

    public function __construct(BudgetTrackerService $tracker)
    {
        $this->tracker = $tracker;
    }

    // #[Route('/api/budget-report', name: 'budget_report', methods: ['POST'])]
    public function generateReport(Request $request): JsonResponse
    {
        $requestData = json_decode($request->getContent(), true);
        if (empty($requestData)) {
            return $this->json(['errors' => ['Invalid request data.']], 400);
        }
        $errors = $this->validateRequestData($requestData);
        if (!empty($errors)) {
            return $this->json(['errors' => $errors], 400);
        }

        // Extract startDate and period from the request
        $startDate = date('Y-m-d', strtotime($requestData['startDate']));
        $periodMonths = (int) $requestData['period'];
        // Add budget entries
        $budgetEntries = $requestData['budgetHistory'] ?? [];
        foreach ($budgetEntries as $entry) {
            $this->tracker->addBudgetEntry(
                date('Y-m-d', strtotime($entry['date'])), 
                $entry['time'], 
                $entry['budget']
            );
        }

        // Generate and return the report
        $report = $this->tracker->generateReport($startDate, $periodMonths);

        return $this->json($report);
    }

    private function validateRequestData(array $data): array
    {
        $errors = [];

        // Validate startDate
        if (!isset($data['startDate']) || !strtotime($data['startDate'])) {
            $errors[] = "Invalid or missing 'startDate'.";
        }

        // Validate period
        if (!isset($data['period']) || !is_numeric($data['period']) || $data['period'] <= 0) {
            $errors[] = "Invalid or missing 'period'.";
        }

        // Validate budget entries
        if (isset($data['budgetHistory']) && is_array($data['budgetHistory'])) {
            $entryErrors = $this->validateBudgetEntries($data['budgetHistory']);
            $errors = array_merge($errors, $entryErrors);
        }

        return $errors;
    }

    private function validateBudgetEntries(array $entries): array
    {
        $errors = [];

        foreach ($entries as $index => $entry) {
            // Validate date
            if (!isset($entry['date']) || !strtotime($entry['date'])) {
                $errors[] = "Invalid date for entry $index.";
            }

            // Validate time
            if (!isset($entry['time']) || 
                !preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $entry['time'])) {
                $errors[] = "Invalid time format for entry $index.";
            }

            // Validate budget
            if (!isset($entry['budget']) || 
                !is_numeric($entry['budget']) || 
                $entry['budget'] < 0) {
                $errors[] = "Invalid budget for entry $index.";
            }
        }

        return $errors;
    }
}
