<?php
namespace App\Services;

use App\Commands\GrantPointsCommand;
use App\Repositories\AchievementRepository;
use App\Repositories\ActionLogRepository;
use Illuminate\Support\Facades\Log;

class GamificationService {
    private EconomyService $economy_service;
    private ActionLogService $action_log_service;
    private AchievementRepository $achievement_repository;
    private ActionLogRepository $action_log_repository;
    private RulesEngineService $rules_engine;

    public function __construct(
        EconomyService $economy_service,
        ActionLogService $action_log_service,
        AchievementRepository $achievement_repository,
        ActionLogRepository $action_log_repository,
        RulesEngineService $rules_engine
    ) {
        $this->economy_service = $economy_service;
        $this->action_log_service = $action_log_service;
        $this->achievement_repository = $achievement_repository;
        $this->action_log_repository = $action_log_repository;
        $this->rules_engine = $rules_engine;
    }

    public function handle_event(array $payload, string $event_name) {
        $user_id = $payload['user_snapshot']['identity']['user_id'] ?? 0;
        if (empty($user_id)) {
            return;
        }
        $this->check_and_process_event($user_id, $event_name, $payload);
    }

    private function check_and_process_event(int $user_id, string $event_name, array $context = []) {
        $achievements_to_check = $this->achievement_repository->findByTriggerEvent($event_name);
        $user_unlocked_keys = $this->achievement_repository->getUnlockedKeysForUser($user_id);

        foreach ($achievements_to_check as $achievement) {
            if (in_array($achievement->achievement_key, $user_unlocked_keys, true)) {
                continue;
            }

            if ($this->evaluate_conditions($achievement, $user_id, $context)) {
                $this->unlock_achievement($user_id, $achievement);
            }
        }
    }
    
    private function evaluate_conditions(object $achievement, int $user_id, array $context): bool {
        $action_count = $this->action_log_repository->countUserActions($user_id, $achievement->trigger_event);
        if ($action_count < (int) $achievement->trigger_count) {
            return false;
        }

        $json_conditions = json_decode($achievement->conditions ?: '[]', true);
        if (!is_array($json_conditions)) {
            Log::error("CannaRewards: Malformed JSON condition for achievement key: {$achievement->achievement_key}");
            return false;
        }

        return $this->rules_engine->evaluate($json_conditions, $context);
    }

    private function unlock_achievement(int $user_id, object $achievement) {
        $this->achievement_repository->saveUnlockedAchievement($user_id, $achievement->achievement_key);
        
        $points_reward = (int) $achievement->points_reward;
        if ($points_reward > 0) {
            $command = new GrantPointsCommand(
                \App\Domain\ValueObjects\UserId::fromInt($user_id),
                \App\Domain\ValueObjects\Points::fromInt($points_reward),
                'Achievement Unlocked: ' . $achievement->title
            );
            $this->economy_service->handle($command);
        }

        $achievement_details = ['key' => $achievement->achievement_key, 'name' => $achievement->title, 'points_rewarded' => $points_reward];
        $this->action_log_service->record($user_id, 'achievement_unlocked', 0, $achievement_details);
    }
}