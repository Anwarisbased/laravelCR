<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray($request): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'sku' => $this->sku,
            'description' => $this->description,
            'short_description' => $this->short_description,
            'points_award' => $this->points_award,
            'points_cost' => $this->points_cost,
            'required_rank_key' => $this->required_rank_key,
            'is_active' => $this->is_active,
            'is_featured' => $this->is_featured,
            'is_new' => $this->is_new,
            'brand' => $this->brand,
            'strain_type' => $this->strain_type,
            'thc_content' => $this->thc_content,
            'cbd_content' => $this->cbd_content,
            'product_form' => $this->product_form,
            'marketing_snippet' => $this->marketing_snippet,
            'images' => $this->image_urls,
            'tags' => $this->tags,
            'available_from' => $this->available_from,
            'available_until' => $this->available_until,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];

        // Add category if available
        if ($this->relationLoaded('category')) {
            $data['category'] = $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ] : null;
        }

        // Add eligibility info if available
        if (isset($this->eligibility)) {
            $data['eligibility'] = $this->eligibility;
        }

        return $data;
    }
}