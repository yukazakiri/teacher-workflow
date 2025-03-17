<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Casts\AsCollection;

class Question extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
     protected $fillable = [
         'teacher_id',
         'exam_id',
         'team_id',
         'question_type_id',
         'type',
         'content',
         'choices',
         'correct_answer',
         'explanation',
         'rubric',
         'word_limit',
         'matching_pairs',
         'answers',
         'points',
     ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'choices' => AsArrayObject::class,
        'correct_answer' => AsArrayObject::class,
        'matching_pairs' => AsArrayObject::class,
        'answers' => AsArrayObject::class,
        'points' => 'integer',
        'word_limit' => 'integer',
    ];

    public const TYPES = [
        'multiple_choice' => 'Multiple Choice',
        'true_false' => 'True/False',
        'short_answer' => 'Short Answer',
        'essay' => 'Essay',
        'matching' => 'Matching',
        'fill_in_blank' => 'Fill in the Blank',
    ];

    /**
     * Get the teacher that owns the question.
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * Get the exam that owns the question.
     */
     public function exam(): BelongsTo
     {
         return $this->belongsTo(Exam::class);
     }

     /**
      * Get the question type that owns the question.
      */
     public function questionType(): BelongsTo
     {
         return $this->belongsTo(QuestionType::class);
     }



    /**
     * Get the exam questions.
     */
    public function examQuestions(): HasMany
    {
        return $this->hasMany(ExamQuestion::class);
    }

    /**
     * Get the responses for the question.
     */
    public function responses(): HasMany
    {
        return $this->hasMany(ExamQuestionResponse::class);
    }
}
