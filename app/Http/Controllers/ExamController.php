<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Exam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use Illuminate\Support\Str;

class ExamController extends Controller
{
    /**
     * Export an exam to PDF or DOCX format.
     */
    public function export(Request $request, Exam $exam)
    {
        // Check if user has access to this exam
        if ($exam->team_id !== Auth::user()->currentTeam->id && $exam->teacher_id !== Auth::id()) {
            abort(403, 'You do not have permission to access this exam.');
        }

        // Get format and include answer key options
        $format = $request->input('format', 'pdf');
        $includeAnswerKey = (bool) $request->input('include_answer_key', false);

        // Generate the exam content
        $examData = [
            'exam' => $exam,
            'includeAnswerKey' => $includeAnswerKey,
        ];

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('exports.exam', $examData);
            return $pdf->download("exam_{$exam->id}.pdf");
        } elseif ($format === 'docx') {
            // Create a new Word document
            $phpWord = new PhpWord();
            $section = $phpWord->addSection();
            
            // Add exam title
            $section->addTitle($exam->title, 1);
            
            // Add exam description
            $section->addText(strip_tags($exam->description));
            
            // Add total points
            $section->addText("Total Points: {$exam->total_points}");
            
            // Add questions
            foreach ($exam->questions as $index => $question) {
                $section->addTitle("Question " . ($index + 1) . " ({$question->pivot->points} points)", 2);
                $section->addText(strip_tags($question->content));
                
                // Add options for multiple choice questions
                if ($question->options) {
                    $options = explode("\n", $question->options);
                    foreach ($options as $optionIndex => $option) {
                        $section->addListItem(trim($option), 0);
                    }
                }
                
                // Add answer if answer key is included
                if ($includeAnswerKey && $question->answer) {
                    $section->addText("Answer: {$question->answer}", ['bold' => true]);
                }
                
                // Add space between questions
                $section->addTextBreak(1);
            }
            
            // Save the document to a temporary file
            $tempFile = storage_path("app/temp/exam_{$exam->id}_" . Str::random(8) . ".docx");
            $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
            $objWriter->save($tempFile);
            
            // Return the document as a download
            return response()->download($tempFile, "exam_{$exam->id}.docx")->deleteFileAfterSend(true);
        }
        
        return back()->with('error', 'Unsupported export format.');
    }

    /**
     * Export multiple exams as a ZIP file.
     */
    public function exportBulk(Request $request)
    {
        $ids = $request->input('ids', []);
        $exams = Exam::whereIn('id', $ids)
            ->where(function ($query) {
                $query->where('team_id', Auth::user()->currentTeam->id)
                    ->orWhere('teacher_id', Auth::id());
            })
            ->get();

        if ($exams->isEmpty()) {
            return back()->with('error', 'No exams found to export.');
        }

        // Create a ZIP file
        $zipFileName = 'exams_export_' . date('Y-m-d_H-i-s') . '.zip';
        $zipFilePath = storage_path("app/temp/{$zipFileName}");
        
        $zip = new \ZipArchive();
        if ($zip->open($zipFilePath, \ZipArchive::CREATE) !== true) {
            return back()->with('error', 'Could not create ZIP file.');
        }

        foreach ($exams as $exam) {
            // Generate PDF for each exam
            $pdf = Pdf::loadView('exports.exam', ['exam' => $exam, 'includeAnswerKey' => false]);
            $pdfPath = storage_path("app/temp/exam_{$exam->id}.pdf");
            $pdf->save($pdfPath);
            
            // Add PDF to ZIP
            $zip->addFile($pdfPath, "exam_{$exam->id}.pdf");
        }
        
        $zip->close();
        
        // Clean up temporary PDF files
        foreach ($exams as $exam) {
            @unlink(storage_path("app/temp/exam_{$exam->id}.pdf"));
        }
        
        return response()->download($zipFilePath, $zipFileName)->deleteFileAfterSend(true);
    }
}
