<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Support\CrmTenantGuard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmOpportunity extends Model
{
    use BelongsToCompany;

    protected static function booted(): void
    {
        static::saving(function (CrmOpportunity $opportunity): void {
            CrmTenantGuard::assertOpportunity($opportunity);
        });
    }

    public const STAGE_LEAD = 'lead';

    public const STAGE_QUALIFIED = 'qualified';

    public const STAGE_PROPOSAL = 'proposal';

    public const STAGE_NEGOTIATION = 'negotiation';

    public const STAGE_WON = 'won';

    public const STAGE_LOST = 'lost';

    protected $table = 'crm_opportunities';

    protected $fillable = [
        'company_id',
        'crm_account_id',
        'crm_contact_id',
        'name',
        'stage',
        'amount_cents',
        'probability',
        'expected_close_date',
        'owner_user_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'expected_close_date' => 'date',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function stageLabels(): array
    {
        return [
            self::STAGE_LEAD => 'Lead',
            self::STAGE_QUALIFIED => 'Qualified',
            self::STAGE_PROPOSAL => 'Proposal',
            self::STAGE_NEGOTIATION => 'Negotiation',
            self::STAGE_WON => 'Won',
            self::STAGE_LOST => 'Lost',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(CrmAccount::class, 'crm_account_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(CrmContact::class, 'crm_contact_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function isOpen(): bool
    {
        return ! in_array($this->stage, [self::STAGE_WON, self::STAGE_LOST], true);
    }
}
