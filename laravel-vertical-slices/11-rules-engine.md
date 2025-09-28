# Laravel Vertical Slice 11: Rules Engine

## Overview
This vertical slice implements the rules engine system including conditional logic processing, trigger evaluation, and dynamic rule management using Laravel's native features, replacing WordPress-based rule builder.

## Key Components

### Laravel Components
- Laravel Collections for rule evaluation
- Laravel Validation for rule conditions
- Laravel Events for rule triggers
- Laravel Jobs for rule execution
- Laravel Cache for rule caching
- Laravel Policies for rule authorization
- Laravel Notifications for rule outcomes

### Domain Entities
- Rule (Eloquent Model)
- RuleCondition (Eloquent Model)
- RuleAction (Eloquent Model)
- RuleExecution (Eloquent Model)
- RuleContext (DTO for evaluation context)

### Rule Types
- Achievement Unlock Rules
- Trigger-Based Bonus Rules
- Rank Progression Rules
- Referral Conversion Rules
- Redemption Restriction Rules
- Custom Business Logic Rules

### Laravel Services
- RulesEngineService (Core rules engine)
- RuleEvaluationService (Rule evaluation logic)
- RuleTriggerService (Trigger management)
- RuleActionService (Action execution)
-RuleContextService (Context building)

### Laravel Models
- Rule (Eloquent model for rule definitions)
- RuleCondition (Eloquent model for rule conditions)
- RuleAction (Eloquent model for rule actions)
- RuleExecution (Eloquent model for execution tracking)

### Laravel Events
- RuleEvaluated
- RuleTriggered
- RuleActionExecuted

### Laravel Jobs
- EvaluateRuleConditions
- ExecuteRuleAction
- ProcessRuleTrigger

### Laravel Policies
- RuleManagementPolicy
- RuleExecutionPolicy

## Implementation Details

### Rule Model Structure
```php
// app/Models/Rule.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Rule extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name',
        'description',
        'event_trigger',
        'is_active',
        'priority',
        'conditions_logic',
        'execution_limit',
        'execution_count',
        'start_date',
        'end_date',
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
        'priority' => 'integer',
        'execution_limit' => 'integer',
        'execution_count' => 'integer',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'conditions_logic' => 'array',
    ];
    
    // Relationships
    public function conditions()
    {
        return $this->hasMany(RuleCondition::class);
    }
    
    public function actions()
    {
        return $this->hasMany(RuleAction::class);
    }
    
    public function executions()
    {
        return $this->hasMany(RuleExecution::class);
    }
    
    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('start_date')
                  ->orWhere('start_date', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', now());
            });
    }
    
    public function scopeByEvent($query, string $event)
    {
        return $query->where('event_trigger', $event);
    }
    
    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc');
    }
    
    // Methods
    public function isActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }
        
        if ($this->start_date && $this->start_date > now()) {
            return false;
        }
        
        if ($this->end_date && $this->end_date < now()) {
            return false;
        }
        
        if ($this->execution_limit > 0 && $this->execution_count >= $this->execution_limit) {
            return false;
        }
        
        return true;
    }
    
    public function canExecuteForUser(int $userId): bool
    {
        // Check if user has already triggered this rule (if rule is user-specific)
        if ($this->isUserSpecific()) {
            $executions = $this->executions()
                ->where('user_id', $userId)
                ->count();
                
            if ($executions > 0) {
                return false;
            }
        }
        
        return true;
    }
    
    protected function isUserSpecific(): bool
    {
        // Check if any conditions reference user-specific data
        return $this->conditions->some(function ($condition) {
            return strpos($condition->field, 'user.') === 0;
        });
    }
    
    public function incrementExecutionCount(): void
    {
        $this->increment('execution_count');
    }
}
```

