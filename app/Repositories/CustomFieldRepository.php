<?php
namespace App\Repositories;

/**
 * Handles all data access for Custom Field definitions.
 */
class CustomFieldRepository {

    /**
     * @return array The definitions for all published custom fields.
     */
    public function getFieldDefinitions(): array {
        // For now, return an empty array since we're focusing on pure Laravel implementation
        // In a complete implementation, this would fetch from a custom_fields table
        return [];
    }
}