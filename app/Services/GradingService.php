<?php

namespace App\Services;

use App\Models\Activity;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log; // Keep Log import

class GradingService
{
    /**
     * Calculate SHS Initial Grade based on component scores and weights.
     */
    public function calculateShsInitialGrade(
        array $studentScores,
        Collection $activities,
        ?int $wwWeight,
        ?int $ptWeight,
        ?int $qaWeight
    ): ?float {
        if ($wwWeight === null || $ptWeight === null || $qaWeight === null) {
            Log::warning('SHS weights not fully configured.');

            return null; // Weights must be set
        }
        if ($wwWeight + $ptWeight + $qaWeight !== 100) {
            Log::warning('SHS weights do not sum to 100.');

            return null; // Weights must sum to 100
        }

        $wwDetails = $this->calculateShsComponentDetails(
            $studentScores,
            $activities,
            Activity::COMPONENT_WRITTEN_WORK
        );
        $ptDetails = $this->calculateShsComponentDetails(
            $studentScores,
            $activities,
            Activity::COMPONENT_PERFORMANCE_TASK
        );
        $qaDetails = $this->calculateShsComponentDetails(
            $studentScores,
            $activities,
            Activity::COMPONENT_QUARTERLY_ASSESSMENT
        );

        $initialGrade = 0;
        $hasScores = false; // Track if any component has scores

        if ($wwDetails['percentage_score'] !== null) {
            $initialGrade += $wwDetails['percentage_score'] * ($wwWeight / 100);
            $hasScores = true;
        }
        if ($ptDetails['percentage_score'] !== null) {
            $initialGrade += $ptDetails['percentage_score'] * ($ptWeight / 100);
            $hasScores = true;
        }
        if ($qaDetails['percentage_score'] !== null) {
            $initialGrade += $qaDetails['percentage_score'] * ($qaWeight / 100);
            $hasScores = true;
        }

        // If no scores were found in any component, return null
        if (! $hasScores) {
            return null;
        }

        // Ensure grade is not below 0 or above 100 (though calculation should prevent > 100)
        return max(0, min(round($initialGrade, 2), 100));
    }

    /**
     * Calculate details for a specific SHS component (WW, PT, QA).
     */
    public function calculateShsComponentDetails(
        array $studentScores,
        Collection $activities,
        string $componentType
    ): array {
        $componentActivities = $activities->where(
            'component_type',
            $componentType
        );
        $totalRawScore = 0;
        $totalHighestPossibleScore = 0;
        $hasScoreInComponent = false;

        foreach ($componentActivities as $activity) {
            $score = $studentScores[$activity->id] ?? null;
            // Only include activities where a score is entered
            if ($score !== null && $activity->total_points > 0) {
                $totalRawScore += (float) $score;
                $totalHighestPossibleScore += (float) $activity->total_points;
                $hasScoreInComponent = true;
            } elseif ($score !== null && $activity->total_points <= 0) {
                Log::warning(
                    "Activity ID {$activity->id} has score {$score} but total_points is zero or less."
                );
            }
        }

        $percentageScore = null;
        if ($hasScoreInComponent && $totalHighestPossibleScore > 0) {
            $percentageScore = round(
                ($totalRawScore / $totalHighestPossibleScore) * 100,
                2
            );
            // Cap at 100%
            $percentageScore = min($percentageScore, 100.0);
        }

        return [
            'total_raw_score' => $hasScoreInComponent ? $totalRawScore : null,
            'total_highest_possible_score' => $hasScoreInComponent
                ? $totalHighestPossibleScore
                : null,
            'percentage_score' => $percentageScore,
        ];
    }