### Rule Condition Model
```php
// app/Models/RuleCondition.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RuleCondition extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'rule_id',
        'field',
        'operator',
        'value',
        'value_type',
        'group',
        'logical_operator',
    ];
    
    protected $casts = [
        'value' => 'array',
        'value_type' => 'string',
    ];
    
    // Relationships
    public function rule()
    {
        return $this->belongsTo(Rule::class);
    }
    
    // Accessors
    public function getValueAttribute($value)
    {
        if (is_string($value) && $this->value_type === 'json') {
            return json_decode($value, true);
        }
        
        return $value;
    }
    
    public function setValueAttribute($value)
    {
        if (is_array($value) && $this->value_type === 'json') {
            $this->attributes['value'] = json_encode($value);
        } else {
            $this->attributes['value'] = $value;
        }
    }
    
    // Methods
    public function evaluate(array $context): bool
    {
        $actualValue = $this->extractValueFromContext($context, $this->field);
        
        switch ($this->operator) {
            case 'equals':
            case 'is':
                return $actualValue == $this->value;
            case 'not_equals':
            case 'is_not':
                return $actualValue != $this->value;
            case 'greater_than':
            case '>':
                return (float) $actualValue > (float) $this->value;
            case 'less_than':
            case '<':
                return (float) $actualValue < (float) $this->value;
            case 'greater_than_or_equal':
            case '>=':
                return (float) $actualValue >= (float) $this->value;
            case 'less_than_or_equal':
            case '<=':
                return (float) $actualValue <= (float) $this->value;
            case 'contains':
                return is_string($actualValue) && strpos($actualValue, $this->value) !== false;
            case 'does_not_contain':
                return is_string($actualValue) && strpos($actualValue, $this->value) === false;
            case 'in':
                return is_array($this->value) && in_array($actualValue, $this->value);
            case 'not_in':
                return is_array($this->value) && !in_array($actualValue, $this->value);
            case 'is_empty':
                return empty($actualValue);
            case 'is_not_empty':
                return !empty($actualValue);
            default:
                return false;
        }
    }
    
    protected function extractValueFromContext(array $context, string $fieldPath)
    {
        $keys = explode('.', $fieldPath);
        $value = $context;
        
        foreach ($keys as $key) {
            if (!is_array($value) || !array_key_exists($key, $value)) {
                return null;
            }
            $value = $value[$key];
        }
        
        return $value;
    }
}
```

### Rules Engine Service
```php
// app/Services/RulesEngineService.php
namespace App\Services;

use App\Models\Rule;
use App\Models\RuleExecution;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RulesEngineService
{
    protected $ruleEvaluationService;
    protected $ruleActionService;
    protected $cacheTtl;
    
    public function __construct(
        RuleEvaluationService $ruleEvaluationService,
        RuleActionService $ruleActionService
    ) {
        $this->ruleEvaluationService = $ruleEvaluationService;
        $this->ruleActionService = $ruleActionService;
        $this->cacheTtl = config('cache.rules_ttl', 1800); // 30 minutes
    }
    
    public function evaluateRulesForEvent(string $event, array $context = []): void
    {
        // Get active rules for this event
        $rules = Rule::active()
            ->byEvent($event)
            ->byPriority()
            ->with(['conditions', 'actions'])
            ->get();
            
        if ($rules->isEmpty()) {
            return;
        }
        
        // Process each rule
        foreach ($rules as $rule) {
            $this->processRule($rule, $context);
        }
    }
    
    protected function processRule(Rule $rule, array $context): void
    {
        try {
            // Check if rule can be executed for this context
            if (!$this->canExecuteRule($rule, $context)) {
                return;
            }
            
            // Evaluate rule conditions
            if (!$this->ruleEvaluationService->evaluateRule($rule, $context)) {
                return;
            }
            
            // Execute rule actions
            $this->executeRuleActions($rule, $context);
            
            // Increment execution count
            $rule->incrementExecutionCount();
            
            // Log execution
            $this->logRuleExecution($rule, $context);
            
        } catch (\Exception $e) {
            Log::error('Rule processing failed', [
                'rule_id' => $rule->id,
                'rule_name' => $rule->name,
                'event' => $rule->event_trigger,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
    
    protected function canExecuteRule(Rule $rule, array $context): bool
    {
        // Check execution limits
        if (!$rule->isActive()) {
            return false;
        }
        
        // Check user-specific execution if applicable
        if (isset($context['user_id'])) {
            if (!$rule->canExecuteForUser($context['user_id'])) {
                return false;
            }
        }
        
        return true;
    }
    
    protected function executeRuleActions(Rule $rule, array $context): void
    {
        DB::transaction(function () use ($rule, $context) {
            foreach ($rule->actions as $action) {
                $this->ruleActionService->executeAction($action, $context);
            }
            
            // Record execution
            RuleExecution::create([
                'rule_id' => $rule->id,
                'user_id' => $context['user_id'] ?? null,
                'context' => $context,
                'executed_at' => now(),
            ]);
        });
    }
    
    protected function logRuleExecution(Rule $rule, array $context): void
    {
        Log::info('Rule executed', [
            'rule_id' => $rule->id,
            'rule_name' => $rule->name,
            'event' => $rule->event_trigger,
            'user_id' => $context['user_id'] ?? null,
            'execution_time' => now()->toISOString(),
        ]);
        
        // Fire event
        event(new \App\Events\RuleExecuted($rule, $context));
    }
    
    public function getAvailableConditions(): array
    {
        return [
            'user.points_balance' => [
                'label' => 'User Points Balance',
                'operators' => ['equals', 'not_equals', 'greater_than', 'less_than', 'greater_than_or_equal', 'less_than_or_equal'],
                'input_type' => 'number',
            ],
            'user.lifetime_points' => [
                'label' => 'User Lifetime Points',
                'operators' => ['equals', 'not_equals', 'greater_than', 'less_than', 'greater_than_or_equal', 'less_than_or_equal'],
                'input_type' => 'number',
            ],
            'user.current_rank_key' => [
                'label' => 'User Current Rank',
                'operators' => ['equals', 'not_equals', 'in', 'not_in'],
                'input_type' => 'select',
                'options' => $this->getAvailableRanks(),
            ],
            'user.total_scans' => [
                'label' => 'User Total Scans',
                'operators' => ['equals', 'not_equals', 'greater_than', 'less_than', 'greater_than_or_equal', 'less_than_or_equal'],
                'input_type' => 'number',
            ],
            'product.category' => [
                'label' => 'Product Category',
                'operators' => ['equals', 'not_equals', 'in', 'not_in'],
                'input_type' => 'select',
                'options' => $this->getAvailableCategories(),
            ],
            'product.strain_type' => [
                'label' => 'Product Strain Type',
                'operators' => ['equals', 'not_equals', 'in', 'not_in'],
                'input_type' => 'select',
                'options' => ['Sativa', 'Indica', 'Hybrid'],
            ],
            'event.is_first_scan' => [
                'label' => 'Is First Scan',
                'operators' => ['is', 'is_not'],
                'input_type' => 'boolean',
            ],
        ];
    }
    
    protected function getAvailableRanks(): array
    {
        return cache()->remember('rule_ranks', 3600, function () {
            return \App\Models\Rank::active()
                ->pluck('name', 'key')
                ->toArray();
        });
    }
    
    protected function getAvailableCategories(): array
    {
        return cache()->remember('rule_categories', 3600, function () {
            return \App\Models\ProductCategory::active()
                ->pluck('name', 'id')
                ->toArray();
        });
    }
}
```

