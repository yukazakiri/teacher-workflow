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
        
        if (!$question || !$question->answer_key) {
            return false;
        }

        // This is a simplified check. In a real application, you would implement
        // more sophisticated logic based on the question type
        return strtolower(trim($this->response)) === strtolower(trim($question->answer_key));
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
                'feedback' => 'Incorrect answer. The correct answer is: ' . $question->answer_key
            ]);
        }
    }
}
