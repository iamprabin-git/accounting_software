<?php

namespace App\Support;

use App\Models\CrmAccount;
use App\Models\CrmActivity;
use App\Models\CrmContact;
use App\Models\CrmOpportunity;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Ensures CRM foreign keys and morph subjects always match the row's company_id
 * so tenant data cannot be cross-linked in a shared database.
 */
final class CrmTenantGuard
{
    public static function assertContact(CrmContact $contact): void
    {
        if ($contact->crm_account_id === null) {
            return;
        }

        $accountCompanyId = CrmAccount::query()
            ->whereKey($contact->crm_account_id)
            ->value('company_id');

        if ($accountCompanyId === null || (int) $accountCompanyId !== (int) $contact->company_id) {
            throw ValidationException::withMessages([
                'crm_account_id' => [__('The selected account does not belong to this organization.')],
            ]);
        }
    }

    public static function assertOpportunity(CrmOpportunity $opportunity): void
    {
        if ($opportunity->crm_account_id !== null) {
            $accountCompanyId = CrmAccount::query()
                ->whereKey($opportunity->crm_account_id)
                ->value('company_id');

            if ($accountCompanyId === null || (int) $accountCompanyId !== (int) $opportunity->company_id) {
                throw ValidationException::withMessages([
                    'crm_account_id' => [__('The selected account does not belong to this organization.')],
                ]);
            }
        }

        if ($opportunity->crm_contact_id !== null) {
            $contactCompanyId = CrmContact::query()
                ->whereKey($opportunity->crm_contact_id)
                ->value('company_id');

            if ($contactCompanyId === null || (int) $contactCompanyId !== (int) $opportunity->company_id) {
                throw ValidationException::withMessages([
                    'crm_contact_id' => [__('The selected contact does not belong to this organization.')],
                ]);
            }
        }

        if ($opportunity->owner_user_id !== null) {
            $ownerCompanyId = User::query()
                ->whereKey($opportunity->owner_user_id)
                ->value('company_id');

            if ($ownerCompanyId === null || (int) $ownerCompanyId !== (int) $opportunity->company_id) {
                throw ValidationException::withMessages([
                    'owner_user_id' => [__('The selected owner must be a user in this organization.')],
                ]);
            }
        }
    }

    public static function assertActivity(CrmActivity $activity): void
    {
        $subjectCompanyId = match ($activity->subject_type) {
            'crm_account' => CrmAccount::query()->whereKey($activity->subject_id)->value('company_id'),
            'crm_contact' => CrmContact::query()->whereKey($activity->subject_id)->value('company_id'),
            'crm_opportunity' => CrmOpportunity::query()->whereKey($activity->subject_id)->value('company_id'),
            default => null,
        };

        if ($subjectCompanyId === null || (int) $subjectCompanyId !== (int) $activity->company_id) {
            throw ValidationException::withMessages([
                'subject_id' => [__('The related CRM record must belong to the same organization.')],
            ]);
        }
    }
}
