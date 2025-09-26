<?php
namespace App\Commands;

use App\Commands\UpdateProfileCommand;
use App\Repositories\UserRepository;
use App\Services\ActionLogService;
use App\Services\CDPService;
use Exception;

// Exit if accessed directly.
}

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
        UserRepository $user_repository,
        WordPressApiWrapperInterface $wp
    ) {
        $this->action_log_service = $action_log_service;
        $this->cdp_service = $cdp_service;
        $this->user_repository = $user_repository;
        $this->wp = $wp;
    }

    /**
     * @throws Exception
     */
    public function handle(UpdateProfileCommand $command): void {
        $user_id = \App\Domain\ValueObjects\UserId::fromInt($command->user_id);
        $data = $command->data;
        $changed_fields = [];

        $core_user_data = [];
        if (isset($data['firstName'])) {
            $core_user_data['first_name'] = $this->wp->sanitizeTextField($data['firstName']);
            $changed_fields[] = 'firstName';
        }
        if (isset($data['lastName'])) {
            $core_user_data['last_name'] = $this->wp->sanitizeTextField($data['lastName']);
            $changed_fields[] = 'lastName';
        }
        if (count($core_user_data) > 0) {
            // REFACTOR: Use the UserRepository instead of direct WordPress function
            $result = $this->user_repository->updateUserData($user_id, $core_user_data);
            if ($this->user_repository->wp->isWpError($result)) {
                throw new Exception('Could not update user core data.');
            }
        }

        // Update shipping address when firstName or lastName changes
        $shipping_data = [];
        if (isset($data['firstName'])) {
            $shipping_data['firstName'] = $this->wp->sanitizeTextField($data['firstName']);
        }
        if (isset($data['lastName'])) {
            $shipping_data['lastName'] = $this->wp->sanitizeTextField($data['lastName']);
        }
        if (count($shipping_data) > 0) {
            $this->user_repository->saveShippingAddress($user_id, $shipping_data);
        }

        if (isset($data['phone'])) {
            // REFACTOR: Use the UserRepository instead of direct WordPress function
            $this->user_repository->updateUserMetaField($user_id, 'phone_number', $this->wp->sanitizeTextField($data['phone']));
            $changed_fields[] = 'phone_number';
        }

        if (isset($data['custom_fields']) && is_array($data['custom_fields'])) {
            // In a full implementation, we'd fetch definitions from a CustomFieldRepository
            // to validate the keys and values before saving.
            foreach ($data['custom_fields'] as $key => $value) {
                // REFACTOR: Use the UserRepository instead of direct WordPress function
                $this->user_repository->updateUserMetaField($user_id, $this->wp->sanitizeKey($key), $this->wp->sanitizeTextField($value));
                $changed_fields[] = 'custom_' . sanitize_key($key);
            }
        }

        if (!empty($changed_fields)) {
            $log_meta_data = ['changed_fields' => $changed_fields];
            $this->action_log_service->record($command->user_id, 'profile_updated', 0, $log_meta_data);
            $this->cdp_service->track($command->user_id, 'user_profile_updated', $log_meta_data);
        }
    }
}