<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Exam;
use App\Models\Question;
use App\Models\ExamQuestion;
use App\Models\Student;
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
        // Get the test user
        $user = User::where('email', 'test@example.com')->first();

        if (!$user) {
            $this->command->error('Test user not found. Please run the DatabaseSeeder first.');
            return;
        }

        // Get all teams for the teacher
        $teams = Team::where('user_id', $user->id)->get();

        if ($teams->isEmpty()) {
            $this->command->error('No teams found for the teacher. Please run DatabaseSeeder first.');
            return;
        }

        // Create exams for each team
        foreach ($teams as $team) {
            // Check if the team has students
            $studentCount = Student::where('team_id', $team->id)->count();
            if ($studentCount === 0) {
                $this->command->info("No students found in team {$team->name}. Skipping exam creation.");
                continue;
            }

            $this->createExamsForTeam($user, $team);
        }

        $this->command->info('Exams have been seeded successfully!');
    }

    /**
     * Create exams for a specific team
     */
    protected function createExamsForTeam(User $user, Team $team): void
    {
        // Create 3 exams per team
        for ($i = 1; $i <= 3; $i++) {
            $this->createExam($user, $team, $i);
        }
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

            // Create exam submissions for students if the exam is published
            if ($exam->isPublished()) {
                $this->createExamSubmissions($exam, $team);
            }
        });
    }

    /**
     * Create exam submissions for an exam.
     */
    protected function createExamSubmissions(Exam $exam, Team $team): void
    {
        // Get actual students from the database for this team
        $students = Student::where('team_id', $team->id)->get();

        if ($students->isEmpty()) {
            $this->command->info("No students found for team ID {$team->id}. Skipping submissions.");
            return;
        }

        // Create submissions for each student
        foreach ($students as $student) {
            // Generate random answers for the exam
            $answers = json_encode($this->generateRandomAnswers($exam));

            // Calculate a random score between 0 and 100
            $score = rand(0, 100);

            // Determine the final grade based on the score
            $finalGrade = $this->calculateFinalGrade($score);

            // For students without associated users, we need to handle differently
            if (!$student->user_id) {
                // For students without users, we'll create a submission directly
                // but we need to modify our database schema to allow this
                $this->command->info("Student {$student->id} has no associated user. Creating submission with teacher as student_id.");

                // Use the teacher's ID as the student_id for the submission
                // This is a workaround since the foreign key constraint requires a valid user_id
                $exam->submissions()->create([
                    'student_id' => $exam->teacher_id, // Use teacher ID as a placeholder
                    'status' => 'submitted',
                    'score' => $score,
                    'final_grade' => $finalGrade,
                    'feedback' => $this->generateFeedback($finalGrade),
                    'submitted_at' => now()->subDays(rand(1, 7)),
                    'graded_by' => $exam->teacher_id,
                    'graded_at' => now()->subDays(rand(0, 3)),
                    'answers' => $answers,
                ]);
                continue;
            }

            // Create the submission for students with associated users
            $exam->submissions()->create([
                'student_id' => $student->user_id,
                'status' => 'submitted',
                'score' => $score,
                'final_grade' => $finalGrade,
                'feedback' => $this->generateFeedback($finalGrade),
                'submitted_at' => now()->subDays(rand(1, 7)),
                'graded_by' => $exam->teacher_id,
                'graded_at' => now()->subDays(rand(0, 3)),
                'answers' => $answers,
            ]);
        }
    }

    /**
     * Generate random answers for an exam submission
     */
    protected function generateRandomAnswers(Exam $exam): array
    {
        $answers = [];
        $questions = $exam->questions;

        foreach ($questions as $question) {
            switch ($question->type) {
                case 'multiple_choice':
                    $choices = $question->choices;
                    // Convert ArrayObject to array if needed
                    if ($choices instanceof \Illuminate\Database\Eloquent\Casts\ArrayObject) {
                        $choices = $choices->toArray();
                    }
                    // Make sure we have choices before trying to get a random one
                    if (!empty($choices)) {
                        $choiceKeys = array_keys($choices);
                        $answers[$question->id] = $choiceKeys[array_rand($choiceKeys)];
                    } else {
                        $answers[$question->id] = 'A'; // Default to A if no choices
                    }
                    break;

                case 'true_false':
                    $answers[$question->id] = rand(0, 1) ? 'true' : 'false';
                    break;

                case 'short_answer':
                    // For short answer, we'll sometimes use the correct answer and sometimes a random one
                    $correctAnswer = null;
                    if (is_object($question->correct_answer) && method_exists($question->correct_answer, 'offsetGet')) {
                        $correctAnswerArray = $question->correct_answer->getArrayCopy();
                        $correctAnswer = !empty($correctAnswerArray) ? $correctAnswerArray[0] : null;
                    } else if (is_array($question->correct_answer)) {
                        $correctAnswer = !empty($question->correct_answer) ? $question->correct_answer[0] : null;
                    }

                    // 50% chance to use the correct answer
                    if ($correctAnswer && rand(0, 1)) {
                        $answers[$question->id] = $correctAnswer;
                    } else {
                        $answers[$question->id] = "Answer to question {$question->id}";
                    }
                    break;

                case 'essay':
                    $answers[$question->id] = $this->getRandomEssayAnswer();
                    break;

                case 'matching':
                    $pairs = $question->matching_pairs;
                    // Convert ArrayObject to array if needed
                    if ($pairs instanceof \Illuminate\Database\Eloquent\Casts\ArrayObject) {
                        $pairs = $pairs->toArray();
                    }

                    if (!empty($pairs)) {
                        $shuffledValues = array_values($pairs);
                        shuffle($shuffledValues);

                        $studentMatches = [];
                        $keys = array_keys($pairs);
                        foreach ($keys as $index => $key) {
                            $studentMatches[$key] = $shuffledValues[$index % count($shuffledValues)];
                        }
                        $answers[$question->id] = $studentMatches;
                    } else {
                        $answers[$question->id] = []; // Empty array if no pairs
                    }
                    break;

                case 'fill_in_blank':
                    $blanks = $question->answers;
                    // Convert ArrayObject to array if needed
                    if ($blanks instanceof \Illuminate\Database\Eloquent\Casts\ArrayObject) {
                        $blanks = $blanks->toArray();
                    }

                    $studentBlanks = [];
                    if (!empty($blanks)) {
                        foreach ($blanks as $key => $value) {
                            $studentBlanks[$key] = rand(0, 1) ? $value : $this->getRandomWord();
                        }
                    }
                    $answers[$question->id] = $studentBlanks;
                    break;
            }
        }

        return $answers;
    }

    /**
     * Create a question of a specific type
     */
    protected function createQuestion(User $user, Exam $exam, string $type, int $index): Question
    {
        $points = $this->getRandomPoints($type);
        $content = $this->getRandomQuestionContent($type, $index);

        // Get the question type ID from the database
        $questionType = DB::table('question_types')
            ->where('name', Question::TYPES[$type])
            ->first();

        if (!$questionType) {
            throw new \Exception("Question type '{$type}' not found in the database.");
        }

        $questionData = [
            'teacher_id' => $user->id,
            'exam_id' => $exam->id,
            'team_id' => $exam->team_id,
            'question_type_id' => $questionType->id,
            'type' => $type,
            'content' => $content,
            'points' => $points,
        ];

        // Add type-specific data
        switch ($type) {
            case 'multiple_choice':
                $choices = $this->generateMultipleChoiceOptions();
                $choiceKeys = array_keys($choices);
                $correct = $choiceKeys[array_rand($choiceKeys)];
                $questionData['choices'] = $choices;
                // Store correct_answer as an array to match the resource implementation
                $questionData['correct_answer'] = [$correct];
                $questionData['explanation'] = "The correct answer is {$correct}. " . $this->getRandomExplanation();
                break;

            case 'true_false':
                $correct = Arr::random(['true', 'false'], 1)[0];
                // Store correct_answer as an array to match the resource implementation
                $questionData['correct_answer'] = [$correct];
                $questionData['explanation'] = "The statement is {$correct}. " . $this->getRandomExplanation();
                break;

            case 'short_answer':
                $answer = $this->getRandomShortAnswer();
                // Store correct_answer as an array to match the resource implementation
                $questionData['correct_answer'] = [$answer];
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
     * Get a random essay answer
     */
    protected function getRandomEssayAnswer(): string
    {
        $paragraphs = [
            "The topic presents several important considerations that must be analyzed carefully. First, we must consider the historical context and how it has shaped our current understanding. Throughout history, various perspectives have emerged, each contributing to the complex tapestry of knowledge we now possess.",

            "When examining this subject from a scientific perspective, we can observe several patterns and principles at work. The evidence suggests a correlation between various factors, though causation remains a subject of debate among experts in the field. Recent studies have shed new light on previously held assumptions.",

            "From a philosophical standpoint, this raises questions about fundamental concepts such as truth, knowledge, and reality. Different schools of thought have approached these questions with varying methodologies and frameworks, leading to diverse conclusions that continue to influence contemporary discourse.",

            "In conclusion, while no single answer can fully address the complexity of this topic, a multidisciplinary approach offers the most comprehensive understanding. By integrating insights from various fields and remaining open to new evidence, we can continue to refine our knowledge and approach to this important subject."
        ];

        // Return 2-3 random paragraphs
        $selectedParagraphs = array_rand(array_flip($paragraphs), rand(2, 3));
        if (!is_array($selectedParagraphs)) {
            $selectedParagraphs = [$selectedParagraphs];
        }

        return implode("\n\n", $selectedParagraphs);
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

    /**
     * Get random feedback for graded submissions
     */
    protected function getRandomFeedback(): string
    {
        $feedbacks = [
            "Excellent work! Your understanding of the concepts is clear.",
            "Good job. There are a few areas that could use improvement.",
            "Satisfactory work, but please pay more attention to details.",
            "You've made some good points, but your analysis needs more depth.",
            "Well-structured and thoughtful. Keep up the good work!",
            "Your work shows promise, but needs more development in key areas.",
            "Very thorough analysis. I'm impressed with your attention to detail.",
            "You've met the basic requirements, but could have expanded more on your ideas.",
            "Strong start, but your conclusion needs more supporting evidence.",
            "Outstanding work! Your critical thinking skills are evident throughout."
        ];

        return Arr::random($feedbacks, 1)[0];
    }

    /**
     * Calculate final grade based on score
     */
    private function calculateFinalGrade(float $score): float
    {
        // Implement your logic to calculate final grade based on score
        // This is a placeholder and should be replaced with the actual implementation
        return $score;
    }

    /**
     * Generate feedback based on final grade
     */
    private function generateFeedback(float $finalGrade): string
    {
        // Implement your logic to generate feedback based on final grade
        // This is a placeholder and should be replaced with the actual implementation
        return $this->getRandomFeedback();
    }
}
