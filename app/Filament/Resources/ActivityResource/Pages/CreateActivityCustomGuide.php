<?php

namespace App\Filament\Resources\ActivityResource\Pages;

use App\Filament\Pages\ClassesResources;
use App\Filament\Resources\ActivityResource;
use App\Models\Team;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log; // Import Team model

class CreateActivityCustomGuide extends CreateRecord
{
    protected static string $resource = ActivityResource::class;

    protected static string $view = 'filament.resources.activity-resource.pages.create-activity-custom-guide';

    // State properties
    public bool $showGradingModalStage1 = false; // SHS vs College

    public bool $showGradingModalStage2 = false; // College Scale Selection

    public bool $showGuide = false;

    public ?Team $currentTeam = null;

    public function mount(): void
    {
        parent::mount();
        $user = Auth::user();
        $this->currentTeam = $user?->currentTeam;

        if (! $this->currentTeam) {
            Notification::make()
                ->title('Error')
                ->body('Cannot create activity without an active team.')
                ->danger()
                ->send();
            $this->halt();

            return;
        }

        // Check if grading system type is set
        if (empty($this->currentTeam->grading_system_type)) {
            $this->showGradingModalStage1 = true; // Show first modal
            $this->showGradingModalStage2 = false;
            $this->showGuide = false;
            Log::info(
                'Activity Create: Grading system type not set, showing Stage 1 modal.',
                ['user_id' => $user->id, 'team_id' => $this->currentTeam->id]
            );
        }
        // Check if type is College BUT scale is not set
        elseif (
            $this->currentTeam->grading_system_type ===
                Team::GRADING_SYSTEM_COLLEGE &&
            empty($this->currentTeam->college_grading_scale)
        ) {
            $this->showGradingModalStage1 = false;
            $this->showGradingModalStage2 = true; // Show second modal directly
            $this->showGuide = false;
            Log::info(
                'Activity Create: College type set but scale missing, showing Stage 2 modal.',
                ['user_id' => $user->id, 'team_id' => $this->currentTeam->id]
            );
        }
        // Grading system is fully set
        else {
            $this->showGradingModalStage1 = false;
            $this->showGradingModalStage2 = false;
            $this->showGuide = true; // Show guide panel
            Log::info(
                'Activity Create: Grading system fully set, showing guide panel.',
                [
                    'user_id' => $user->id,
                    'team_id' => $this->currentTeam->id,
                    'system' => $this->currentTeam->grading_system_type,
                    'scale' => $this->currentTeam->college_grading_scale ?? 'N/A',
                ]
            );
        }
    }

    // Action for selecting SHS in Stage 1
    public function selectShsSystem(): void
    {
        if (! $this->currentTeam) {
            return;
        }

        try {
            $this->currentTeam->grading_system_type = Team::GRADING_SYSTEM_SHS;
            // Clear potentially conflicting college fields
            $this->currentTeam->college_grading_scale = null;
            $this->currentTeam->college_prelim_weight = null;
            $this->currentTeam->college_midterm_weight = null;
            $this->currentTeam->college_final_weight = null;
            $this->currentTeam->save();

            Log::info('Grading system set to SHS.', [
                'user_id' => Auth::id(),
                'team_id' => $this->currentTeam->id,
            ]);
            Notification::make()
                ->title('Grading System Set!')
                ->body('You selected the K-12 SHS grading system.')
                ->success()
                ->send();

            $this->showGradingModalStage1 = false;
            $this->showGuide = true;
            $this->forceFormRefresh(); // Refresh form schema
        } catch (\Exception $e) {
            Log::error('Error setting SHS grading system.', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'team_id' => $this->currentTeam->id,
            ]);
            Notification::make()
                ->title('Error Saving Selection')
                ->danger()
                ->send();
        }
    }

    // Action for selecting College in Stage 1 (moves to Stage 2)
    public function selectCollegeSystem(): void
    {
        $this->showGradingModalStage1 = false;
        $this->showGradingModalStage2 = true; // Show the College scale modal
        Log::debug('Moving to Grading Modal Stage 2 (College Scale).', [
            'user_id' => Auth::id(),
            'team_id' => $this->currentTeam?->id,
        ]);
    }

    // Action for Stage 2 modal - selecting the College scale
    public function setCollegeScale(string $scale): void
    {
        if (! $this->currentTeam) {
            return;
        }

        // Validate scale
        $validScales = array_merge(
            Team::COLLEGE_GWA_SCALES,
            Team::COLLEGE_TERM_SCALES
        );
        if (! in_array($scale, $validScales)) {
            Notification::make()
                ->title('Invalid scale selected.')
                ->warning()
                ->send();
            Log::warning('Invalid college scale selection attempt.', [
                'scale' => $scale,
                'user_id' => Auth::id(),
                'team_id' => $this->currentTeam->id,
            ]);

            return;
        }

        try {
            $this->currentTeam->grading_system_type =
                Team::GRADING_SYSTEM_COLLEGE;
            $this->currentTeam->college_grading_scale = $scale;
            // Clear potentially conflicting SHS fields
            $this->currentTeam->shs_ww_weight = null;
            $this->currentTeam->shs_pt_weight = null;
            $this->currentTeam->shs_qa_weight = null;
            $this->currentTeam->save();

            Log::info('College grading scale set.', [
                'user_id' => Auth::id(),
                'team_id' => $this->currentTeam->id,
                'scale' => $scale,
            ]);
            Notification::make()
                ->title('Grading System Set!')
                ->body('College grading scale configured successfully.')
                ->success()
                ->send();

            $this->showGradingModalStage2 = false;
            $this->showGuide = true;
            $this->forceFormRefresh(); // Refresh form schema
        } catch (\Exception $e) {
            Log::error('Error setting college grading scale.', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'team_id' => $this->currentTeam->id,
            ]);
            Notification::make()
                ->title('Error Saving Selection')
                ->danger()
                ->send();
        }
    }

    // Action to go back from Stage 2 to Stage 1
    public function goBackToStage1(): void
    {
        $this->showGradingModalStage2 = false;
        $this->showGradingModalStage1 = true;
    }

    // Action to dismiss the guide panel
    public function dismissGuide(): void
    {
        $this->showGuide = false;
        Log::info('Activity form guide dismissed.', ['user_id' => Auth::id()]);
    }

    // No header actions needed
    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getViewData(): array
    {
        return array_merge(parent::getViewData(), [
            'showGradingModalStage1' => $this->showGradingModalStage1,
            'showGradingModalStage2' => $this->showGradingModalStage2,
            'showGuide' => $this->showGuide,
            'classesResourcesUrl' => ClassesResources::getUrl(),
            'teamName' => $this->currentTeam?->name ?? 'Current Class',
            // Pass constants for easy use in Blade
            'collegeGwaScales' => Team::COLLEGE_GWA_SCALES,
            'collegeTermScales' => Team::COLLEGE_TERM_SCALES,
        ]);
    }

    protected function getRedirectUrl(): string
    {
        Notification::make()
            ->title('Activity Created Successfully!')
            ->body('You can manage shared files in the Resources Hub.')
            ->success()
            ->seconds(8)
            ->send();

        return ClassesResources::getUrl();
    }

    // Helper to attempt form refresh
    protected function forceFormRefresh(): void
    {
        // Try filling form again, might work for simple visibility changes
        $this->form->fill();

        // More forceful refresh if needed - uncomment if fill() isn't enough
        // $this->dispatch('$refresh');
        // If schema changes drastically based on team settings, a full page redirect might
        // sometimes be the most robust way after saving, although less smooth UX.
        // $this->redirect(static::getUrl());
    }
}
