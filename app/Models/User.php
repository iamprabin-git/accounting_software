<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_COMPANY = 'company';

    public const ROLE_STAFF = 'staff';

    public const ROLE_END_USER = 'end_user';

    /**
     * @return list<string>
     */
    public static function roles(): array
    {
        return [
            self::ROLE_ADMIN,
            self::ROLE_COMPANY,
            self::ROLE_STAFF,
            self::ROLE_END_USER,
        ];
    }

    /**
     * @return list<string>
     */
    public static function companyAssignableRoles(): array
    {
        return [
            self::ROLE_STAFF,
            self::ROLE_END_USER,
        ];
    }

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'google_id',
        'avatar_url',
        'profile_photo_path',
        'is_active',
        'portal_approved_at',
        'portal_approved_by_user_id',
        'subscription_ends_at',
        'company_id',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'portal_approved_at' => 'datetime',
            'subscription_ends_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function profilePhotoPublicUrl(): ?string
    {
        if ($this->profile_photo_path === null || $this->profile_photo_path === '') {
            return null;
        }

        return Storage::disk('public')->url($this->profile_photo_path);
    }

    /**
     * Uploaded profile photo takes precedence over OAuth avatar.
     */
    public function avatarDisplayUrl(): ?string
    {
        return $this->profilePhotoPublicUrl() ?? $this->avatar_url;
    }

    public function portalApprovedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'portal_approved_by_user_id');
    }

    /**
     * Member row in the same company whose email matches this login (case-insensitive).
     */
    public function linkedMember(): ?Member
    {
        if (! $this->isEndUser() || $this->company_id === null || $this->email === null || $this->email === '') {
            return null;
        }

        return Member::query()
            ->where('company_id', $this->company_id)
            ->whereNotNull('email')
            ->whereRaw('LOWER(email) = ?', [mb_strtolower($this->email)])
            ->first();
    }

    public function isPortalApprovedByCompany(): bool
    {
        return $this->portal_approved_at !== null;
    }

    /**
     * End user can view loans/savings/passbook when company approved portal and member is approved.
     */
    public function canViewMemberFinancePortal(): bool
    {
        if (! $this->isEndUser()) {
            return false;
        }

        if (! $this->isPortalApprovedByCompany()) {
            return false;
        }

        $member = $this->linkedMember();

        return $member !== null && $member->isApproved();
    }

    /**
     * @return 'no_member'|'member_pending'|'member_rejected'|'portal_pending'|'ok'
     */
    public function memberPortalState(): string
    {
        $member = $this->linkedMember();

        if ($member === null) {
            return 'no_member';
        }

        if ($member->isPending()) {
            return 'member_pending';
        }

        if ($member->status === Member::STATUS_REJECTED) {
            return 'member_rejected';
        }

        if (! $this->isPortalApprovedByCompany()) {
            return 'portal_pending';
        }

        return 'ok';
    }

    /**
     * Legacy: accounts created under this user id (audit). Books are scoped by company_id.
     */
    public function chartAccounts(): HasMany
    {
        return $this->hasMany(ChartAccount::class, 'user_id');
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class, 'user_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isCompany(): bool
    {
        return $this->role === self::ROLE_COMPANY;
    }

    public function isStaff(): bool
    {
        return $this->role === self::ROLE_STAFF;
    }

    public function isEndUser(): bool
    {
        return $this->role === self::ROLE_END_USER;
    }

    public function canManageTeam(): bool
    {
        return $this->isCompany();
    }

    /**
     * Company profile, CBS configuration, and related POST routes (not integrations tokens).
     */
    public function canManageCompanyWebSettings(): bool
    {
        return $this->isCompany() || $this->isAdmin();
    }

    public function canEditAccounting(): bool
    {
        return $this->isAdmin() || $this->isCompany() || $this->isStaff();
    }

    /**
     * Journal entries are drafted by staff (and platform admins). Company owners approve.
     */
    public function canCreateJournalEntries(): bool
    {
        return $this->isAdmin() || $this->isStaff() || $this->isCompany();
    }

    public function canApproveJournalEntries(): bool
    {
        return $this->isCompany();
    }

    /**
     * Company owners (and platform admins) approve chart accounts proposed by staff.
     */
    public function canApproveChartAccounts(): bool
    {
        return $this->isCompany();
    }

    public function canViewAccountingReports(): bool
    {
        if ($this->isEndUser()) {
            return false;
        }

        return $this->isAdmin() || $this->company_id !== null;
    }

    public function canManageChartOfAccounts(): bool
    {
        return $this->isCompany() || $this->isAdmin();
    }

    public function canAccessCustomerApp(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->subscription_ends_at === null) {
            return true;
        }

        return $this->subscription_ends_at->isFuture();
    }

    public function customerAccessLabel(): string
    {
        if (! $this->is_active) {
            return 'Inactive';
        }

        if ($this->subscription_ends_at?->isPast()) {
            return 'Expired';
        }

        return 'Active';
    }
}
