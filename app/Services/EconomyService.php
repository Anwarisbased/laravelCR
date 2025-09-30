<?php
namespace App\Services;

use App\Commands\GrantPointsCommand;
use App\Commands\GrantPointsCommandHandler;
use App\Domain\ValueObjects\UserId;
use App\Events\PointsToBeGranted;
use App\Events\UserPointsGranted;
use App\Policies\AuthorizationPolicyInterface;
use App\Policies\ValidationPolicyInterface;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Exception;
use Psr\Container\ContainerInterface;

final class EconomyService {
    private array $command_map; // Changed from private property to constructor-injected
    private array $policy_map;
    private ContainerInterface $container;
    private RankService $rankService;
    private ContextBuilderService $contextBuilder;
    private UserRepository $userRepository;
    private GrantPointsCommandHandler $grantPointsHandler;

    public function __construct(
        ContainerInterface $container,
        array $policy_map,
        array $command_map, // Inject the command map
        RankService $rankService,
        ContextBuilderService $contextBuilder,
        UserRepository $userRepository,
        GrantPointsCommandHandler $grantPointsHandler
    ) {
        $this->container = $container;
        $this->policy_map = $policy_map;
        $this->command_map = $command_map; // Assign the injected map
        $this->rankService = $rankService;
        $this->contextBuilder = $contextBuilder;
        $this->userRepository = $userRepository;
        $this->grantPointsHandler = $grantPointsHandler;
    }

    // This map now declaratively defines all business rules for a command.
    private function getPolicyMap(): array
    {
        return [
            \App\Commands\ProcessUnauthenticatedClaimCommand::class => [
                'validation' => [
                    // PolicyClass => function that extracts the VO from the command
                    \App\Policies\UnauthenticatedCodeIsValidPolicy::class => fn($cmd) => $cmd->code,
                ],
                'authorization' => []
            ],
            \App\Commands\RedeemRewardCommand::class => [
                'validation' => [],
                'authorization' => [
                    \App\Policies\UserMustBeAbleToAffordRedemptionPolicy::class,
                    \App\Policies\UserMustMeetRankRequirementPolicy::class,
                ]
            ],
        ];
    }
    
    public function handle($command) {
        $commandClass = get_class($command);
        $policyMap = $this->getPolicyMap()[$commandClass] ?? [];

        try {
            // --- Run Validation Policies ---
            foreach ($policyMap['validation'] ?? [] as $policyClass => $valueExtractor) {
                /** @var ValidationPolicyInterface $policy */
                $policy = $this->container->get($policyClass);
                $valueToValidate = $valueExtractor($command);
                Log::info("Running policy: " . $policyClass);
                $policy->check($valueToValidate);
            }

            // --- Run Authorization Policies ---
            if (!empty($policyMap['authorization'] ?? [])) {
                $userId = $command->userId; // Only extract userId if authorization policies exist
                foreach ($policyMap['authorization'] ?? [] as $policyClass) {
                    /** @var AuthorizationPolicyInterface $policy */
                    $policy = $this->container->get($policyClass);
                    $policy->check($userId, $command);
                }
            }
        } catch (\Exception $e) {
            Log::error("Exception in EconomyService: " . $e->getMessage() . " Code: " . $e->getCode());
            throw $e;
        }

        // The service now uses the injected map to find the correct handler.
        // It no longer has internal knowledge of which handlers exist.
        if (!isset($this->command_map[$commandClass])) {
            throw new Exception("No economy handler registered for command: {$commandClass}");
        }
        
        $handler_class = $this->command_map[$commandClass];
        $handler = $this->container->get($handler_class); // Use container to build the handler
        return $handler->handle($command);
    }
    
    public static function createWithDependencies($container): self
    {
        return new self(
            $container, // ContainerInterface
            [], // policy_map - can be configured with specific command => policy mappings
            [
                // command_map - can be configured with specific command => handler mappings
                \App\Commands\RedeemRewardCommand::class => \App\Commands\RedeemRewardCommandHandler::class,
                \App\Commands\GrantPointsCommand::class => \App\Commands\GrantPointsCommandHandler::class,
                \App\Commands\ProcessProductScanCommand::class => \App\Commands\ProcessProductScanCommandHandler::class,
            ],
            $container->make(RankService::class),
            $container->make(ContextBuilderService::class),
            $container->make(UserRepository::class),
            $container->make(\App\Commands\GrantPointsCommandHandler::class)
        );
    }
}