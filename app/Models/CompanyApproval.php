<?php

namespace App\Models;

use App\Enums\CompanyApprovalAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyApproval extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'company_id',
        'approved_by',
        'action',
        'commission_percentage',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'action' => CompanyApprovalAction::class,
            'commission_percentage' => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
