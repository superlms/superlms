<?php

namespace App\Models\Boards;

use Illuminate\Database\Eloquent\Model;

/**
 * A school's Boards fee rules (More → Boards → Settings): the share of the
 * fee, in per cent, a Class 9–12 student must have paid before the website's
 * Boards login shows their board roll number, and before it gives their
 * admit card. 0 (and a school that has not saved any) asks for nothing.
 */
class BoardSetting extends Model
{
    protected $fillable = ['organization_id', 'roll_fee_percent', 'admit_fee_percent'];

    protected $casts = [
        'roll_fee_percent'  => 'float',
        'admit_fee_percent' => 'float',
    ];

    /** The school's rules: [roll => %, admit => %]. */
    public static function rules(int $orgId): array
    {
        try {
            $row = self::where('organization_id', $orgId)->first();
        } catch (\Illuminate\Database\QueryException $e) {
            $row = null;   // before the migration
        }

        return [
            'roll'  => (float) ($row->roll_fee_percent ?? 0),
            'admit' => (float) ($row->admit_fee_percent ?? 0),
        ];
    }
}