    /**
     * Transmute SHS Initial Grade using the DepEd table.
     */
    public function transmuteShsGrade(?float $initialGrade): ?int
    {
        if ($initialGrade === null) {
            return null;
        }

        // Ensure grade is within 0-100 range before transmutation
        $initialGrade = max(0, min($initialGrade, 100));

        // DepEd Transmutation Table (Based on Docs/Common Practice)
        $transmutationTable = [
            100.0 => 100,
            98.4 => 99,
            96.8 => 98,
            95.2 => 97,
            93.6 => 96, // Changed 95.21 to 95.20 etc for range start
            92.0 => 95,
            90.4 => 94,
            88.8 => 93,
            87.2 => 92,
            85.6 => 91,
            84.0 => 90,
            82.4 => 89,
            80.8 => 88,
            79.2 => 87,
            77.6 => 86,
            76.0 => 85,
            74.4 => 84,
            72.8 => 83,
            71.2 => 82,
            69.6 => 81, // Changed 69.61 to 69.60
            68.0 => 80,
            66.4 => 79,
            64.8 => 78,
            63.2 => 77,
            61.6 => 76, // Changed 64.81, 63.21 etc
            60.0 => 75, // Changed 60.01 to 60.00
            56.0 => 74,
            52.0 => 73,
            48.0 => 72,
            44.0 => 71,
            40.0 => 70, // Changed 52.01, 40.01
            36.0 => 69,
            32.0 => 68,
            28.0 => 67,
            24.0 => 66,
            20.0 => 65,
            16.0 => 64,
            12.0 => 63,
            8.0 => 62,
            4.0 => 61,
            0.0 => 60,
        ];

        // Find the highest key in the table that is less than or equal to the initial grade
        $transmutedGrade = 60; // Default lowest transmuted grade
        foreach ($transmutationTable as $lowerBound => $grade) {
            if ($initialGrade >= $lowerBound) {
                $transmutedGrade = $grade;
                break; // Since keys are ordered descending
            }
        }

        return $transmutedGrade;
    }

    /**
     * Get SHS Grade Descriptor.
     */
    public function getShsGradeDescriptor(?int $transmutedGrade): string
    {
        if ($transmutedGrade === null) {
            return 'N/A';
        }

        return match (true) {
            $transmutedGrade >= 90 => 'Outstanding',
            $transmutedGrade >= 85 => 'Very Satisfactory',
            $transmutedGrade >= 80 => 'Satisfactory',
            $transmutedGrade >= 75 => 'Fairly Satisfactory',
            default => 'Did Not Meet Expectations',
        };
    }

    /**
     * Get Tailwind CSS color class for SHS Transmuted Grade.
     */
    public function getShsGradeColor(?int $transmutedGrade): string
    {
        if ($transmutedGrade === null) {
            return 'text-gray-400 dark:text-gray-500';
        }

        return match (true) {
            $transmutedGrade >= 90 => 'text-success-600 dark:text-success-400',
            $transmutedGrade >= 85 => 'text-primary-600 dark:text-primary-400', // Using Primary for Very Satisfactory
            $transmutedGrade >= 80 => 'text-info-600 dark:text-info-400', // Using Info for Satisfactory
            $transmutedGrade >= 75 => 'text-warning-600 dark:text-warning-400',
            default => 'text-danger-600 dark:text-danger-400',
        };
    }

    // --- College Grading ---

    /**
     * Calculate College GWA based on activity scores, units, and scale.
     */
    public function calculateCollegeGwa(
        array $studentScores,
        Collection $activities,
        ?string $collegeScale
    ): ?float {
        $details = $this->calculateCollegeGwaDetails(
            $studentScores,
            $activities,
            $collegeScale
        );

        return $details['gwa']; // Return the calculated GWA from details
    }

    /**
     * Calculate College GWA and related details.
     */
    public function calculateCollegeGwaDetails(
        array $studentScores,
        Collection $activities,
        ?string $collegeScale
    ): array {
        if (! $collegeScale) {
            Log::warning('College grading scale not set.');

            return [
                'gwa' => null,
                'total_units' => 0,
                'weighted_grade_sum' => 0,
                'activity_grades' => [],
            ];
        }

        $weightedGradeSum = 0;
        $totalUnits = 0;
        $activityGrades = []; // Store the grade used for each activity in the calculation

        foreach ($activities as $activity) {
            $score = $studentScores[$activity->id] ?? null;
            $units = (float) ($activity->credit_units ?? 0);

            // Only include activities with scores and positive units
            if ($score !== null && $units > 0 && $activity->total_points > 0) {
                $percentage = round(
                    ((float) $score / (float) $activity->total_points) * 100,
                    2
                );
                $percentage = min(max($percentage, 0), 100); // Clamp between 0 and 100

                // Convert percentage to the specified college scale grade
                $scaleGrade = $this->convertPercentageToCollegeScale(
                    $percentage,
                    $collegeScale
                );

                if ($scaleGrade !== null) {
                    $weightedGradeSum += $scaleGrade * $units;
                    $totalUnits += $units;
                    $activityGrades[$activity->id] = [
                        'scale_grade' => $scaleGrade,
                        'percentage' => $percentage,
                        'units' => $units,
                    ];
                }
            } elseif (
                $score !== null &&
                $units > 0 &&
                $activity->total_points <= 0
            ) {
                Log::warning(
                    "Activity ID {$activity->id} included in GWA calc has score but zero/negative total_points."
                );
            }
        }

        $gwa = null;
        if ($totalUnits > 0) {
            $gwa = round($weightedGradeSum / $totalUnits, 2); // Standard GWA is usually 2 decimal places
        }

        return [
            'gwa' => $gwa,
            'total_units' => $totalUnits,
            'weighted_grade_sum' => $weightedGradeSum,
            'activity_grades' => $activityGrades,
        ];
    }

