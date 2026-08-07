<?php

namespace App\Models\RH\Declaration;

use App\Models\RH\Employee\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeclarationRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'declaration_requests';

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (DeclarationRequest $request) {
            if (empty($request->reference_number)) {
                $request->reference_number = self::generateReferenceNumber();
            }
        });
    }

    public static function generateReferenceNumber(): string
    {
        $last = static::withTrashed()
            ->where('reference_number', 'like', 'DEC-%')
            ->orderByRaw('CAST(SUBSTRING(reference_number, 5) AS UNSIGNED) DESC')
            ->first();

        $next = 1;
        if ($last && preg_match('/DEC-(\d+)/', $last->reference_number, $matches)) {
            $next = (int) $matches[1] + 1;
        }

        return 'DEC-' . str_pad($next, 5, '0', STR_PAD_LEFT);
    }

    protected $fillable = [
        'reference_number',
        'employee_id',
        'declaration_type_id',
        'institution_name',
        'institution_type',
        'purpose',
        'additional_info',
        'content',
        'status',
        'issued_number',
        'issued_at',
        'issued_by',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'content' => 'array',
            'issued_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function declarationType()
    {
        return $this->belongsTo(DeclarationType::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function issuedBy()
    {
        return $this->belongsTo(User::class, 'issued_by');
    }
}
