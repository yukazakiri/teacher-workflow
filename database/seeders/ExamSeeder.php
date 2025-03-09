<?php

namespace Database\Seeders;

use App\Models\Exam;
use App\Models\Question;
use App\Models\ExamQuestion;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ExamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first user to be the teacher
        $user = User::first();
        $team = $user->currentTeam;

        if (!$user || !$team) {
            $this->command->error('No user or team found. Please run the user seeder first.');
            return;
        }

        // Create 5 exams
        for ($i = 1; $i <= 5; $i++) {
            $this->createExam($user, $team, $i);
        }

        $this->command->info('Exams have been seeded successfully!');
    }

    /**
     * Create an exam with random questions
     */
    protected function createExam(User $user, Team $team, int $index): void
    {
        // Begin a database transaction
        DB::transaction(function () use ($user, $team, $index) {
            // Create the exam
            $exam = Exam::create([
                'teacher_id' => $user->id,
                'team_id' => $team->id,
                'title' => "Sample Exam {$index}: " . $this->getRandomExamTitle(),
                'description' => $this->getRandomExamDescription(),
                'status' => Arr::random(['draft', 'published', 'archived'], 1)[0],
                'total_points' => 0, // Will calculate this after adding questions
            ]);

            // Generate a random number of questions (between 5 and 10)
            $numQuestions = rand(5, 10);
            $totalPoints = 0;
            $order = 1;

            // Create questions for this exam
            for ($i = 1; $i <= $numQuestions; $i++) {
                // Randomly select a question type
                $questionType = Arr::random([
                    'multiple_choice',
                    'true_false',
                    'short_answer',
                    'essay',
                    'matching',
                    'fill_in_blank'
                ], 1)[0];

                // Create question based on type
                $question = $this->createQuestion($user, $exam, $questionType, $i);

                // Add to total points
                $totalPoints += $question->points;

                // Create exam_question pivot entry
                ExamQuestion::create([
                    'exam_id' => $exam->id,
                    'question_id' => $question->id,
                    'order' => $order,
                    'points' => $question->points
                ]);

                $order++;
            }

            // Update the exam's total points
            $exam->update(['total_points' => $totalPoints]);
        });
    }

    /**
     * Create a question of a specific type
     */
    protected function createQuestion(User $user, Exam $exam, string $type, int $index): Question
    {
        $points = $this->getRandomPoints($type);
        $content = $this->getRandomQuestionContent($type, $index);

        $questionData = [
            'teacher_id' => $user->id,
            'exam_id' => $exam->id,
            'team_id' => $exam->team_id,
            'type' => $type,
            'content' => $content,
            'points' => $points,
        ];

        // Add type-specific data
        switch ($type) {
            case 'multiple_choice':
                $choices = $this->generateMultipleChoiceOptions();
                $correct = array_rand($choices);
                $questionData['choices'] = $choices;
                $questionData['correct_answer'] = $correct;
                $questionData['explanation'] = "The correct answer is {$correct}. " . $this->getRandomExplanation();
                break;

            case 'true_false':
                $correct = Arr::random(['true', 'false'], 1)[0];
                $questionData['correct_answer'] = $correct;
                $questionData['explanation'] = "The statement is {$correct}. " . $this->getRandomExplanation();
                break;

            case 'short_answer':
                $questionData['correct_answer'] = $this->getRandomShortAnswer();
                $questionData['explanation'] = $this->getRandomExplanation();
                break;

            case 'essay':
                $questionData['rubric'] = $this->getRandomRubric();
                $questionData['word_limit'] = rand(200, 1000);
                break;

            case 'matching':
                $questionData['matching_pairs'] = $this->generateMatchingPairs();
                break;

            case 'fill_in_blank':
                $blanks = rand(1, 3);
                $answers = [];
                for ($i = 1; $i <= $blanks; $i++) {
                    $answers["blank{$i}"] = $this->getRandomWord();
                }
                $questionData['answers'] = $answers;
                break;
        }

        return Question::create($questionData);
    }

    /**
     * Generate random multiple choice options
     */
    protected function generateMultipleChoiceOptions(): array
    {
        $options = [];
        $letters = ['A', 'B', 'C', 'D'];

        foreach ($letters as $letter) {
            $options[$letter] = "Option {$letter}: " . $this->getRandomSentence(rand(1, 2));
        }

        return $options;
    }

    /**
     * Generate random matching pairs
     */
    protected function generateMatchingPairs(): array
    {
        $pairs = [];
        $terms = [
            'Photosynthesis', 'Mitosis', 'Meiosis', 'Osmosis', 'Diffusion'
        ];
        $definitions = [
            'Process where plants convert light energy to chemical energy',
            'Cell division resulting in two identical daughter cells',
            'Cell division resulting in four haploid cells',
            'Movement of water molecules across a semipermeable membrane',
            'Movement of molecules from high to low concentration'
        ];

        // Shuffle definitions to make it more random
        shuffle($definitions);

        $count = min(count($terms), count($definitions));
        for ($i = 0; $i < $count; $i++) {
            $pairs[$terms[$i]] = $definitions[$i];
        }

        return $pairs;
    }

    /**
     * Get random points based on question type
     */
    protected function getRandomPoints(string $type): int
    {
        switch ($type) {
            case 'essay':
                return rand(5, 20);
            case 'matching':
                return rand(3, 8);
            default:
                return rand(1, 5);
        }
    }

    /**
     * Get random content for a question based on its type
     */
    protected function getRandomQuestionContent(string $type, int $index): string
    {
        switch ($type) {
            case 'multiple_choice':
                return "Multiple choice question #{$index}: " . $this->getRandomSentence(2) . "?";

            case 'true_false':
                return "True/False question #{$index}: " . $this->getRandomSentence(2) . ".";

            case 'short_answer':
                return "Short answer question #{$index}: " . $this->getRandomSentence(2) . "?";

            case 'essay':
                return "Essay question #{$index}: " . $this->getRandomSentence(4) . " " . $this->getRandomSentence(3) . " Discuss in detail.";

            case 'matching':
                return "Matching question #{$index}: Match each term with its correct definition.";

            case 'fill_in_blank':
                $sentence = $this->getRandomSentence(3);
                $words = explode(' ', $sentence);
                $totalWords = count($words);

                $blanks = min(rand(1, 3), $totalWords - 2);
                for ($i = 0; $i < $blanks; $i++) {
                    $position = rand(2, $totalWords - 1);
                    $words[$position] = '[blank]';
                }

                return "Fill in the blank question #{$index}: " . implode(' ', $words);

            default:
                return "Question #{$index}: " . $this->getRandomSentence(2) . "?";
        }
    }

    /**
     * Get a random exam title
     */
    protected function getRandomExamTitle(): string
    {
        $subjects = ['Math', 'Science', 'English', 'History', 'Geography', 'Physics', 'Chemistry', 'Biology'];
        $types = ['Quiz', 'Test', 'Exam', 'Assessment', 'Evaluation'];
        $levels = ['Basic', 'Intermediate', 'Advanced', 'Final', 'Mid-term'];

        $subject = Arr::random($subjects, 1)[0];
        $type = Arr::random($types, 1)[0];
        $level = Arr::random($levels, 1)[0];

        return "{$level} {$subject} {$type}";
    }

    /**
     * Get a random exam description
     */
    protected function getRandomExamDescription(): string
    {
        $descriptions = [
            "This exam covers the fundamental concepts and principles of the subject. Students should review all class materials before attempting.",
            "A comprehensive assessment designed to test understanding of key topics and application of knowledge.",
            "This evaluation will assess critical thinking and problem-solving skills related to the course material.",
            "An in-depth examination of core concepts. Students will need to demonstrate analytical skills and detailed knowledge.",
            "This assessment focuses on practical applications and theoretical understanding of the subject material."
        ];

        return Arr::random($descriptions, 1)[0];
    }

    /**
     * Get a random explanation for correct answers
     */
    protected function getRandomExplanation(): string
    {
        $explanations = [
            "This is the correct answer because it aligns with the principles we discussed in class.",
            "The other options are incorrect because they misrepresent key concepts of the subject.",
            "This option accurately reflects the relationship between the variables in question.",
            "You can verify this answer by referring to chapter 3 in your textbook.",
            "Remember the formula we learned that helps solve this type of problem."
        ];

        return Arr::random($explanations, 1)[0];
    }

    /**
     * Get a random short answer
     */
    protected function getRandomShortAnswer(): string
    {
        $answers = [
            "The water cycle",
            "Photosynthesis",
            "The law of supply and demand",
            "Newton's third law of motion",
            "The Pythagorean theorem",
            "Cellular respiration",
            "The Industrial Revolution",
            "Democracy"
        ];

        return Arr::random($answers, 1)[0];
    }

    /**
     * Get a random rubric for essay questions
     */
    protected function getRandomRubric(): string
    {
        return "<ul>
            <li><strong>Content (40%):</strong> Demonstrates thorough understanding of the topic.</li>
            <li><strong>Analysis (30%):</strong> Shows critical thinking and insightful analysis.</li>
            <li><strong>Organization (15%):</strong> Well-structured with clear introduction, body, and conclusion.</li>
            <li><strong>Language (15%):</strong> Uses appropriate vocabulary, grammar, and academic style.</li>
        </ul>";
    }

    /**
     * Generate a random sentence
     */
    protected function getRandomSentence(int $length = 1): string
    {
        $sentences = [
            "The quick brown fox jumps over the lazy dog",
            "Students must understand the core concepts before proceeding",
            "The scientific method involves observation, hypothesis, experimentation, and conclusion",
            "Critical thinking is essential for academic success",
            "Mathematical principles can be applied to solve real-world problems",
            "Historical events should be analyzed within their proper context",
            "Atoms combine to form molecules through chemical bonding",
            "Literature reflects the cultural values of its time period",
            "Geographic features influence human settlement patterns",
            "Economic theories attempt to explain market behaviors",
            "Computer algorithms process data to solve complex problems",
            "Biological systems maintain homeostasis through feedback mechanisms",
            "Environmental factors impact ecosystem development",
            "Political systems vary in their distribution of power",
            "Psychological studies examine human behavior and mental processes"
        ];

        $result = [];
        for ($i = 0; $i < $length; $i++) {
            $result[] = Arr::random($sentences, 1)[0];
        }

        return implode(' ', $result);
    }

    /**
     * Get a random word for fill-in-the-blank questions
     */
    protected function getRandomWord(): string
    {
        $words = [
            'photosynthesis', 'mitochondria', 'democracy', 'equation', 'molecule',
            'civilization', 'revolution', 'environment', 'algorithm', 'parallelogram',
            'coefficient', 'hypothesis', 'chromosome', 'ecosystem', 'precipitation'
        ];

        return Arr::random($words, 1)[0];
    }
}