    /**
     * Calculate the grade for a specific college term (Prelim, Midterm, Final).
     * Applies Written Work (WW) and Performance Task (PT) weights if provided,
     * then converts the final term percentage to the specified college scale.
     */
    public function calculateCollegeTermGrade(
        array $studentScores,
        Collection $termActivities, // Activities ALREADY filtered for the specific term
        ?int $wwWeight, // Weight for Written Work within the term (e.g., 40)
        ?int $ptWeight, // Weight for Performance Task within the term (e.g., 60)
        ?string $numericScale // e.g., '5_point', '4_point', 'percentage'
    ): ?float {
        if (! $numericScale || $termActivities->isEmpty()) {
            return null;
        }

        // Check if term weights are provided and valid
        $useTermComponentWeights = $wwWeight !== null && $ptWeight !== null && ($wwWeight + $ptWeight === 100);

        $wwTotalPercentageSum = 0;
        $wwScoredCount = 0;
        $ptTotalPercentageSum = 0;
        $ptScoredCount = 0;
        $otherTotalPercentageSum = 0; // For activities not categorized as WW or PT
        $otherScoredCount = 0;

        foreach ($termActivities as $activity) {
            $score = $studentScores[$activity->id] ?? null;

            if ($score === null || $activity->total_points <= 0) {
                if ($score !== null) {
                    Log::warning(
                        "Activity ID {$activity->id} in term calc has score but zero/negative total_points."
                    );
                }
                continue; // Skip activities without scores or valid points
            }

            $percentage = round(
                ((float) $score / (float) $activity->total_points) * 100,
                2
            );
            $percentage = min(max($percentage, 0), 100); // Clamp 0-100

            if ($useTermComponentWeights) {
                // Use string comparison for category
                if ($activity->category === 'written') {
                    $wwTotalPercentageSum += $percentage;
                    $wwScoredCount++;
                } elseif ($activity->category === 'performance') {
                    $ptTotalPercentageSum += $percentage;
                    $ptScoredCount++;
                } else {
                    // Activities without WW/PT category are ignored when weights are active.
                     Log::debug("Activity ID {$activity->id} ignored in weighted term calc (category: {$activity->category})");
                }
            } else {
                // If not using term weights, average all activities together
                $otherTotalPercentageSum += $percentage;
                $otherScoredCount++;
            }
        }

        $finalTermPercentage = null;

        if ($useTermComponentWeights) {
            $wwAverage = $wwScoredCount > 0 ? ($wwTotalPercentageSum / $wwScoredCount) : 0;
            $ptAverage = $ptScoredCount > 0 ? ($ptTotalPercentageSum / $ptScoredCount) : 0;

            // Calculate weighted average only if there are scores in weighted categories
            if ($wwScoredCount > 0 || $ptScoredCount > 0) {
                $finalTermPercentage = ($wwAverage * ($wwWeight / 100)) + ($ptAverage * ($ptWeight / 100));
            } else {
                 Log::debug("No WW or PT scores found for weighted term calculation.");
            }
        } else {
            // Use simple average if term component weights are not used
            if ($otherScoredCount > 0) {
                $finalTermPercentage = $otherTotalPercentageSum / $otherScoredCount;
            } else {
                 Log::debug("No scores found for simple average term calculation.");
            }
        }

        if ($finalTermPercentage === null) {
            return null; // No scorable activities found for the term calculation
        }

        $finalTermPercentage = round($finalTermPercentage, 2);

        // Convert the final term percentage to the required numeric scale
        return $this->convertPercentageToCollegeScale(
            $finalTermPercentage,
            $numericScale
        );
    }


