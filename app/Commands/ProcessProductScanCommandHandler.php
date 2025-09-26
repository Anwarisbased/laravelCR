<?php
namespace App\Commands;

use App\Repositories\RewardCodeRepository;
use App\Repositories\ProductRepository;
use App\Repositories\ActionLogRepository;
use App\Services\ActionLogService;
use App\Services\ContextBuilderService;
use App\Includes\EventBusInterface;
use App\Infrastructure\WordPressApiWrapperInterface;
use Exception;

final class ProcessProductScanCommandHandler {
    private RewardCodeRepository $rewardCodeRepo;
    private ProductRepository $productRepo;
    private ActionLogRepository $logRepo;
    private ActionLogService $logService;
    private EventBusInterface $eventBus;
    private ContextBuilderService $contextBuilder;
    private WordPressApiWrapperInterface $wp;

    public function __construct(
        RewardCodeRepository $rewardCodeRepo,
        ProductRepository $productRepo,
        ActionLogRepository $logRepo,
        ActionLogService $logService,
        EventBusInterface $eventBus,
        ContextBuilderService $contextBuilder,
        WordPressApiWrapperInterface $wp
    ) {
        $this->rewardCodeRepo = $rewardCodeRepo;
        $this->productRepo = $productRepo;
        $this->logRepo = $logRepo;
        $this->logService = $logService;
        $this->eventBus = $eventBus;
        $this->contextBuilder = $contextBuilder;
        $this->wp = $wp;
    }

    public function handle(ProcessProductScanCommand $command): array {
        \Illuminate\Support\Facades\Log::info('ProcessProductScanCommandHandler.handle called', [
            'user_id' => $command->userId->toInt(),
            'code' => $command->code->value
        ]);
        
        $code_data = $this->rewardCodeRepo->findValidCode($command->code);
        if (!$code_data) { 
            \Illuminate\Support\Facades\Log::info('ProcessProductScanCommandHandler: Code not found or already used');
            throw new Exception('This code is invalid or has already been used.'); 
        }
        
        $product_id = $this->productRepo->findIdBySku(\App\Domain\ValueObjects\Sku::fromString($code_data->sku));
        if (!$product_id) { 
            \Illuminate\Support\Facades\Log::info('ProcessProductScanCommandHandler: Product not found for SKU', ['sku' => $code_data->sku]);
            throw new Exception('The product associated with this code could not be found.'); 
        }
        
        // --- ANTI-FRAGILE REFACTOR ---

        // 1. Log the scan to establish its history and count.
        $this->logService->record($command->userId->toInt(), 'scan', $product_id->toInt());
        $scan_count = $this->logRepo->countUserActions($command->userId->toInt(), 'scan');
        $is_first_scan = ($scan_count === 1);

        // 2. Mark the code as used immediately.
        $this->rewardCodeRepo->markCodeAsUsed($code_data->id, $command->userId);
        
        // 3. Build the rich context for the event.
        $product_post = $product_id ? (object)['ID' => $product_id->toInt()] : null;
        $context = $this->contextBuilder->build_event_context($command->userId->toInt(), $product_post);

        // 4. BE EXPLICIT: Dispatch a different event based on the business context.
        if ($is_first_scan) {
            \Illuminate\Support\Facades\Log::info('Dispatching "first_product_scanned" event', ['context' => $context]);
            $this->eventBus->dispatch('first_product_scanned', $context);
        } else {
            \Illuminate\Support\Facades\Log::info('Dispatching "standard_product_scanned" event', ['context' => $context]);
            $this->eventBus->dispatch('standard_product_scanned', $context);
        }
        
        // 5. Return a generic, immediate success message.
        $product = $product_id ? $this->wp->getProduct($product_id->toInt()) : null;
        return [
            'success' => true,
            'message' => ($product ? $product->name : 'Product') . ' scanned successfully!',
        ];
        // --- END REFACTOR ---
    }
}