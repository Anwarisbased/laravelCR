<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Handle Customer.io webhook
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function handleCustomerIoWebhook(Request $request): JsonResponse
    {
        // Verify webhook signature (this is a simplified implementation)
        $signature = $request->header('X-CustomerIO-Signature');
        
        // In production, you would verify the signature using Customer.io's signature verification
        // For now, we'll trust the webhook during development
        
        try {
            $payload = $request->all();
            
            // Log the received webhook for debugging
            Log::info('Customer.io Webhook Received', [
                'payload' => $payload
            ]);

            // Process the webhook based on its type
            if (isset($payload['user_id'])) {
                $userId = $payload['user_id'];
                
                // Update user's AI profile with Customer.io insights
                if (isset($payload['predictions'])) {
                    $this->userService->updateUserAiProfile($userId, $payload['predictions']);
                }
            }

            return response()->json(['success' => true], 200);
        } catch (\Exception $e) {
            Log::error('Error processing Customer.io webhook', [
                'error' => $e->getMessage(),
                'payload' => $request->all()
            ]);
            
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}