    /**
     * Calculate the Final Final Grade for the College Term-Based system.
     * This now considers weights for WW/PT *within* each term.
     */
    public function calculateCollegeFinalFinalGrade(
        array $studentScores,
        Collection $allActivities,
        ?int $prelimWeight, // Overall term weight
        ?int $midtermWeight, // Overall term weight
        ?int $finalWeight, // Overall term weight
        ?int $termWwWeight, // WW weight WITHIN each term
        ?int $termPtWeight, // PT weight WITHIN each term
        ?string $numericScale // e.g., '5_point', '4_point', 'percentage'
    ): array { // Return array: ['final_grade' => float|null, 'term_grades' => [...]]

        $weightsConfigured = $numericScale !== null &&
                             $prelimWeight !== null &&
                             $midtermWeight !== null &&
                             $finalWeight !== null &&
                             ($prelimWeight + $midtermWeight + $finalWeight === 100);

        // Term component weights are optional but must sum to 100 if both are set
        $termComponentWeightsValid = ($termWwWeight === null && $termPtWeight === null) ||
                                     ($termWwWeight !== null && $termPtWeight !== null && ($termWwWeight + $termPtWeight === 100));

        if (! $weightsConfigured || ! $termComponentWeightsValid) {
            Log::warning(
                'College term weights (overall or component) or scale not configured correctly.',
                [
                    'overall_weights_ok' => $weightsConfigured,
                    'component_weights_ok' => $termComponentWeightsValid,
                    'scale' => $numericScale,
                    'prelim_w' => $prelimWeight, 'midterm_w' => $midtermWeight, 'final_w' => $finalWeight,
                    'term_ww_w' => $termWwWeight, 'term_pt_w' => $termPtWeight
                ]
            );
            return ['final_grade' => null, 'term_grades' => []];
        }

        $termGrades = [];
        $validTermWeights = []; // Store weights only for terms with calculated grades

        // --- Calculate Prelim Grade --- 
        $prelimActivities = $allActivities->where(
            'term',
            Activity::TERM_PRELIM
        );
        $prelimGrade = $this->calculateCollegeTermGrade(
            $studentScores,
            $prelimActivities,
            $termWwWeight, // Pass term WW weight
            $termPtWeight, // Pass term PT weight
            $numericScale
        );
        $termGrades[Activity::TERM_PRELIM] = $prelimGrade;
        if ($prelimGrade !== null) {
            $validTermWeights[Activity::TERM_PRELIM] = $prelimWeight;
        }

        // --- Calculate Midterm Grade --- 
        $midtermActivities = $allActivities->where(
            'term',
            Activity::TERM_MIDTERM
        );
        $midtermGrade = $this->calculateCollegeTermGrade(
            $studentScores,
            $midtermActivities,
            $termWwWeight, // Pass term WW weight
            $termPtWeight, // Pass term PT weight
            $numericScale
        );
        $termGrades[Activity::TERM_MIDTERM] = $midtermGrade;
        if ($midtermGrade !== null) {
            $validTermWeights[Activity::TERM_MIDTERM] = $midtermWeight;
        }

        // --- Calculate Final Term Grade --- 
        $finalTermActivities = $allActivities->where(
            'term',
            Activity::TERM_FINAL
        );
        $finalTermGrade = $this->calculateCollegeTermGrade(
            $studentScores,
            $finalTermActivities,
            $termWwWeight, // Pass term WW weight
            $termPtWeight, // Pass term PT weight
            $numericScale
        );
        $termGrades[Activity::TERM_FINAL] = $finalTermGrade;
        if ($finalTermGrade !== null) {
            $validTermWeights[Activity::TERM_FINAL] = $finalWeight;
        }

        // --- Calculate Final Final Grade --- 
        $finalFinalGrade = null;
        $totalValidWeight = array_sum($validTermWeights);

        if ($totalValidWeight > 0) {
            $weightedSum = 0;
            foreach ($validTermWeights as $term => $weight) {
                // Adjust weight proportionally based on available terms
                $adjustedWeight = ($weight / $totalValidWeight);
                $weightedSum += $termGrades[$term] * $adjustedWeight;
            }
            // Rounding might depend on scale type (e.g., more precision for 5/4 point)
            $finalFinalGrade = round($weightedSum, 2);
            // Clamp based on scale (important for 5/4 point scales)
            if ($numericScale === '5_point') {
                 $finalFinalGrade = max(1.00, min(5.00, $finalFinalGrade));
            } elseif ($numericScale === '4_point') {
                 $finalFinalGrade = max(0.00, min(4.00, $finalFinalGrade));
            }
            // Add more clamps if other scales are introduced

        } else {
            Log::info('No valid term grades to calculate final grade.');
        }

        return ['final_grade' => $finalFinalGrade, 'term_grades' => $termGrades];
    }


