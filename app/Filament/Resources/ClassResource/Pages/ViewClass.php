<?php

namespace App\Filament\Resources\ClassResource\Pages;

use App\Filament\Resources\ClassResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Support\Facades\Storage;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid;
use Illuminate\Support\HtmlString;

class ViewClass extends ViewRecord
{
    protected static string $resource = ClassResource::class;
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
    
    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Resource Details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('title')
                                    ->label('Title')
                                    ->weight('bold')
                                    ->size('text-xl'),
                                
                                TextEntry::make('category.name')
                                    ->label('Category'),
                                
                                TextEntry::make('access_level')
                                    ->label('Access Level')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'all' => 'All Members',
                                        'teacher' => 'Teachers Only',
                                        'owner' => 'Owner Only',
                                        default => $state,
                                    })
                                    ->color(fn (string $state): string => match ($state) {
                                        'all' => 'success',
                                        'teacher' => 'warning',
                                        'owner' => 'danger',
                                        default => 'gray',
                                    }),
                                
                                TextEntry::make('creator.name')
                                    ->label('Created By'),
                                
                                TextEntry::make('created_at')
                                    ->label('Created At')
                                    ->dateTime(),
                                
                                TextEntry::make('updated_at')
                                    ->label('Last Updated')
                                    ->dateTime(),
                            ]),
                        
                        TextEntry::make('description')
                            ->label('Description')
                            ->columnSpanFull()
                            ->markdown(),
                    ]),
                
                Section::make('File Preview')
                    ->schema([
                        TextEntry::make('media')
                            ->label('')
                            ->columnSpanFull()
                            ->formatStateUsing(function ($record) {
                                $media = $record->getMedia('resources')->first();
                                
                                if (!$media) {
                                    return new HtmlString('<div class="text-gray-500">No file attached</div>');
                                }
                                
                                $fileUrl = $media->getUrl();
                                $fileName = $media->file_name;
                                $fileSize = $media->human_readable_size;
                                $fileType = $media->mime_type;
                                
                                $preview = '';
                                
                                // PDF preview
                                if (str_contains($fileType, 'pdf')) {
                                    $preview = <<<HTML
                                    <div class="mb-4">
                                        <iframe src="{$fileUrl}" class="w-full h-96 border border-gray-200 rounded-lg"></iframe>
                                    </div>
                                    HTML;
                                }
                                // Image preview
                                elseif (str_contains($fileType, 'image')) {
                                    $preview = <<<HTML
                                    <div class="mb-4">
                                        <img src="{$fileUrl}" class="max-w-full max-h-96 border border-gray-200 rounded-lg" alt="{$fileName}">
                                    </div>
                                    HTML;
                                }
                                
                                // File info and download button
                                $fileInfo = <<<HTML
                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg border border-gray-200">
                                    <div>
                                        <p class="font-medium">{$fileName}</p>
                                        <p class="text-sm text-gray-500">{$fileType} • {$fileSize}</p>
                                    </div>
                                    <a href="{$fileUrl}" download class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                                        Download
                                    </a>
                                </div>
                                HTML;
                                
                                return new HtmlString($preview . $fileInfo);
                            }),
                    ]),
            ]);
    }
}
