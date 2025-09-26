Action Plan: Phase 1 - Achieve 100% Feature Parity
Step 1: Implement the Simple triggers Data Model
This gets your automation engine's foundation in place, mirroring the WordPress setup.
1. Create the Migration:
Run this in your terminal:
code
Bash
php artisan make:migration create_triggers_table
Open the new file in database/migrations/ and replace its content with this:
code
PHP
// database/migrations/YYYY_MM_DD_HHMMSS_create_triggers_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('triggers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('event_key')->index();
            $table->string('action_type');
            $table->string('action_value');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('triggers');
    }
};
2. Create the Model:
code
Bash
php artisan make:model Trigger
Open app/Models/Trigger.php and set the $fillable property:
code
PHP
// app/Models/Trigger.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trigger extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'event_key',
        'action_type',
        'action_value',
    ];
}
3. Run the Migration:
code
Bash
php artisan migrate
Result: The triggers table now exists. Your ReferralService has a table to query.
Step 2: Implement the Missing API Controllers & Routes
This exposes your existing backend services to the frontend.
1. Create the Controllers:
Run these commands:
code
Bash
php artisan make:controller Api/ProfileController
php artisan make:controller Api/OrdersController
php artisan make:controller Api/CatalogController
2. Populate the Controllers:
app/Http/Controllers/Api/ProfileController.php:
code
PHP
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateProfileRequest;
use App\Services\UserService;
use App\Domain\ValueObjects\UserId;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(private UserService $userService) {}

    public function getProfile(Request $request): JsonResponse
    {
        $profileDto = $this->userService->get_full_profile_data(UserId::fromInt($request->user()->id));
        return response()->json(['success' => true, 'data' => (array) $profileDto]);
    }
    
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $command = $request->toCommand();
        $this->userService->handle($command);
        
        $updatedProfile = $this->userService->get_full_profile_data(UserId::fromInt($request->user()->id));
        return response()->json(['success' => true, 'data' => (array) $updatedProfile]);
    }
}
app/Http/Controllers/Api/OrdersController.php:
code
PHP
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\OrderRepository;
use Illuminate\Http\Request;

class OrdersController extends Controller
{
    public function __construct(private OrderRepository $orderRepository) {}

    public function getOrders(Request $request)
    {
        $orders = $this->orderRepository->getUserOrders($request->user()->id);
        return response()->json(['success' => true, 'data' => ['orders' => $orders]]);
    }
}
app/Http/Controllers/Api/CatalogController.php:
code
PHP
<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CatalogService;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function __construct(private CatalogService $catalogService) {}

    public function getProducts()
    {
        $products = $this->catalogService->get_all_reward_products();
        return response()->json(['success' => true, 'data' => ['products' => $products]]);
    }

    public function getProduct(Request $request, int $id)
    {
        $userId = $request->user() ? $request->user()->id : 0;
        $product = $this->catalogService->get_product_with_eligibility($id, $userId);
        return response()->json(['success' => true, 'data' => $product]);
    }
}
3. Create the UpdateProfileRequest:
code
Bash
php artisan make:request Api/UpdateProfileRequest
Replace the content of app/Http/Requests/Api/UpdateProfileRequest.php:
code
PHP
<?php

namespace App\Http\Requests\Api;

use App\Commands\UpdateProfileCommand;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'firstName' => 'sometimes|string|max:255',
            'lastName' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
            'custom_fields' => 'sometimes|array',
        ];
    }

    public function toCommand(): UpdateProfileCommand
    {
        return new UpdateProfileCommand(
            $this->user()->id,
            $this->validated()
        );
    }
}
4. Update routes/api.php:
Open routes/api.php and add the new routes and imports.
code
PHP
<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SessionController;
use App\Http\Controllers\Api\RedeemController;
use App\Http\Controllers\Api\ClaimController;
// ADD THESE IMPORTS
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\OrdersController;
use App\Http\Controllers\Api\CatalogController;

// --- PUBLIC ROUTES ---
Route::prefix('rewards/v2')->group(function () {
    Route::post('/unauthenticated/claim', [ClaimController::class, 'processUnauthenticatedClaim']);
    // ADD PUBLIC CATALOG ROUTES
    Route::get('/catalog/products', [CatalogController::class, 'getProducts']);
    Route::get('/catalog/products/{id}', [CatalogController::class, 'getProduct']);
});

// ... login/register routes are fine ...

