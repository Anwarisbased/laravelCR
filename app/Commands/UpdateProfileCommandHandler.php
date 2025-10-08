<?php
namespace App\Commands;

use App\Commands\UpdateProfileCommand;
use App\Repositories\UserRepository;
use App\Services\ActionLogService;
use App\Services\CDPService;
use Exception;
use Illuminate\Support\Facades\Validator;

/**
 * Handler for updating a user's profile.
 */
final class UpdateProfileCommandHandler {
    private $action_log_service;
    private $cdp_service;
    private $user_repository;

    public function __construct(
        ActionLogService $action_log_service,
        CDPService $cdp_service,
        UserRepository $user_repository
    ) {
        $this->action_log_service = $action_log_service;
        $this->cdp_service = $cdp_service;
        $this->user_repository = $user_repository;
    }

    /**
     * @throws Exception
     */
    public function handle(UpdateProfileCommand $command): void {
        $user_id = \App\Domain\ValueObjects\UserId::fromInt($command->user_id);
        $data = $command->data;
        $changed_fields = [];

        // Handle first name and last name - update using repository method
        if (isset($data['firstName'])) {
            $this->user_repository->updateUserMetaField($user_id, 'first_name', trim(strip_tags($data['firstName'])));
            $changed_fields[] = 'firstName';
        }
        if (isset($data['lastName'])) {
            $this->user_repository->updateUserMetaField($user_id, 'last_name', trim(strip_tags($data['lastName'])));
            $changed_fields[] = 'lastName';
        }
        
        // Update shipping address when firstName or lastName changes
        $shipping_data = [];
        if (isset($data['firstName'])) {
            $shipping_data['firstName'] = trim(strip_tags($data['firstName']));
        }
        if (isset($data['lastName'])) {
            $shipping_data['lastName'] = trim(strip_tags($data['lastName']));
        }
        
        // Also update with any provided shipping address data
        if (isset($data['shippingAddress']) && is_array($data['shippingAddress'])) {
            foreach ($data['shippingAddress'] as $key => $value) {
                if (is_string($value)) {
                    $shipping_data[$key] = trim(strip_tags($value));
                } else {
                    $shipping_data[$key] = $value;
                }
            }
        }
        
        if (count($shipping_data) > 0) {
            $this->user_repository->saveShippingAddress($user_id, $shipping_data);
        }

        if (isset($data['phone'])) {
            // REFACTOR: Use the UserRepository instead of direct WordPress function
            $this->user_repository->updateUserMetaField($user_id, 'phone_number', trim(strip_tags($data['phone'])));
            $changed_fields[] = 'phone';
        }

        if (isset($data['custom_fields']) && is_array($data['custom_fields'])) {
            // In a full implementation, we'd fetch definitions from a CustomFieldRepository
            // to validate the keys and values before saving.
            foreach ($data['custom_fields'] as $key => $value) {
                // REFACTOR: Use the UserRepository instead of direct WordPress function
                $clean_key = preg_replace('/[^a-zA-Z0-9_\-]/', '', $key);
                $this->user_repository->updateUserMetaField($user_id, $clean_key, trim(strip_tags($value)));
                $changed_fields[] = 'custom_' . $clean_key;
            }
        }

        // Get user to update the main name field to match first and last name
        $user = $this->user_repository->getUserCoreData($user_id);
        if ($user) {
            $user_meta = $user->meta ?: [];
            $first_name = $user_meta['first_name'] ?? $user->name;
            $last_name = $user_meta['last_name'] ?? '';
            $user_name = trim($first_name . ' ' . $last_name);
            
            // Update using repository instead of direct model update
            $this->user_repository->updateUserData($user_id, ['name' => $user_name]);
        }

        if (!empty($changed_fields)) {
            $log_meta_data = ['changed_fields' => $changed_fields];
            $this->action_log_service->record($user_id, 'profile_updated', 0, $log_meta_data);
            $this->cdp_service->track($user_id, 'user_profile_updated', $log_meta_data);
        }
    }
}