<?php
namespace App\Repositories;

use App\Domain\ValueObjects\RewardCode;
use App\Domain\ValueObjects\UserId;
use App\Models\RewardCode as RewardCodeModel;
use Illuminate\Support\Str;

// Exit if accessed directly.

/**
 * Reward Code Repository
 *
 * Handles all data access for reward QR codes.
 */
class RewardCodeRepository {

    /**
     * Finds a valid, unused reward code.
     *
     * @return object|null The code data object or null if not found.
     */
    public function findValidCode(RewardCode $codeToClaim): ?object {
        $code = RewardCodeModel::where('code', $codeToClaim->value)
            ->where('is_used', 0)
            ->select('id', 'sku')
            ->first();
        
        return $code;
    }

    /**
     * Marks a reward code as used by a specific user.
     */
    public function markCodeAsUsed(int $code_id, UserId $user_id): void {
        RewardCodeModel::where('id', $code_id)
            ->update([
                'is_used'    => 1,
                'user_id'    => $user_id->toInt(),
                'claimed_at' => now()
            ]);
    }
    
    public function generateCodes(string $sku, int $quantity): array {
        $generated_codes = [];
        for ($i = 0; $i < $quantity; $i++) {
            $new_code = strtoupper($sku) . '-' . Str::random(12);
            RewardCodeModel::create([
                'code' => $new_code, 
                'sku' => $sku,
                'is_used' => 0,
            ]);
            $generated_codes[] = $new_code;
        }
        return $generated_codes;
    }
}