// --- PROTECTED ROUTES ---
Route::middleware('auth:sanctum')->prefix('rewards/v2')->group(function () {
    // Session & Profile
    Route::get('/users/me/session', [SessionController::class, 'getSessionData']);
    // ADD PROFILE ROUTES
    Route::get('/users/me/profile', [ProfileController::class, 'getProfile']);
    Route::post('/users/me/profile', [ProfileController::class, 'updateProfile']);
    
    // Actions
    Route::post('/actions/claim', [ClaimController::class, 'processClaim']);
    Route::post('/actions/redeem', [RedeemController::class, 'processRedemption']);

    // Data
    // UNCOMMENT AND ADD ORDERS ROUTE
    Route::get('/users/me/orders', [OrdersController::class, 'getOrders']);
});
Step 3: Update Services to use the new triggers Table
Now we modify the ReferralService to use the new Eloquent model instead of the old WordPress API calls.
Update app/Services/ReferralService.php:
Make these changes to the execute_triggers method.
code
PHP
<?php
namespace App\Services;

// ... other use statements
use App\Models\Trigger; // <-- ADD THIS IMPORT

class ReferralService {
    // ... constructor and other methods are fine ...

    private function execute_triggers(string $event_key, int $user_id, array $context = []) {
        // <<<--- REFACTOR: Use the Eloquent model
        $triggers_to_run = Trigger::where('event_key', $event_key)->get();

        if ($triggers_to_run->isEmpty()) {
            return;
        }

        foreach ($triggers_to_run as $trigger) {
            $action_type = $trigger->action_type;
            $action_value = $trigger->action_value;
            
            if ($action_type === 'grant_points') {
                $points_to_grant = (int) $action_value;
                if ($points_to_grant > 0) {
                    // This part stays the same
                    $this->eventBus->dispatch('points_to_be_granted', [
                        'user_id'     => $user_id,
                        'points'      => $points_to_grant,
                        'description' => $trigger->name
                    ]);
                }
            }
        }

        $this->cdp_service->track($user_id, $event_key, $context);
    }
    
    // ... rest of the class
}
Step 4: Build the Filament Admin Resources
This gives you the UI to manage the system.
1. Create AchievementResource:
code
Bash
php artisan make:filament-resource Achievement --model=Models/Achievement --generate
2. Create TriggerResource:
code
Bash
php artisan make:filament-resource Trigger --model=Models/Trigger --generate
3. Populate the Resource files:
app/Filament/Resources/AchievementResource.php: Fill in the form() and table() methods.
code
PHP
// In form()
return $form->schema([
    Forms\Components\TextInput::make('achievement_key')->required()->label('Key (e.g., first_scan)'),
    Forms\Components\TextInput::make('title')->required(),
    Forms\Components\Textarea::make('description')->columnSpanFull(),
    Forms\Components\TextInput::make('points_reward')->numeric()->default(0),
    Forms\Components\Select::make('rarity')->options(['common' => 'Common', 'uncommon' => 'Uncommon', 'rare' => 'Rare'])->required(),
    Forms\Components\Select::make('trigger_event')->options(['first_product_scanned' => 'First Scanned', 'standard_product_scanned' => 'Standard Scanned'])->required(),
    Forms\Components\TextInput::make('trigger_count')->numeric()->default(1)->required(),
    Forms\Components\Toggle::make('is_active')->default(true),
    Forms\Components\Textarea::make('conditions')->label('Conditions (JSON)')->columnSpanFull()->nullable(),
]);

// In table()
return $table->columns([
    Tables\Columns\TextColumn::make('title')->sortable()->searchable(),
    Tables\Columns\TextColumn::make('achievement_key'),
    Tables\Columns\TextColumn::make('trigger_event'),
    Tables\Columns\TextColumn::make('points_reward')->sortable(),
    Tables\Columns\IconColumn::make('is_active')->boolean(),
]);
app/Filament/Resources/TriggerResource.php: Fill in the form() and table() methods.
code
PHP
// In form()
return $form->schema([
    Forms\Components\TextInput::make('name')->required()->label('Trigger Name'),
    Forms\Components\Select::make('event_key')->options(['referral_converted' => 'Referral Converted'])->required(),
    Forms\Components\Select::make('action_type')->options(['grant_points' => 'Grant Points'])->required(),
    Forms\Components\TextInput::make('action_value')->required()->label('Value (e.g., 500)'),
]);

// In table()
return $table->columns([
    Tables\Columns\TextColumn::make('name')->searchable(),
    Tables\Columns\TextColumn::make('event_key'),
    Tables\Columns\TextColumn::make('action_type'),
    Tables\Columns\TextColumn::make('action_value'),
]);
You should now be able to log in to /admin and see "Achievements" and "Triggers" in your Filament sidebar.
You are now at feature parity. The data models are in place, the core business logic is wired up, the API endpoints are exposed, and the admin panel is functional. The next and final step is to port the Playwright test suite to verify everything works end-to-end.