## Rule Evaluation Implementation

### Rule Evaluation Service
```php
// app/Services/RuleEvaluationService.php
namespace App\Services;

use App\Models\Rule;
use Illuminate\Support\Collection;

class RuleEvaluationService
{
    public function evaluateRule(Rule $rule, array $context): bool
    {
        // Group conditions by logical groups
        $conditionGroups = $this->groupConditionsByLogic($rule->conditions);
        
        // Evaluate each group
        foreach ($conditionGroups as $group) {
            $groupResult = $this->evaluateConditionGroup($group, $context);
            
            // If any group fails and we're using AND logic, return false
            if (!$groupResult && ($rule->conditions_logic['operator'] ?? 'AND') === 'AND') {
                return false;
            }
            
            // If any group passes and we're using OR logic, return true
            if ($groupResult && ($rule->conditions_logic['operator'] ?? 'AND') === 'OR') {
                return true;
            }
        }
        
        // If we're using AND logic, all groups passed
        // If we're using OR logic, no groups passed
        return ($rule->conditions_logic['operator'] ?? 'AND') === 'AND';
    }
    
    protected function groupConditionsByLogic(Collection $conditions): array
    {
        $groups = [];
        $currentGroup = [];
        $currentGroupIndex = 0;
        
        foreach ($conditions as $condition) {
            $currentGroup[] = $condition;
            
            // If this is the last condition or next condition starts a new group
            if ($condition === $conditions->last() || 
                ($conditions->get($conditions->search($condition) + 1)?->logical_operator ?? 'AND') === 'OR') {
                $groups[] = $currentGroup;
                $currentGroup = [];
            }
        }
        
        // Add any remaining conditions
        if (!empty($currentGroup)) {
            $groups[] = $currentGroup;
        }
        
        return $groups;
    }
    
    protected function evaluateConditionGroup(array $conditions, array $context): bool
    {
        foreach ($conditions as $condition) {
            $result = $condition->evaluate($context);
            
            // If any condition in an AND group fails, return false
            if (!$result && ($condition->logical_operator ?? 'AND') === 'AND') {
                return false;
            }
            
            // If any condition in an OR group passes, return true
            if ($result && ($condition->logical_operator ?? 'AND') === 'OR') {
                return true;
            }
        }
        
        // If we're using AND logic, all conditions passed
        // If we're using OR logic, no conditions passed
        return ($conditions[0]->logical_operator ?? 'AND') === 'AND';
    }
    
    public function evaluateComplexCondition(array $conditions, array $context): bool
    {
        if (empty($conditions)) {
            return true;
        }
        
        $result = null;
        $currentOperator = 'AND';
        
        foreach ($conditions as $conditionDef) {
            if (isset($conditionDef['operator']) && in_array($conditionDef['operator'], ['AND', 'OR'])) {
                $currentOperator = $conditionDef['operator'];
                continue;
            }
            
            $conditionResult = $this->evaluateSingleCondition($conditionDef, $context);
            
            if ($result === null) {
                $result = $conditionResult;
            } elseif ($currentOperator === 'AND') {
                $result = $result && $conditionResult;
            } elseif ($currentOperator === 'OR') {
                $result = $result || $conditionResult;
            }
        }
        
        return $result ?? true;
    }
    
    protected function evaluateSingleCondition(array $condition, array $context): bool
    {
        if (!isset($condition['field'], $condition['operator'], $condition['value'])) {
            return false;
        }
        
        $field = $condition['field'];
        $operator = $condition['operator'];
        $expectedValue = $condition['value'];
        
        $actualValue = $this->extractValueFromContext($context, $field);
        
        if ($actualValue === null) {
            return false;
        }
        
        switch ($operator) {
            case 'is':
            case 'equals':
                return $actualValue == $expectedValue;
            case 'is_not':
            case 'not_equals':
                return $actualValue != $expectedValue;
            case '>':
            case 'greater_than':
                return (float) $actualValue > (float) $expectedValue;
            case '<':
            case 'less_than':
                return (float) $actualValue < (float) $expectedValue;
            case '>=':
            case 'greater_than_or_equal':
                return (float) $actualValue >= (float) $expectedValue;
            case '<=':
            case 'less_than_or_equal':
                return (float) $actualValue <= (float) $expectedValue;
            case 'contains':
                return is_string($actualValue) && strpos($actualValue, $expectedValue) !== false;
            case 'does_not_contain':
                return is_string($actualValue) && strpos($actualValue, $expectedValue) === false;
            case 'in':
                return is_array($expectedValue) && in_array($actualValue, $expectedValue);
            case 'not_in':
                return is_array($expectedValue) && !in_array($actualValue, $expectedValue);
            case 'is_empty':
                return empty($actualValue);
            case 'is_not_empty':
                return !empty($actualValue);
            default:
                return false;
        }
    }
    
    protected function extractValueFromContext(array $context, string $fieldPath)
    {
        $keys = explode('.', $fieldPath);
        $value = $context;
        
        foreach ($keys as $key) {
            if (!is_array($value) || !array_key_exists($key, $value)) {
                return null;
            }
            $value = $value[$key];
        }
        
        return $value;
    }
}
```

