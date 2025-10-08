<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Jobs\SendOrderConfirmation;
use App\Domain\ValueObjects\UserId;
use App\Domain\ValueObjects\Points;
use App\Commands\GrantPointsCommand;
use App\Events\RewardRedeemed;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;


class RedemptionService
{
    protected $economyService;
    protected $productRepository;
    protected $orderService;
    protected $userPolicyService;
    
    public function __construct(
        EconomyService $economyService,
        \App\Repositories\ProductRepository $productRepository,
        OrderService $orderService,
        UserPolicyService $userPolicyService
    ) {
        $this->economyService = $economyService;
        $this->productRepository = $productRepository;
        $this->orderService = $orderService;
        $this->userPolicyService = $userPolicyService;
    }
    
    /**
     * Process a product redemption for a user.
     *
     * @param \App\Models\User $user The user making the redemption
     * @param int $productId The ID of the product being redeemed
     * @param array $shippingDetails Shipping details for the order
     * @return \App\Models\Order The created order
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If the product is not found
     * @throws \Exception If user is not eligible for redemption
     */
    public function processRedemption(User $user, int $productId, array $shippingDetails): Order
    {
        // Validate shipping details
        $this->validateShippingDetails($shippingDetails);
        
        // Find product - convert int to ProductId value object
        $productIdVO = \App\Domain\ValueObjects\ProductId::fromInt($productId);
        $product = $this->productRepository->find($productIdVO);
        if (!$product) {
            throw new ModelNotFoundException("Product with ID {$productId} not found.");
        }
        
        // Check user eligibility
        $this->validateUserEligibility($user, $product);
        
        // Check policy requirements
        $this->enforcePolicies($user, $product);
        
        // Deduct points from user
        $this->deductPoints($user, $product);
        
        // Create order
        $order = $this->createOrderFromRedemption($user, $product, $shippingDetails);
        
        // Fire event - pass an array with the required context
        event(new RewardRedeemed([
            'user' => $user,
            'product' => $product,
            'order' => $order
        ]));
        
        return $order;
    }
    
    /**
     * Validates the shipping details provided for an order.
     *
     * @param array $shippingDetails The shipping details to validate
     * @return void
     * @throws \Exception If any required shipping field is missing or invalid
     */
    protected function validateShippingDetails(array $shippingDetails): void
    {
        $requiredFields = ['first_name', 'last_name', 'address_1', 'city', 'state', 'postcode'];
        
        foreach ($requiredFields as $field) {
            if (empty($shippingDetails[$field])) {
                throw new \Exception("Shipping {$field} is required.");
            }
        }
        
        // Validate postcode format (simple US format)
        if (!preg_match('/^\d{5}(-\d{4})?$/', $shippingDetails['postcode'])) {
            throw new \Exception("Invalid postcode format.");
        }
        
        // Validate state format (2-letter US state)
        if (!preg_match('/^[A-Z]{2}$/', $shippingDetails['state'])) {
            throw new \Exception("Invalid state format.");
        }
    }
    
    /**
     * Validates if the user is eligible to redeem the product.
     *
     * @param \App\Models\User $user The user to validate
     * @param \App\Models\Product $product The product being redeemed
     * @return void
     * @throws \Exception If user has insufficient points or doesn't meet rank requirements
     */
    protected function validateUserEligibility(User $user, Product $product): void
    {
        // Check if user can afford redemption
        if ($user->points_balance < $product->points_cost) {
            throw new \Exception('Insufficient points for redemption.', 402);
        }
        
        // Check rank requirements
        if (!$this->userPolicyService->meetsRankRequirement($user, $product)) {
            throw new \Exception("You must be rank '{$product->required_rank_key}' or higher to redeem this item.", 403);
        }
    }
    
    /**
     * Enforces business policies for the redemption.
     *
     * @param \App\Models\User $user The user making the redemption
     * @param \App\Models\Product $product The product being redeemed
     * @return void
     * @throws \Exception If user doesn't meet policy requirements
     */
    protected function enforcePolicies(User $user, Product $product): void
    {
        // Check if user can afford redemption (duplicate check for extra safety)
        if (!$this->userPolicyService->canAffordRedemption($user, $product)) {
            throw new \Exception('Insufficient points.', 402);
        }
        
        // Check rank requirement (duplicate check for extra safety)
        if (!$this->userPolicyService->meetsRankRequirement($user, $product)) {
            throw new \Exception("You must be rank '{$product->required_rank_key}' or higher to redeem this item.", 403);
        }
    }
    
    /**
     * Deducts the required points from the user's balance.
     *
     * @param \App\Models\User $user The user to deduct points from
     * @param \App\Models\Product $product The product being redeemed
     * @return void
     */
    protected function deductPoints(User $user, Product $product): void
    {
        // Instead of trying to use GrantPointsCommand with negative values that cause an error,
        // handle the deduction directly using the UserRepository
        $newPointsBalance = $user->points_balance - $product->points_cost;
        
        // Use the userRepository from the EconomyService through its public interface
        // Keep the lifetime points unchanged - lifetime points should only increase, not decrease
        $currentLifetimePoints = $this->economyService->getUserRepository()->getLifetimePoints(UserId::fromInt($user->id));
        
        $this->economyService->getUserRepository()->savePointsAndRank(
            UserId::fromInt($user->id),
            $newPointsBalance,
            $currentLifetimePoints, // Lifetime points remain unchanged during redemption
            $user->current_rank_key
        );
        
        // Update the user model's meta directly for the current request
        $user->meta = array_merge($user->meta ?? [], [\App\Domain\MetaKeys::POINTS_BALANCE => $newPointsBalance]);
        $user->save();
    }
    
    /**
     * Creates an order from a redemption.
     *
     * @param \App\Models\User $user The user making the redemption
     * @param \App\Models\Product $product The product being redeemed
     * @param array $shippingDetails Shipping details for the order
     * @return \App\Models\Order The created order
     */
    protected function createOrderFromRedemption(User $user, Product $product, array $shippingDetails): Order
    {
        // Use the order repository to create the order
        $orderId = $this->orderService->createFromRedemption(
            \App\Domain\ValueObjects\UserId::fromInt($user->id),
            \App\Domain\ValueObjects\ProductId::fromInt($product->id),
            $shippingDetails
        );
        
        // Get the created order
        $order = Order::find($orderId);
        
        // Update user's shipping address using UserRepository
        $this->economyService->getUserRepository()->saveShippingAddress(
            UserId::fromInt($user->id),
            [
                'firstName' => $shippingDetails['first_name'],
                'lastName' => $shippingDetails['last_name'],
                'address1' => $shippingDetails['address_1'],
                'city' => $shippingDetails['city'],
                'state' => $shippingDetails['state'],
                'zip' => $shippingDetails['postcode'],
                'country' => $shippingDetails['country'] ?? 'US',
                'phone' => $shippingDetails['phone'] ?? null,
            ]
        );
        
        return $order;
    }
}
