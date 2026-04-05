<?php

namespace App\Filament\Resources\CrmContacts\Pages;

use App\Filament\Resources\CrmContacts\CrmContactResource;
use Filament\Resources\Pages\EditRecord;

class EditCrmContact extends EditRecord
{
    protected static string $resource = CrmContactResource::class;
}