## Rule Action Implementation

### Rule Action Service
```php
// app/Services/RuleActionService.php
namespace App\Services;

use App\Models\RuleAction;
use App\Services\EconomyService;
use App\Services\AchievementService;
use Illuminate\Support\Facades\Log;

class RuleActionService
{
    protected $economyService;
    protected $achievementService;
    
    public function __construct(
        EconomyService $economyService,
        AchievementService $achievementService
    ) {
        $this->economyService = $economyService;
        $this->achievementService = $achievementService;
    }
    
    public function executeAction(RuleAction $action, array $context): void
    {
        $actionType = $action->action_type;
        $parameters = $action->parameters ?? [];
        
        try {
            switch ($actionType) {
                case 'grant_points':
                    $this->grantPoints($parameters, $context);
                    break;
                case 'unlock_achievement':
                    $this->unlockAchievement($parameters, $context);
                    break;
                case 'send_notification':
                    $this->sendNotification($parameters, $context);
                    break;
                case 'update_user_meta':
                    $this->updateUserMeta($parameters, $context);
                    break;
                case 'create_task':
                    $this->createTask($parameters, $context);
                    break;
                default:
                    Log::warning('Unknown rule action type', [
                        'action_type' => $actionType,
                        'rule_action_id' => $action->id,
                    ]);
                    break;
            }
        } catch (\Exception $e) {
            Log::error('Rule action execution failed', [
                'action_type' => $actionType,
                'rule_action_id' => $action->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw $e;
        }
    }
    
    protected function grantPoints(array $parameters, array $context): void
    {
        $userId = $context['user_id'] ?? null;
        if (!$userId) {
            throw new \InvalidArgumentException('User ID required for grant_points action');
        }
        
        $points = $parameters['points'] ?? 0;
        $description = $parameters['description'] ?? 'Rule-based point grant';
        
        // Apply context variables to description
        $description = $this->replaceContextVariables($description, $context);
        
        if ($points > 0) {
            // Dispatch points grant command
            $command = new \App\Commands\GrantPointsCommand(
                \App\Domain\ValueObjects\UserId::fromInt($userId),
                \App\Domain\ValueObjects\Points::fromInt($points),
                $description
            );
            
            $this->economyService->handle($command);
        }
    }
    
    protected function unlockAchievement(array $parameters, array $context): void
    {
        $userId = $context['user_id'] ?? null;
        if (!$userId) {
            throw new \InvalidArgumentException('User ID required for unlock_achievement action');
        }
        
        $achievementKey = $parameters['achievement_key'] ?? null;
        if (!$achievementKey) {
            throw new \InvalidArgumentException('Achievement key required for unlock_achievement action');
        }
        
        $user = \App\Models\User::find($userId);
        if (!$user) {
            throw new \InvalidArgumentException('User not found');
        }
        
        $achievement = \App\Models\Achievement::where('key', $achievementKey)->first();
        if (!$achievement) {
            throw new \InvalidArgumentException("Achievement with key {$achievementKey} not found");
        }
        
        // Unlock achievement for user
        $this->achievementService->unlockAchievement($user, $achievement);
    }
    
    protected function sendNotification(array $parameters, array $context): void
    {
        $userId = $context['user_id'] ?? null;
        if (!$userId) {
            throw new \InvalidArgumentException('User ID required for send_notification action');
        }
        
        $user = \App\Models\User::find($userId);
        if (!$user) {
            throw new \InvalidArgumentException('User not found');
        }
        
        $notificationType = $parameters['notification_type'] ?? null;
        $message = $parameters['message'] ?? '';
        $title = $parameters['title'] ?? '';
        
        // Apply context variables
        $message = $this->replaceContextVariables($message, $context);
        $title = $this->replaceContextVariables($title, $context);
        
        // Create and send notification
        $notification = new \App\Notifications\RuleBasedNotification($title, $message);
        $user->notify($notification);
    }
    
    protected function updateUserMeta(array $parameters, array $context): void
    {
        $userId = $context['user_id'] ?? null;
        if (!$userId) {
            throw new \InvalidArgumentException('User ID required for update_user_meta action');
        }
        
        $user = \App\Models\User::find($userId);
        if (!$user) {
            throw new \InvalidArgumentException('User not found');
        }
        
        $metaKey = $parameters['meta_key'] ?? null;
        $metaValue = $parameters['meta_value'] ?? null;
        
        if ($metaKey) {
            // Apply context variables to meta value
            $metaValue = $this->replaceContextVariables($metaValue, $context);
            
            // Update user meta
            $user->update([
                $metaKey => $metaValue,
            ]);
        }
    }
    
    protected function createTask(array $parameters, array $context): void
    {
        $userId = $context['user_id'] ?? null;
        if (!$userId) {
            throw new \InvalidArgumentException('User ID required for create_task action');
        }
        
        $title = $parameters['title'] ?? 'Rule-generated task';
        $description = $parameters['description'] ?? '';
        $dueDate = $parameters['due_date'] ?? null;
        $priority = $parameters['priority'] ?? 'normal';
        
        // Apply context variables
        $title = $this->replaceContextVariables($title, $context);
        $description = $this->replaceContextVariables($description, $context);
        
        // Create task (implementation depends on task management system)
        \App\Models\Task::create([
            'user_id' => $userId,
            'title' => $title,
            'description' => $description,
            'due_date' => $dueDate ? now()->modify($dueDate) : null,
            'priority' => $priority,
            'status' => 'pending',
        ]);
    }
    
    protected function replaceContextVariables(string $text, array $context): string
    {
        // Replace variables like {user.first_name} with actual values
        return preg_replace_callback('/\{([^}]+)\}/', function ($matches) use ($context) {
            $variable = $matches[1];
            $keys = explode('.', $variable);
            $value = $context;
            
            foreach ($keys as $key) {
                if (!is_array($value) || !array_key_exists($key, $value)) {
                    return $matches[0]; // Return original variable if not found
                }
                $value = $value[$key];
            }
            
            return $value;
        }, $text);
    }
}
```

