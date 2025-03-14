<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamQuestionResponse extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'exam_submission_id',
        'question_id',
        'response',
        'score',
        'feedback',
    ];

    /**
     * Get the submission that owns the response.
     */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(ExamSubmission::class, 'exam_submission_id');
    }

    /**
     * Get the question that owns the response.
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    /**
     * Check if the response is correct based on the question's answer key.
     */
    public function isCorrect(): bool
    {
        $question = $this->question;

        if (!$question) {
            return false;
        }

        // Get the correct answer based on question type
        $correctAnswer = null;

        if (is_object($question->correct_answer) && method_exists($question->correct_answer, 'offsetGet')) {
            $correctAnswerArray = $question->correct_answer->getArrayCopy();
            $correctAnswer = !empty($correctAnswerArray) ? $correctAnswerArray[0] : null;
        } else if (is_array($question->correct_answer)) {
            $correctAnswer = !empty($question->correct_answer) ? $question->correct_answer[0] : null;
        } else {
            $correctAnswer = $question->correct_answer;
        }

        if (!$correctAnswer) {
            return false;
        }

        // Check based on question type
        switch ($question->type) {
            case 'multiple_choice':
            case 'true_false':
                return strtolower(trim($this->response)) === strtolower(trim($correctAnswer));

            case 'short_answer':
                // More flexible matching for short answers
                return strtolower(trim($this->response)) === strtolower(trim($correctAnswer));

            default:
                // For other question types, manual grading is required
                return false;
        }
    }

    /**
     * Auto-grade the response based on the question's answer key.
     */
    public function autoGrade(): void
    {
        $question = $this->question;

        if (!$question) {
            return;
        }

        // Only auto-grade question types that can be automatically graded
        if (in_array($question->type, ['multiple_choice', 'true_false', 'short_answer'])) {
            // Get the correct answer
            $correctAnswer = null;

            if (is_object($question->correct_answer) && method_exists($question->correct_answer, 'offsetGet')) {
                $correctAnswerArray = $question->correct_answer->getArrayCopy();
                $correctAnswer = !empty($correctAnswerArray) ? $correctAnswerArray[0] : null;
            } else if (is_array($question->correct_answer)) {
                $correctAnswer = !empty($question->correct_answer) ? $question->correct_answer[0] : null;
            } else {
                $correctAnswer = $question->correct_answer;
            }

            // If the response is correct, assign full points
            if ($this->isCorrect()) {
                $this->update([
                    'score' => $question->points,
                    'feedback' => 'Correct answer!'
                ]);
            } else {
                // If incorrect, assign 0 points
                $this->update([
                    'score' => 0,
                    'feedback' => 'Incorrect answer. The correct answer is: ' . $correctAnswer
                ]);
            }
        }
    }
}
