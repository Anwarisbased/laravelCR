<?php

namespace App\Jobs;

use App\Domain\ValueObjects\UserId;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\ActionLogService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AwardReferralBonus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 30;

    public $userId;
    public $points;
    public $description;

    /**
     * Create a new job instance.
     */
    public function __construct(int $userId, int $points, string $description)
    {
        $this->userId = $userId;
        $this->points = $points;
        $this->description = $description;
    }

    /**
     * Execute the job.
     */
    public function handle(UserRepository $userRepository = null, ActionLogService $actionLogService = null): void
    {
        // For backward compatibility when called directly in tests
        $userRepository = $userRepository ?: app(UserRepository::class);
        $actionLogService = $actionLogService ?: app(ActionLogService::class);

        $user = User::find($this->userId);
        if (!$user) {
            // Log error or handle missing user
            return;
        }

        // Get current points using repository
        $currentBalance = $userRepository->getPointsBalance(UserId::fromInt($this->userId));
        $currentLifetimePoints = $userRepository->getLifetimePoints(UserId::fromInt($this->userId));
        
        // Calculate new values
        $newBalance = $currentBalance + $this->points;
        $newLifetimePoints = $currentLifetimePoints + $this->points;
        
        // Use the repository to update both points balance and lifetime points atomically
        $userRepository->savePointsAndRank(
            UserId::fromInt($this->userId),
            $newBalance,
            $newLifetimePoints,
            $user->current_rank_key
        );
        
        // Log the transaction using the ActionLogService
        $actionLogService->record(
            UserId::fromInt($user->id),
            'referral_bonus',
            0,  // No specific object associated with referral bonus
            [
                'points' => $this->points,
                'description' => $this->description
            ]
        );
    }
}