## Rule Context Building

### Rule Context Service
```php
// app/Services/RuleContextService.php
namespace App\Services;

use App\Models\User;
use App\Models\Product;

class RuleContextService
{
    public function buildContextForEvent(string $event, array $payload = []): array
    {
        $context = [
            'event' => [
                'name' => $event,
                'timestamp' => now()->toISOString(),
            ],
        ];
        
        switch ($event) {
            case 'product_scanned':
                $context = array_merge($context, $this->buildProductScanContext($payload));
                break;
            case 'user_registered':
                $context = array_merge($context, $this->buildUserRegistrationContext($payload));
                break;
            case 'reward_redeemed':
                $context = array_merge($context, $this->buildRewardRedemptionContext($payload));
                break;
            case 'achievement_unlocked':
                $context = array_merge($context, $this->buildAchievementUnlockContext($payload));
                break;
            case 'user_rank_changed':
                $context = array_merge($context, $this->buildRankChangeContext($payload));
                break;
        }
        
        return $context;
    }
    
    protected function buildProductScanContext(array $payload): array
    {
        $user = $payload['user'] ?? null;
        $product = $payload['product'] ?? null;
        $isFirstScan = $payload['is_first_scan'] ?? false;
        
        $context = [
            'is_first_scan' => $isFirstScan,
        ];
        
        if ($user) {
            $context['user'] = $this->buildUserSnapshot($user);
        }
        
        if ($product) {
            $context['product'] = $this->buildProductSnapshot($product);
        }
        
        return $context;
    }
    
    protected function buildUserRegistrationContext(array $payload): array
    {
        $user = $payload['user'] ?? null;
        
        if (!$user) {
            return [];
        }
        
        return [
            'user' => $this->buildUserSnapshot($user),
        ];
    }
    
    protected function buildRewardRedemptionContext(array $payload): array
    {
        $user = $payload['user'] ?? null;
        $product = $payload['product'] ?? null;
        
        $context = [];
        
        if ($user) {
            $context['user'] = $this->buildUserSnapshot($user);
        }
        
        if ($product) {
            $context['product'] = $this->buildProductSnapshot($product);
        }
        
        return $context;
    }
    
    protected function buildAchievementUnlockContext(array $payload): array
    {
        $user = $payload['user'] ?? null;
        $achievement = $payload['achievement'] ?? null;
        
        $context = [];
        
        if ($user) {
            $context['user'] = $this->buildUserSnapshot($user);
        }
        
        if ($achievement) {
            $context['achievement'] = [
                'key' => $achievement->key,
                'title' => $achievement->title,
                'points_reward' => $achievement->points_reward,
            ];
        }
        
        return $context;
    }
    
    protected function buildRankChangeContext(array $payload): array
    {
        $user = $payload['user'] ?? null;
        $newRank = $payload['new_rank'] ?? null;
        $previousRank = $payload['previous_rank'] ?? null;
        
        $context = [];
        
        if ($user) {
            $context['user'] = $this->buildUserSnapshot($user);
        }
        
        if ($newRank) {
            $context['new_rank'] = [
                'key' => $newRank->key,
                'name' => $newRank->name,
                'points_required' => $newRank->pointsRequired->toInt(),
            ];
        }
        
        if ($previousRank) {
            $context['previous_rank'] = [
                'key' => $previousRank->key,
                'name' => $previousRank->name,
                'points_required' => $previousRank->pointsRequired->toInt(),
            ];
        }
        
        return $context;
    }
    
    protected function buildUserSnapshot(User $user): array
    {
        return [
            'id' => $user->id,
            'email' => $user->email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'points_balance' => $user->points_balance,
            'lifetime_points' => $user->lifetime_points,
            'current_rank_key' => $user->current_rank_key,
            'total_scans' => $this->getUserScanCount($user),
            'total_redemptions' => $this->getUserRedemptionCount($user),
            'total_achievements_unlocked' => $user->unlockedAchievements()->count(),
            'created_at' => $user->created_at->toISOString(),
        ];
    }
    
    protected function buildProductSnapshot(Product $product): array
    {
        return [
            'id' => $product->id,
            'sku' => $product->sku,
            'name' => $product->name,
            'category' => $product->category?->name,
            'strain_type' => $product->strain_type,
            'points_award' => $product->points_award,
            'points_cost' => $product->points_cost,
            'required_rank_key' => $product->required_rank_key,
        ];
    }
    
    protected function getUserScanCount(User $user): int
    {
        return $user->actionLogs()
            ->where('action_type', 'scan')
            ->count();
    }
    
    protected function getUserRedemptionCount(User $user): int
    {
        return $user->orders()
            ->where('is_canna_redemption', true)
            ->count();
    }
}
```

