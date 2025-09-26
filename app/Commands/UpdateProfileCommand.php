<?php
namespace App\Commands;

// Exit if accessed directly.
}

/**
 * Command DTO for updating a user's profile.
 */
final class UpdateProfileCommand {
    public $user_id;
    public $data;

    public function __construct(int $user_id, array $data) {
        $this->user_id = $user_id;
        $this->data = $data;
    }
}