    /**
     * Convert a percentage score to a college grade based on the numeric scale.
     * (Make sure this handles '5_point', '4_point', 'percentage')
     */
    public function convertPercentageToCollegeScale(
        float $percentage,
        string $numericScale
    ): ?float {
        $percentage = max(0, min($percentage, 100));

        switch ($numericScale) {
            // Use the extracted numeric scale
            case '5_point':
                // Example 5-point scale (adjust as needed)
                return match (true) {
                    $percentage >= 98 => 1.0,
                    $percentage >= 95 => 1.25,
                    $percentage >= 92 => 1.5,
                    $percentage >= 89 => 1.75,
                    $percentage >= 86 => 2.0,
                    $percentage >= 83 => 2.25,
                    $percentage >= 80 => 2.5,
                    $percentage >= 77 => 2.75,
                    $percentage >= 75 => 3.0,
                    default => 5.0,
                };

            case '4_point':
                 // Example 4-point scale (adjust as needed)
                return match (true) {
                    $percentage >= 93 => 4.0,
                    $percentage >= 90 => 3.7,
                    $percentage >= 87 => 3.3,
                    $percentage >= 83 => 3.0,
                    $percentage >= 80 => 2.7,
                    $percentage >= 77 => 2.3,
                    $percentage >= 73 => 2.0,
                    $percentage >= 70 => 1.7,
                    $percentage >= 67 => 1.3,
                    $percentage >= 63 => 1.0,
                    $percentage >= 60 => 0.7,
                    default => 0.0,
                };

            case 'percentage':
                return round($percentage, 2);

            default:
                Log::error(
                    "Unsupported college numeric scale: {$numericScale}"
                );

                return null;
        }
    }

    /**
     * Format college grade for display (Handles 5-point, 4-point, percentage).
     */
    public function formatCollegeGrade(
        ?float $gradeValue,
        ?string $numericScale,
        bool $raw = false // Add raw flag to omit '%' for exports
    ): string {
        if ($gradeValue === null || ! $numericScale) {
            return 'N/A';
        }

        switch ($numericScale) {
            case '5_point':
            case '4_point':
                return number_format($gradeValue, 2);
            case 'percentage':
                return number_format($gradeValue, 2).($raw ? '' : '%');
            default:
                return 'N/A';
        }
    }

    /**
     * Get Tailwind CSS color class for College Grade (Handles 5-point, 4-point, percentage).
     */
    public function getCollegeGradeColor(
        ?float $gradeValue,
        ?string $numericScale
    ): string {
        if ($gradeValue === null || ! $numericScale) {
            return 'text-gray-400 dark:text-gray-500';
        }

        switch ($numericScale) {
            case '5_point':
                // Adjust colors based on typical 5-point scale (lower is better)
                return match (true) {
                    $gradeValue <= 1.5 => 'text-success-600 dark:text-success-400',
                    $gradeValue <= 2.0 => 'text-primary-600 dark:text-primary-400',
                    $gradeValue <= 2.5 => 'text-info-600 dark:text-info-400',
                    $gradeValue <= 3.0 => 'text-warning-600 dark:text-warning-400',
                    default => 'text-danger-600 dark:text-danger-400',
                };
            case '4_point':
                 // Adjust colors based on typical 4-point scale (higher is better)
                return match (true) {
                    $gradeValue >= 3.7 => 'text-success-600 dark:text-success-400',
                    $gradeValue >= 3.0 => 'text-primary-600 dark:text-primary-400',
                    $gradeValue >= 2.0 => 'text-info-600 dark:text-info-400',
                    $gradeValue >= 1.0 => 'text-warning-600 dark:text-warning-400',
                    default => 'text-danger-600 dark:text-danger-400',
                };
            case 'percentage':
                // Keep existing percentage logic
                return match (true) {
                    $gradeValue >= 90 => 'text-success-600 dark:text-success-400',
                    $gradeValue >= 80 => 'text-primary-600 dark:text-primary-400',
                    $gradeValue >= 75 => 'text-warning-600 dark:text-warning-400', // Example passing threshold
                    default => 'text-danger-600 dark:text-danger-400',
                };
            default:
                return 'text-gray-400 dark:text-gray-500';
        }
    }
}
