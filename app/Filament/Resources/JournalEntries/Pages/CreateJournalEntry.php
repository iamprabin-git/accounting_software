<?php

namespace App\Filament\Resources\JournalEntries\Pages;

use App\Filament\Resources\JournalEntries\JournalEntryResource;
use App\Models\JournalEntry;
use Filament\Resources\Pages\CreateRecord;

class CreateJournalEntry extends CreateRecord
{
    protected static string $resource = JournalEntryResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = JournalEntry::STATUS_APPROVED;
        $data['approved_at'] = now();
        $data['approved_by_user_id'] = $data['user_id'] ?? null;

        return $data;
    }
}
