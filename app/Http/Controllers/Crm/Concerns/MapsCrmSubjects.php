<?php

namespace App\Http\Controllers\Crm\Concerns;

use App\Models\CrmAccount;
use App\Models\CrmContact;
use App\Models\CrmOpportunity;
use Illuminate\Database\Eloquent\Model;

trait MapsCrmSubjects
{
    protected function crmSubjectLabel(?Model $subject): string
    {
        if ($subject instanceof CrmAccount) {
            return $subject->name;
        }
        if ($subject instanceof CrmContact) {
            return $subject->fullName();
        }
        if ($subject instanceof CrmOpportunity) {
            return $subject->name;
        }

        return '—';
    }
}