## Laravel-Native Features Utilized

### Collections
- Laravel Collections for rule condition grouping and evaluation
- Higher-order messaging for complex rule processing
- Collection pipelining for efficient data transformation

### Validation
- Laravel Validation for rule condition definition
- Custom validation rules for business logic constraints
- Automatic error response formatting for API endpoints

### Events & Listeners
- Laravel Event system for rule triggers
- Event discovery for automatic listener registration
- Queued event listeners for performance
- Event broadcasting for real-time rule evaluation

### Jobs & Queues
- Laravel Jobs for background rule evaluation
- Queue workers for async rule processing
- Failed job handling and retry logic
- Job chaining for complex rule workflows

### Caching
- Laravel Cache facade for rule definition caching
- Cache tags for granular invalidation
- Automatic cache expiration and refresh
- Redis or file-based caching drivers

### Policies
- Laravel Policies for rule management authorization
- Fine-grained access control for rule operations
- Resource-based permissions for rule execution

### Notifications
- Laravel Notifications for rule-based user communications
- Multiple channels (email, SMS, database, push)
- Markdown notification templates
- Notification throttling

## Business Logic Implementation

### Rule Trigger Integration
```php
// app/Listeners/RuleTriggerListener.php
namespace App\Listeners;

use App\Events\ProductScanned;
use App\Events\UserRegistered;
use App\Events\RewardRedeemed;
use App\Events\AchievementUnlocked;
use App\Events\UserRankChanged;
use App\Services\RulesEngineService;
use App\Services\RuleContextService;

class RuleTriggerListener
{
    protected $rulesEngineService;
    protected $ruleContextService;
    
    public function __construct(
        RulesEngineService $rulesEngineService,
        RuleContextService $ruleContextService
    ) {
        $this->rulesEngineService = $rulesEngineService;
        $this->ruleContextService = $ruleContextService;
    }
    
    public function handleProductScanned(ProductScanned $event): void
    {
        $context = $this->ruleContextService->buildContextForEvent('product_scanned', [
            'user' => $event->user,
            'product' => $event->product,
            'is_first_scan' => $event->isFirstScan,
        ]);
        
        $this->rulesEngineService->evaluateRulesForEvent('product_scanned', $context);
    }
    
    public function handleUserRegistered(UserRegistered $event): void
    {
        $context = $this->ruleContextService->buildContextForEvent('user_registered', [
            'user' => $event->user,
        ]);
        
        $this->rulesEngineService->evaluateRulesForEvent('user_registered', $context);
    }
    
    public function handleRewardRedeemed(RewardRedeemed $event): void
    {
        $context = $this->ruleContextService->buildContextForEvent('reward_redeemed', [
            'user' => $event->user,
            'product' => $event->product,
        ]);
        
        $this->rulesEngineService->evaluateRulesForEvent('reward_redeemed', $context);
    }
    
    public function handleAchievementUnlocked(AchievementUnlocked $event): void
    {
        $context = $this->ruleContextService->buildContextForEvent('achievement_unlocked', [
            'user' => $event->user,
            'achievement' => $event->achievement,
        ]);
        
        $this->rulesEngineService->evaluateRulesForEvent('achievement_unlocked', $context);
    }
    
    public function handleUserRankChanged(UserRankChanged $event): void
    {
        $context = $this->ruleContextService->buildContextForEvent('user_rank_changed', [
            'user' => $event->user,
            'new_rank' => $event->newRank,
            'previous_rank' => $event->previousRank,
        ]);
        
        $this->rulesEngineService->evaluateRulesForEvent('user_rank_changed', $context);
    }
}
```

