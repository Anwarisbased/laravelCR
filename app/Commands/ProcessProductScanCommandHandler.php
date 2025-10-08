<?php
namespace App\Commands;

use App\Events\FirstProductScanned;
use App\Events\StandardProductScanned;
use App\Repositories\RewardCodeRepository;
use App\Repositories\ProductRepository;
use App\Repositories\ActionLogRepository;
use App\Services\ActionLogService;
use App\Services\ContextBuilderService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

final class ProcessProductScanCommandHandler {
    private RewardCodeRepository $rewardCodeRepo;
    private ProductRepository $productRepo;
    private ActionLogRepository $logRepo;
    private ActionLogService $logService;
    private ContextBuilderService $contextBuilder;

    public function __construct(
        RewardCodeRepository $rewardCodeRepo,
        ProductRepository $productRepo,
        ActionLogRepository $logRepo,
        ActionLogService $logService,
        ContextBuilderService $contextBuilder
    ) {
        $this->rewardCodeRepo = $rewardCodeRepo;
        $this->productRepo = $productRepo;
        $this->logRepo = $logRepo;
        $this->logService = $logService;
        $this->contextBuilder = $contextBuilder;
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
        $this->logService->record($command->userId, 'scan', $product_id->toInt());
        $scan_count = $this->logRepo->countUserActions($command->userId, 'scan');
        \Illuminate\Support\Facades\Log::info('ProcessProductScanCommandHandler: Scan count', [
            'user_id' => $command->userId->toInt(),
            'scan_count' => $scan_count
        ]);
        $is_first_scan = ($scan_count === 1);
        \Illuminate\Support\Facades\Log::info('ProcessProductScanCommandHandler: Is first scan', [
            'is_first_scan' => $is_first_scan
        ]);

        // 2. Mark the code as used immediately.
        $this->rewardCodeRepo->markCodeAsUsed($code_data->id, $command->userId);
        
        // 3. Build the rich context for the event.
        $product_post = $product_id ? (object)['ID' => $product_id->toInt()] : null;
        $context = $this->contextBuilder->build_event_context($command->userId, $product_post);

        // 4. BE EXPLICIT: Dispatch a different event based on the business context.
        if ($is_first_scan) {
            \Illuminate\Support\Facades\Log::info('Dispatching "first_product_scanned" event', ['context' => $context]);
            Event::dispatch(new FirstProductScanned($context));
        } else {
            \Illuminate\Support\Facades\Log::info('Dispatching "standard_product_scanned" event', ['context' => $context]);
            Event::dispatch(new StandardProductScanned($context));
        }
        
        // 5. Return a generic, immediate success message.
        // In a pure Laravel implementation, we'd have a proper Product model
        $product = null;
        if ($product_id) {
            $product = DB::table('products')->where('id', $product_id->toInt())->first();
        }
        return [
            'success' => true,
            'message' => ($product ? $product->name : 'Product') . ' scanned successfully!',
        ];
        // --- END REFACTOR ---
    }
}