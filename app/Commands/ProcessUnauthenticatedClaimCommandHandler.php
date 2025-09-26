<?php
namespace App\Commands;

use App\Domain\ValueObjects\Sku;
use App\Repositories\RewardCodeRepository;
use App\Repositories\ProductRepository;
use App\Services\ConfigService; // <<<--- IMPORT THE SERVICE
use App\Infrastructure\WordPressApiWrapperInterface; // <<<--- IMPORT THE WRAPPER
use Exception;

final class ProcessUnauthenticatedClaimCommandHandler {
    private $reward_code_repository;
    private $product_repository;
    private ConfigService $configService; // <<<--- ADD PROPERTY
    private WordPressApiWrapperInterface $wp; // <<<--- ADD PROPERTY

    public function __construct(
        RewardCodeRepository $reward_code_repository,
        ProductRepository $product_repository,
        ConfigService $configService, // <<<--- INJECT DEPENDENCY
        WordPressApiWrapperInterface $wp // <<<--- INJECT DEPENDENCY
    ) {
        $this->reward_code_repository = $reward_code_repository;
        $this->product_repository = $product_repository;
        $this->configService = $configService;
        $this->wp = $wp;
    }

    public function handle(ProcessUnauthenticatedClaimCommand $command): array {
        $code_data = $this->reward_code_repository->findValidCode($command->code);
        if (!$code_data) {
            throw new Exception('This code is invalid or has already been used.');
        }

        $product_id = $this->product_repository->findIdBySku(Sku::fromString($code_data->sku));
        if (!$product_id) {
            throw new Exception('The product associated with this code could not be found.');
        }

        $registration_token = bin2hex(random_bytes(32));
        // REFACTOR: Use the wrapper to set the transient
        $this->wp->setTransient('reg_token_' . $registration_token, (string)$command->code, 15 * 60); // 15 minutes in seconds
        
        // REFACTOR: Use the injected ConfigService
        $welcome_reward_id = $this->configService->getWelcomeRewardProductId();
        $product = $welcome_reward_id ? $this->wp->getProduct($welcome_reward_id) : null;

        return [
            'status'             => 'registration_required',
            'registration_token' => $registration_token,
            'reward_preview'     => [
                'id' => $product ? $product->id : 0,
                'name' => $product ? $product->name : 'Welcome Gift',
                'image' => $product ? '/storage/products/' . $product->id . '.jpg' : '/images/placeholder.png', // Using Laravel placeholder
            ]
        ];
    }
}