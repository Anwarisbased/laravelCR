<?php
namespace App\Repositories;

use App\Domain\ValueObjects\RewardCode;
use App\Domain\ValueObjects\UserId;
use App\Infrastructure\WordPressApiWrapperInterface;

// Exit if accessed directly.

/**
 * Reward Code Repository
 *
 * Handles all data access for reward QR codes.
 */
class RewardCodeRepository {
    private WordPressApiWrapperInterface $wp;
    private string $table_name = 'reward_codes';

    public function __construct(WordPressApiWrapperInterface $wp) {
        $this->wp = $wp;
    }

    /**
     * Finds a valid, unused reward code.
     *
     * @return object|null The code data object or null if not found.
     */
    public function findValidCode(RewardCode $codeToClaim): ?object {
        $full_table_name = $this->wp->getDbPrefix() . $this->table_name;
        $query = $this->wp->dbPrepare(
            "SELECT id, sku FROM {$full_table_name} WHERE code = %s AND is_used = 0",
            $codeToClaim->value
        );
        return $this->wp->dbGetRow($query);
    }

    /**
     * Marks a reward code as used by a specific user.
     */
    public function markCodeAsUsed(int $code_id, UserId $user_id): void {
        $this->wp->dbUpdate(
            $this->table_name,
            [
                'is_used'    => 1,
                'user_id'    => $user_id->toInt(),
                'claimed_at' => $this->wp->currentTime('mysql', 1)
            ],
            ['id' => $code_id]
        );
    }
    
    public function generateCodes(string $sku, int $quantity): array {
        $generated_codes = [];
        for ($i = 0; $i < $quantity; $i++) {
            $new_code = strtoupper($sku) . '-' . $this->wp->generatePassword(12, false, false);
            $this->wp->dbInsert($this->table_name, ['code' => $new_code, 'sku' => $sku]);
            $generated_codes[] = $new_code;
        }
        return $generated_codes;
    }
}