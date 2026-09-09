<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class AuditTrail extends Model
{
    use HasFactory;
    protected $table = 'audit_trail';
    protected $guarded = [];

    /**
     * Create or update audit trail entry for the same user, role, case, and date
     * If an entry exists for the same user, role, case, and date, update the timestamp
     * Otherwise, create a new entry
     * 
     * @return array ['audit_trail' => AuditTrail, 'is_new' => boolean]
     */
    public static function createOrUpdateAuditTrail($caseId, $userId, $userName, $roleId, $roleName, $claimType)
    {
        $today = Carbon::now()->format('Y-m-d');
        
        // Check if an audit trail entry exists for the same user, role, case, and date
        $existingAuditTrail = self::where('case_code', $caseId)
            ->where('user_id', $userId)
            ->where('role_id', $roleId)
            ->where('claim_type', $claimType)
            ->whereDate('created_at', $today)
            ->first();

        if ($existingAuditTrail) {
            // Update the existing entry's timestamp
            $existingAuditTrail->touch(); // This updates the updated_at timestamp
            return ['audit_trail' => $existingAuditTrail, 'is_new' => false];
        } else {
            // Create a new audit trail entry
            $newAuditTrail = self::create([
                'case_code' => $caseId,
                'user_id' => $userId,
                'user_name' => $userName,
                'role_id' => $roleId,
                'role_name' => $roleName,
                'claim_type' => $claimType,
            ]);
            return ['audit_trail' => $newAuditTrail, 'is_new' => true];
        }
    }
}
