<?php

namespace App\Service;

class BudgetTrackerService
{
    private $budgetHistory = [];

    public function addBudgetEntry(string $date, string $time, float $budget): void
    {
        $key = $date . '_' . $time;
        $this->budgetHistory[$key] = [
            'date' => $date,
            'time' => $time,
            'budget' => $budget
        ];
        ksort($this->budgetHistory);
    }

    public function generateReport(string $startDate, int $periodMonths): array
    {
        $costsGenerated = [];
        $dailyHistoryReport = [];

        $costs = $this->generateAllCosts($startDate, $periodMonths);

        foreach ($costs as $date => $costData) {
            $costsGenerated[$date] = $costData['costs'];
            $dailyHistoryReport[] = [
                'date' => $date,
                'max_budget' => $costData['max_budget'],
                'total_cost' => $costData['total_cost']
            ];
        }

        return [
            'costs_generated' => $costsGenerated,
            'daily_history_report' => $dailyHistoryReport
        ];
    }
    // calculate the maximum monthly budgets - 
    private function calculateMaxMonthlyBudgets($startDate, $periodMonths)
    {
        $monthlyBudgets = [];
        foreach ($this->getDaysInPeriod($startDate, $periodMonths) as $date) {
            $month = (new \DateTime($date))->format('Y-m');
            $dailyBudgets = $this->getBudgetsForDate($date);
            $maxDailyBudget = $this->getMaxBudgetForDate($dailyBudgets);
    
            if (!isset($monthlyBudgets[$month])) {
                $monthlyBudgets[$month] = 0;
            }
    
            $monthlyBudgets[$month] += $maxDailyBudget;
        }
    
        return $monthlyBudgets;
    }

    private function generateAllCosts(string $startDate, int $periodMonths): array{
        $costs = [];
        $monthlyCosts = [];
        $monthlyMaxBudgets = $this->calculateMaxMonthlyBudgets($startDate, $periodMonths);

        foreach ($this->getDaysInPeriod($startDate, $periodMonths) as $date) {
            // Get current month
            $currentMonth = (new \DateTime($date))->format('Y-m');
            if (!isset($monthlyCosts[$currentMonth])) {
                $monthlyCosts[$currentMonth] = 0;
            }
            // get all the daily budgets for the date
            $dailyBudgets = $this->getBudgetsForDate($date);
            // get the maximum budget for the date
            $maxDailyBudget = $this->getMaxBudgetForDate($dailyBudgets);
            // generate the daily costs for the date
            $dailyCosts = $this->generateDayCosts($dailyBudgets, $monthlyMaxBudgets[$currentMonth], $monthlyCosts[$currentMonth]);

            $totalCost = $this->getTotalCost($dailyCosts);
            $monthlyCosts[$currentMonth] += $totalCost;

            $costs[$date]["costs"] = $dailyCosts;
            $costs[$date]["total_cost"] = $totalCost;
            $costs[$date]["max_budget"] = $maxDailyBudget;
        }

        return $costs;
    }

    private function generateDayCosts(array $dailyBudgets, float $monthlyMaxBudget, float $monthlyCosts): array
    {
        $costs = [];
        $dailyTotalCost = 0;
        
        // if no budgets, return empty array
        if (empty($dailyBudgets)) {
            return [];
        }
        
        // sort budgets by time to ensure chronological order
        usort($dailyBudgets, fn($a, $b) => strtotime($a['time']) - strtotime($b['time']));
        
        // randomly determine how many costs to generate (1 to 10)
        $targetCosts = rand(1, 10);
        $attempts = 0;
        $maxAttempts = 50; // added this max attempts to prevent infinite loop
        
        while (count($costs) < $targetCosts && $attempts < $maxAttempts) {
            $attempts++;
            
            $startTime = strtotime($dailyBudgets[0]['time']); // start of first budget
            $endTime = strtotime("23:59:59"); // End of day
            $randomTime = rand($startTime, $endTime);
            $costTime = date('H:i:s', $randomTime);
            
            // find applicable budget for this time
            $currentBudget = $this->getApplicableBudgetForTime($dailyBudgets, $costTime);
            
            if ($currentBudget <= 0) {
                continue; // skip if budget is 0 or negative
            }
            
            // calculate maximum allowed cost
            $maxAllowedCost = min(
                $currentBudget, // can't exceed current budget
                ($currentBudget * 2) - $dailyTotalCost // can't exceed 2x budget in total
            );
            // apply monthly budget limit
            $remainingCostForMonth = $monthlyMaxBudget - $monthlyCosts;
            $maxAllowedCost = min($maxAllowedCost, $remainingCostForMonth);
            
            if ($maxAllowedCost <= 0) {
                continue; // skip if no budget left
            }
            
            // generate random cost
            $cost = round(rand(1, $maxAllowedCost * 100) / 100, 2);
            $monthlyCosts += $cost;
        
            $dailyTotalCost += $cost;
            $costs[] = [
                'cost' => $cost,
                'time' => $costTime,
                'budget_at_time' => $currentBudget,
                'daily_total_after' => $dailyTotalCost
            ];
            
        }
        
        // sort costs by time
        usort($costs, fn($a, $b) => strtotime($a['time']) - strtotime($b['time']));
        
        return $costs;
    }
    
    private function getApplicableBudgetForTime(array $dailyBudgets, string $costTime): float
    {
        $applicableBudget = 0;
        
        foreach ($dailyBudgets as $entry) {
            if (strtotime($entry['time']) <= strtotime($costTime)) {
                $applicableBudget = $entry['budget'];
            } else {
                break;
            }
        }
        
        return $applicableBudget;
    }

    private function getTotalCost(array $dailyCosts): float
    {
        return array_sum(array_map(fn($cost) => $cost['cost'], $dailyCosts));
    }

    private function getBudgetsForDate(string $date): array
    {
        return array_values(array_map(
            fn($entry) => ['budget'=>$entry['budget'], 'time'=>$entry['time']],
            array_filter($this->budgetHistory, fn($entry) => $entry['date'] === $date)
        ));
    }

    private function getMaxBudgetForDate(array $dailyBudgets): float
    {
        $allDailyBudgets = [];
        if (empty($dailyBudgets)) {
            return 0;
        }
        foreach ($dailyBudgets as $entry) {
            $allDailyBudgets[] = $entry['budget'];
        }
        return max($allDailyBudgets);
    }

    private function getDaysInPeriod(string $startDate, int $periodMonths): array
    {
        $days = [];
        $currentDate = new \DateTime($startDate);
        $endDate = (clone $currentDate)->modify("+$periodMonths months");

        while ($currentDate < $endDate) {
            $days[] = $currentDate->format('Y-m-d');
            $currentDate->modify('+1 day');
        }

        return $days;
    }
}
