<?php

namespace Tests\Unit\DataObjects;

use Tests\TestCase;
use App\Data\UserData;
use App\Data\RankData;
use App\Data\AchievementData;
use App\Data\OrderData;
use App\Data\SessionData;
use App\Data\ProfileData;
use App\Data\Catalog\ProductData;
use App\Data\Catalog\CategoryData;
use App\Data\Catalog\EligibilityData;
use App\Models\User;
use App\Models\Rank;
use App\Models\Achievement;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;

class DataObjectsTest extends TestCase
{
    public function test_user_data_from_model_transforms_correctly(): void
    {
        // Create a mock user model using factory
        $user = User::factory()->create();

        // Transform to UserData
        $userData = UserData::fromModel($user);

        // Assertions
        $this->assertInstanceOf(UserData::class, $userData);
        $this->assertEquals($user->id, $userData->id);
        $this->assertEquals($user->name, $userData->name);
        $this->assertEquals($user->email, $userData->email);
    }

    public function test_rank_data_from_model_transforms_correctly(): void
    {
        // Create a mock rank model using factory
        $rank = Rank::factory()->create([
            'key' => 'test-bronze-' . time(), // Unique key to avoid conflicts
            'name' => 'Test Bronze',
            'points_required' => 0,
            'point_multiplier' => 1.0,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        // Transform to RankData
        $rankData = RankData::fromModel($rank);

        // Assertions
        $this->assertInstanceOf(RankData::class, $rankData);
        $this->assertEquals($rank->key, $rankData->key);
        $this->assertEquals($rank->name, $rankData->name);
        $this->assertEquals($rank->description, $rankData->description);
        $this->assertEquals($rank->points_required, $rankData->pointsRequired);
        $this->assertEquals($rank->point_multiplier, $rankData->pointMultiplier);
        $this->assertEquals($rank->is_active, $rankData->isActive);
        $this->assertEquals($rank->sort_order, $rankData->sortOrder);
    }

    public function test_achievement_data_from_model_transforms_correctly(): void
    {
        // Create a mock achievement model directly
        $achievement = new Achievement();
        $achievement->achievement_key = 'test-scan-' . time(); // Unique key to avoid conflicts
        $achievement->title = 'Test Scan';
        $achievement->description = 'Complete your test scan';
        $achievement->points_reward = 10;
        $achievement->rarity = 'common';
        $achievement->icon_url = 'https://example.com/icon.png';
        $achievement->is_active = true;
        $achievement->trigger_event = 'scan.completed';
        $achievement->trigger_count = 1;
        $achievement->conditions = [];
        $achievement->category = 'scan';
        $achievement->sort_order = 1;
        $achievement->type = 'standard';
        $achievement->save();

        // Transform to AchievementData
        $achievementData = AchievementData::fromModel($achievement);

        // Assertions
        $this->assertInstanceOf(AchievementData::class, $achievementData);
        $this->assertEquals($achievement->achievement_key, $achievementData->key);
        $this->assertEquals($achievement->title, $achievementData->title);
        $this->assertEquals($achievement->description, $achievementData->description);
        $this->assertEquals($achievement->points_reward, $achievementData->pointsReward);
        $this->assertEquals($achievement->rarity, $achievementData->rarity);
        $this->assertEquals($achievement->icon_url, $achievementData->iconUrl);
        $this->assertEquals($achievement->is_active, $achievementData->isActive);
        $this->assertEquals($achievement->trigger_event, $achievementData->triggerEvent);
        $this->assertEquals($achievement->trigger_count, $achievementData->triggerCount);
        $this->assertEquals($achievement->conditions, $achievementData->conditions);
        $this->assertEquals($achievement->category, $achievementData->category);
        $this->assertEquals($achievement->sort_order, $achievementData->sortOrder);
        $this->assertEquals($achievement->type, $achievementData->type);
    }

    public function test_order_data_from_model_transforms_correctly(): void
    {
        // Create a user first
        $user = User::factory()->create();
        
        // Create a mock order model with valid user
        $order = new Order();
        $order->user_id = $user->id;
        $order->order_number = 'ORD-' . time();
        $order->status = 'pending';
        $order->points_cost = 100;
        $order->is_canna_redemption = true;
        $order->save();

        // Transform to OrderData
        $orderData = OrderData::fromModel($order);

        // Assertions
        $this->assertInstanceOf(OrderData::class, $orderData);
        $this->assertEquals($order->id, $orderData->id);
        $this->assertEquals($order->order_number, $orderData->orderNumber);
        $this->assertEquals($order->status, $orderData->status);
        $this->assertEquals(ucfirst($order->status), $orderData->statusDisplay);
        $this->assertEquals($order->points_cost, $orderData->pointsCost);
        $this->assertEquals($order->is_canna_redemption, $orderData->isCannaRedemption);
    }

    public function test_product_data_from_model_transforms_correctly(): void
    {
        // Create a mock product model directly
        $product = new Product();
        $product->name = 'Test Product';
        $product->sku = 'TEST-' . time(); // Unique SKU to avoid conflicts
        $product->description = 'Test product description';
        $product->short_description = 'Short description';
        $product->points_award = 50;
        $product->points_cost = 100;
        $product->required_rank_key = 'bronze';
        $product->is_active = true;
        $product->is_featured = true;
        $product->is_new = false;
        $product->brand = 'Test Brand';
        $product->strain_type = 'hybrid';
        $product->thc_content = 15.5;
        $product->cbd_content = 2.1;
        $product->product_form = 'flower';
        $product->marketing_snippet = 'Great product!';
        $product->image_urls = ['https://example.com/image1.jpg'];
        $product->tags = ['tag1', 'tag2'];
        $product->sort_order = 1;
        $product->available_from = now();
        $product->available_until = now()->addDays(30);
        $product->status = 'publish';
        $product->save();

        // Transform to ProductData
        $productData = ProductData::fromModel($product);

        // Assertions
        $this->assertInstanceOf(ProductData::class, $productData);
        $this->assertEquals($product->id, $productData->id);
        $this->assertEquals($product->name, $productData->name);
        $this->assertEquals($product->sku, $productData->sku);
        $this->assertEquals($product->description, $productData->description);
        $this->assertEquals($product->short_description, $productData->shortDescription);
        $this->assertEquals($product->points_award, $productData->pointsAward);
        $this->assertEquals($product->points_cost, $productData->pointsCost);
        $this->assertEquals($product->required_rank_key, $productData->requiredRankKey);
        $this->assertEquals($product->is_active, $productData->isActive);
        $this->assertEquals($product->is_featured, $productData->isFeatured);
        $this->assertEquals($product->is_new, $productData->isNew);
        $this->assertEquals($product->brand, $productData->brand);
        $this->assertEquals($product->strain_type, $productData->strainType);
        $this->assertEquals($product->thc_content, $productData->thcContent);
        $this->assertEquals($product->cbd_content, $productData->cbdContent);
        $this->assertEquals($product->product_form, $productData->productForm);
        $this->assertEquals($product->marketing_snippet, $productData->marketingSnippet);
        $this->assertEquals($product->image_urls, $productData->imageUrls);
        $this->assertEquals($product->tags, $productData->tags);
        $this->assertEquals($product->sort_order, $productData->sortOrder);
        $this->assertNotNull($productData->createdAt);
        $this->assertNotNull($productData->updatedAt);
    }

    public function test_category_data_from_model_transforms_correctly(): void
    {
        // Create a mock category model directly
        $category = new ProductCategory();
        $category->name = 'Test Category';
        $category->slug = 'test-category-' . time(); // Unique slug to avoid conflicts
        $category->description = 'Test category description';
        $category->parent_id = null;
        $category->sort_order = 1;
        $category->is_active = true;
        $category->save();

        // Transform to CategoryData
        $categoryData = CategoryData::fromModel($category);

        // Assertions
        $this->assertInstanceOf(CategoryData::class, $categoryData);
        $this->assertEquals($category->id, $categoryData->id);
        $this->assertEquals($category->name, $categoryData->name);
        $this->assertEquals($category->slug, $categoryData->slug);
        $this->assertEquals($category->description, $categoryData->description);
        $this->assertEquals($category->parent_id, $categoryData->parentId);
        $this->assertEquals($category->sort_order, $categoryData->sortOrder);
        $this->assertEquals($category->is_active, $categoryData->isActive);
    }

    public function test_eligibility_data_from_array_transforms_correctly(): void
    {
        // Create eligibility data array
        $eligibilityDataArray = [
            'is_eligible' => true,
            'reasons' => ['sufficient_points', 'rank_eligible'],
            'eligible_for_free_claim' => false,
        ];

        // Transform to EligibilityData
        $eligibilityData = EligibilityData::fromArray($eligibilityDataArray);

        // Assertions
        $this->assertInstanceOf(EligibilityData::class, $eligibilityData);
        $this->assertEquals($eligibilityDataArray['is_eligible'], $eligibilityData->isEligible);
        $this->assertEquals($eligibilityDataArray['reasons'], $eligibilityData->reasons);
        $this->assertEquals($eligibilityDataArray['eligible_for_free_claim'], $eligibilityData->eligibleForFreeClaim);
    }

    public function test_data_objects_have_validation_attributes(): void
    {
        // Reflection to check if properties have validation attributes
        $dataClasses = [
            UserData::class,
            RankData::class,
            AchievementData::class,
            OrderData::class,
            ProductData::class,
            CategoryData::class,
            EligibilityData::class,
        ];

        foreach ($dataClasses as $class) {
            $reflection = new \ReflectionClass($class);
            $constructor = $reflection->getConstructor();
            $parameters = $constructor->getParameters();

            foreach ($parameters as $parameter) {
                $attributes = $parameter->getAttributes();
                $hasValidation = false;

                foreach ($attributes as $attribute) {
                    if ($attribute->getName() === 'Spatie\LaravelData\Attributes\Validation') {
                        $hasValidation = true;
                        break;
                    }
                }

                // Skip specific properties that might legitimately not have validation
                $paramName = $parameter->getName();
                $exemptFields = ['id', 'items', 'shippingAddress', 'meta', 'metaData', 'reasons', 'conditions', 'tags', 'imageUrls', 'eligibility', 'category', 'benefits', 'createdAt', 'updatedAt'];
                
                if (!in_array($paramName, $exemptFields)) {
                    $this->assertTrue($hasValidation, "Property {$paramName} in {$class} should have Validation attribute");
                }
            }
        }
    }

    public function test_session_data_from_dto_transforms_correctly(): void
    {
        // Create a mock session DTO with all required properties
        $sessionDto = new class {
            public $id;
            public $firstName;
            public $lastName;
            public $email;
            public $pointsBalance;
            public $rank;
            public $shippingAddress;
            public $referralCode;
            public $featureFlags;

            public function __construct() {
                $this->id = new class {
                    public function toInt() { return 1; }
                };
                $this->firstName = 'John';
                $this->lastName = 'Doe';
                $this->email = new class {
                    public function __toString() { return 'john.doe@example.com'; }
                };
                $this->pointsBalance = new class {
                    public function toInt() { return 500; }
                };
                $this->rank = new class {
                    public $key;
                    public $name;
                    public $pointsRequired;
                    public $pointMultiplier;

                    public function __construct() {
                        $this->key = new class {
                            public function __toString() { return 'bronze'; }
                        };
                        $this->name = 'Bronze';
                        $this->pointsRequired = new class {
                            public function toInt() { return 0; }
                        };
                        $this->pointMultiplier = 1.0;
                    }
                };
                $this->shippingAddress = (object) [
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'address_1' => '123 Main St',
                    'city' => 'Anytown',
                    'state' => 'CA',
                    'postcode' => '12345',
                    'country' => 'US'
                ];
                $this->referralCode = 'ABC123';
                $this->featureFlags = (object) [];
            }
        };

        // Transform to SessionData
        $sessionData = SessionData::fromSessionDto($sessionDto);

        // Assertions
        $this->assertInstanceOf(SessionData::class, $sessionData);
        $this->assertEquals(1, $sessionData->id);
        $this->assertEquals('John', $sessionData->firstName);
        $this->assertEquals('Doe', $sessionData->lastName);
        $this->assertEquals('john.doe@example.com', $sessionData->email);
        $this->assertEquals(500, $sessionData->pointsBalance);
        $this->assertIsArray($sessionData->rank);
        $this->assertEquals('bronze', $sessionData->rank['key']);
        $this->assertEquals('Bronze', $sessionData->rank['name']);
        $this->assertEquals(0, $sessionData->rank['points_required']);
        $this->assertEquals(1.0, $sessionData->rank['point_multiplier']);
        $this->assertIsArray($sessionData->shippingAddress);
        $this->assertEquals('ABC123', $sessionData->referralCode);
        $this->assertIsArray($sessionData->featureFlags);
    }

    public function test_profile_data_from_dto_transforms_correctly(): void
    {
        // Create a mock profile DTO with all required properties
        $profileDto = new class {
            public $firstName;
            public $lastName;
            public $phoneNumber;
            public $referralCode;
            public $shippingAddress;
            public $unlockedAchievementKeys;
            public $customFields;

            public function __construct() {
                $this->firstName = 'John';
                $this->lastName = 'Doe';
                $this->phoneNumber = new class {
                    public function __toString() { return '555-1234'; }
                };
                $this->referralCode = new class {
                    public function __toString() { return 'ABC123'; }
                };
                $this->shippingAddress = (object) [
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'address_1' => '123 Main St',
                    'city' => 'Anytown',
                    'state' => 'CA',
                    'postcode' => '12345',
                    'country' => 'US'
                ];
                $this->unlockedAchievementKeys = ['first_scan', 'welcome_bonus'];
                $this->customFields = (object) [
                    'definitions' => [],
                    'values' => (object) []
                ];
            }
        };

        // Transform to ProfileData
        $profileData = ProfileData::fromProfileDto($profileDto);

        // Assertions
        $this->assertInstanceOf(ProfileData::class, $profileData);
        $this->assertEquals('John', $profileData->firstName);
        $this->assertEquals('Doe', $profileData->lastName);
        $this->assertIsString($profileData->phoneNumber);
        $this->assertEquals('555-1234', $profileData->phoneNumber);
        $this->assertIsString($profileData->referralCode);
        $this->assertEquals('ABC123', $profileData->referralCode);
        $this->assertIsArray($profileData->shippingAddress);
        $this->assertEquals(['first_scan', 'welcome_bonus'], $profileData->unlockedAchievementKeys);
        $this->assertIsArray($profileData->customFields);
    }
}