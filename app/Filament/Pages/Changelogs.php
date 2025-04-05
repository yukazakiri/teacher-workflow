<?php

namespace App\Filament\Pages;

use Carbon\Carbon;
use Devonab\FilamentEasyFooter\Services\GitHubService;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class Changelogs extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.changelogs';

    protected static ?string $navigationLabel = 'Changelogs';

    protected static ?string $title = 'Release History & Changelogs';

    public array $releases = [];

    public ?string $currentVersion = null;

    public ?string $repository = null;

    public array $releaseStats = [];

    public array $releasesByYear = [];

    public ?string $selectedRelease = null;

    public ?string $latestReleaseDate = null;

    public int $totalReleases = 0;

    public int $majorReleases = 0;

    public int $minorReleases = 0;

    public int $patchReleases = 0;

    public function mount(): void
    {
        $this->repository = config('filament-easy-footer.github.repository');
        $this->currentVersion = $this->getCurrentVersion();
        $this->releases = $this->getReleases();
        $this->processReleaseData();
        $this->selectedRelease = $this->currentVersion;
    }

    protected function getCurrentVersion(): ?string
    {
        $githubService = app(GitHubService::class);

        return $githubService->getLatestTag($this->repository);
    }

    protected function getReleases(): array
    {
        $cacheKey = "filament-easy-footer.github.{$this->repository}.releases";
        $cacheTtl = config('filament-easy-footer.github.cache_ttl', 3600);

        return Cache::remember(
            $cacheKey,
            $cacheTtl,
            function () {
                try {
                    $token = config('filament-easy-footer.github.token');
                    $response = Http::when($token, fn ($request) => $request->withToken($token))
                        ->get("https://api.github.com/repos/{$this->repository}/releases");

                    if ($response->successful()) {
                        return $response->json();
                    }

                    return [];
                } catch (\Exception $e) {
                    report($e);

                    return [];
                }
            }
        );
    }

    protected function processReleaseData(): void
    {
        if (empty($this->releases)) {
            return;
        }

        $this->totalReleases = count($this->releases);
        $releasesByYear = [];

        foreach ($this->releases as $index => $release) {
            // Get release date
            $releaseDate = Carbon::parse($release['published_at']);
            $year = $releaseDate->format('Y');

            // Set latest release date
            if ($index === 0) {
                $this->latestReleaseDate = $releaseDate->format('F j, Y');
            }

            // Group by year
            if (! isset($releasesByYear[$year])) {
                $releasesByYear[$year] = [];
            }
            $releasesByYear[$year][] = $release;

            // Count release types (major, minor, patch)
            $version = $release['tag_name'];
            if (preg_match('/^v?(\d+)\.(\d+)\.(\d+)/', $version, $matches)) {
                if ($matches[3] === '0' && $matches[2] === '0') {
                    $this->majorReleases++;
                } elseif ($matches[3] === '0') {
                    $this->minorReleases++;
                } else {
                    $this->patchReleases++;
                }
            }
        }

        // Sort years in descending order
        krsort($releasesByYear);
        $this->releasesByYear = $releasesByYear;

        // Calculate release frequency
        if ($this->totalReleases > 1) {
            $firstReleaseDate = Carbon::parse(end($this->releases)['published_at']);
            $latestReleaseDate = Carbon::parse($this->releases[0]['published_at']);
            $daysBetween = $firstReleaseDate->diffInDays($latestReleaseDate);

            $this->releaseStats = [
                'average_days_between_releases' => $daysBetween > 0 ? round($daysBetween / ($this->totalReleases - 1), 1) : 0,
                'releases_per_month' => $daysBetween > 0 ? round(($this->totalReleases * 30) / $daysBetween, 1) : 0,
                'first_release_date' => $firstReleaseDate->format('F j, Y'),
            ];
        }
    }

    public function getSelectedReleaseData(): ?array
    {
        if (! $this->selectedRelease) {
            return null;
        }

        foreach ($this->releases as $release) {
            if ($release['tag_name'] === $this->selectedRelease) {
                return $release;
            }
        }

        return null;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
}