### Rule Builder API
```php
// app/Http/Controllers/Api/RuleBuilderController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRuleRequest;
use App\Models\Rule;
use App\Services\RulesEngineService;
use Illuminate\Http\Request;

class RuleBuilderController extends Controller
{
    protected $rulesEngineService;
    
    public function __construct(RulesEngineService $rulesEngineService)
    {
        $this->rulesEngineService = $rulesEngineService;
    }
    
    public function index()
    {
        $rules = Rule::with(['conditions', 'actions'])
            ->orderBy('priority', 'desc')
            ->paginate(20);
            
        return response()->json($rules);
    }
    
    public function store(StoreRuleRequest $request)
    {
        $validated = $request->validated();
        
        $rule = Rule::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? '',
            'event_trigger' => $validated['event_trigger'],
            'is_active' => $validated['is_active'] ?? true,
            'priority' => $validated['priority'] ?? 0,
            'conditions_logic' => $validated['conditions_logic'] ?? ['operator' => 'AND'],
            'execution_limit' => $validated['execution_limit'] ?? 0,
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
        ]);
        
        // Create conditions
        if (isset($validated['conditions'])) {
            foreach ($validated['conditions'] as $conditionData) {
                $rule->conditions()->create($conditionData);
            }
        }
        
        // Create actions
        if (isset($validated['actions'])) {
            foreach ($validated['actions'] as $actionData) {
                $rule->actions()->create($actionData);
            }
        }
        
        return response()->json($rule->load(['conditions', 'actions']), 201);
    }
    
    public function show(Rule $rule)
    {
        return response()->json($rule->load(['conditions', 'actions']));
    }
    
    public function update(StoreRuleRequest $request, Rule $rule)
    {
        $validated = $request->validated();
        
        $rule->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? '',
            'event_trigger' => $validated['event_trigger'],
            'is_active' => $validated['is_active'] ?? true,
            'priority' => $validated['priority'] ?? 0,
            'conditions_logic' => $validated['conditions_logic'] ?? ['operator' => 'AND'],
            'execution_limit' => $validated['execution_limit'] ?? 0,
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
        ]);
        
        // Update conditions
        if (isset($validated['conditions'])) {
            $rule->conditions()->delete();
            foreach ($validated['conditions'] as $conditionData) {
                $rule->conditions()->create($conditionData);
            }
        }
        
        // Update actions
        if (isset($validated['actions'])) {
            $rule->actions()->delete();
            foreach ($validated['actions'] as $actionData) {
                $rule->actions()->create($actionData);
            }
        }
        
        return response()->json($rule->load(['conditions', 'actions']));
    }
    
    public function destroy(Rule $rule)
    {
        $rule->delete();
        
        return response()->json(['success' => true]);
    }
    
    public function getAvailableConditions()
    {
        return response()->json($this->rulesEngineService->getAvailableConditions());
    }
    
    public function getAvailableActions()
    {
        return response()->json([
            'grant_points' => 'Grant Points',
            'unlock_achievement' => 'Unlock Achievement',
            'send_notification' => 'Send Notification',
            'update_user_meta' => 'Update User Meta',
            'create_task' => 'Create Task',
        ]);
    }
    
    public function testRule(Request $request, Rule $rule)
    {
        $request->validate([
            'context' => 'required|array',
        ]);
        
        $context = $request->input('context');
        
        $result = $this->rulesEngineService->evaluateRule($rule, $context);
        
        return response()->json([
            'rule_id' => $rule->id,
            'rule_name' => $rule->name,
            'passed' => $result,
        ]);
    }
}
```

