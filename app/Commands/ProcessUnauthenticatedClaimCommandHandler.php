<?php
namespace App\Commands;

use App\Domain\ValueObjects\Sku;
use App\Repositories\RewardCodeRepository;
use App\Repositories\ProductRepository;
use App\Services\ConfigService; // <<<--- IMPORT THE SERVICE
use Illuminate\Support\Facades\Cache; // <<<--- IMPORT CACHE FACADE FOR TRANSIENTS
use Illuminate\Support\Facades\Storage; // <<<--- IMPORT STORAGE FACADE
use Exception;

final class ProcessUnauthenticatedClaimCommandHandler {
    private $reward_code_repository;
    private $product_repository;
    private ConfigService $configService; // <<<--- ADD PROPERTY

    public function __construct(
        RewardCodeRepository $reward_code_repository,
        ProductRepository $product_repository,
        ConfigService $configService // <<<--- INJECT DEPENDENCY
    ) {
        $this->reward_code_repository = $reward_code_repository;
        $this->product_repository = $product_repository;
        $this->configService = $configService;
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
        // REFACTOR: Use Laravel Cache facade instead of WordPress transients
        Cache::put('reg_token_' . $registration_token, (string)$command->code, 15 * 60); // 15 minutes in seconds
        
        // REFACTOR: Use the injected ConfigService
        $welcome_reward_id = $this->configService->getWelcomeRewardProductId();
        
        // In a pure Laravel implementation, we'd have a proper Product model
        // For now, let's return a basic placeholder product
        $product = null;
        if ($welcome_reward_id) {
            $product = (object)[
                'id' => $welcome_reward_id,
                'name' => 'Welcome Gift',
            ];
        }

        return [
            'status'             => 'registration_required',
            'registration_token' => $registration_token,
            'reward_preview'     => [
                'id' => $product ? $product->id : 0,
                'name' => $product ? $product->name : 'Welcome Gift',
                'image' => $product ? Storage::url('products/' . $product->id . '.jpg') : '/images/placeholder.png', // Using Laravel storage
            ]
        ];
    }
}