## Data Migration Strategy

### From WordPress Rule Builder to Laravel Rules Engine
- Migrate `canna_trigger` custom post types to rules table
- Convert post meta for rule conditions and actions
- Migrate rule priority and execution limits
- Preserve rule scheduling and time-based constraints
- Convert achievement unlock rules to achievement system
- Maintain existing trigger-based bonus rules
- Ensure backward compatibility with existing rules

## Dependencies
- Laravel Framework
- Database (MySQL/PostgreSQL)
- Redis (for caching and queues)
- Eloquent ORM
- Laravel Collections
- Laravel Events

## Definition of Done
- [ ] Rule definitions can be created, edited, and managed through admin interface
- [ ] Rule conditions support complex logical expressions (AND/OR combinations)
- [ ] Rule actions execute correctly when conditions are met
- [ ] Rule triggers fire correctly for domain events
- [ ] Context variables are properly interpolated in rule actions
- [ ] Rule scheduling and time-based constraints work correctly
- [ ] Rule execution limits are properly enforced
- [ ] Rule evaluation performance meets benchmarks (< 50ms per rule)
- [ ] Rules are properly cached for performance (cache hit ratio > 95%)
- [ ] Rule execution logging provides complete audit trail
- [ ] Error handling gracefully manages rule evaluation failures
- [ ] Adequate test coverage for all rule engine functionality (100% of rule logic)
- [ ] Rule builder API provides complete CRUD operations
- [ ] Rule testing endpoint allows for rule validation
- [ ] Available conditions and actions are properly documented via API
- [ ] Rule context building provides complete user and product snapshots
- [ ] Background processing via Laravel queues for complex rule evaluation
- [ ] Proper validation using Laravel Form Requests for rule definitions
- [ ] Authorization policies enforce appropriate access controls for rules
- [ ] Event listeners correctly trigger rule evaluation for domain events
- [ ] Rule actions execute asynchronously with proper error handling
- [ ] Rule execution results are properly logged for debugging
- [ ] Complex nested conditions are correctly evaluated
- [ ] Rule versioning supports backward-compatible rule evolution
- [ ] Performance optimization through rule caching achieves sub-50ms evaluation
- [ ] Security measures prevent malicious rule definitions
- [ ] Integration testing validates complete rule engine functionality
- [ ] Documentation provides clear examples for common rule patterns
- [ ] Monitoring captures rule engine performance metrics
- [ ] Alerting notifies administrators of rule engine failures
- [ ] Backup and restore procedures protect rule definitions
- [ ] Migration from legacy system preserves all existing rules