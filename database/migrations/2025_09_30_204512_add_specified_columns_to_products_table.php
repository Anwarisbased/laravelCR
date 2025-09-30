<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn("products", "short_description")) {
            Schema::table("products", function (Blueprint $table) {
                $table->string("short_description")->nullable();
            });
        }

        if (!Schema::hasColumn("products", "required_rank_key")) {
            Schema::table("products", function (Blueprint $table) {
                $table->string("required_rank_key")->nullable();
            });
        }

        if (!Schema::hasColumn("products", "is_featured")) {
            Schema::table("products", function (Blueprint $table) {
                $table->boolean("is_featured")->default(false);
            });
        }

        if (!Schema::hasColumn("products", "is_new")) {
            Schema::table("products", function (Blueprint $table) {
                $table->boolean("is_new")->default(false);
            });
        }

        if (!Schema::hasColumn("products", "category_id")) {
            Schema::table("products", function (Blueprint $table) {
                $table->unsignedBigInteger("category_id")->nullable();
            });
        }

        if (!Schema::hasColumn("products", "brand")) {
            Schema::table("products", function (Blueprint $table) {
                $table->string("brand")->nullable();
            });
        }

        if (!Schema::hasColumn("products", "strain_type")) {
            Schema::table("products", function (Blueprint $table) {
                $table->string("strain_type")->nullable();
            });
        }

        if (!Schema::hasColumn("products", "thc_content")) {
            Schema::table("products", function (Blueprint $table) {
                $table->decimal("thc_content", 5, 2)->nullable();
            });
        }

        if (!Schema::hasColumn("products", "cbd_content")) {
            Schema::table("products", function (Blueprint $table) {
                $table->decimal("cbd_content", 5, 2)->nullable();
            });
        }

        if (!Schema::hasColumn("products", "product_form")) {
            Schema::table("products", function (Blueprint $table) {
                $table->string("product_form")->nullable();
            });
        }

        if (!Schema::hasColumn("products", "marketing_snippet")) {
            Schema::table("products", function (Blueprint $table) {
                $table->text("marketing_snippet")->nullable();
            });
        }

        if (!Schema::hasColumn("products", "image_urls")) {
            Schema::table("products", function (Blueprint $table) {
                $table->json("image_urls")->nullable();
            });
        }

        if (!Schema::hasColumn("products", "tags")) {
            Schema::table("products", function (Blueprint $table) {
                $table->json("tags")->nullable();
            });
        }

        if (!Schema::hasColumn("products", "sort_order")) {
            Schema::table("products", function (Blueprint $table) {
                $table->integer("sort_order")->default(0);
            });
        }

        if (!Schema::hasColumn("products", "available_from")) {
            Schema::table("products", function (Blueprint $table) {
                $table->timestamp("available_from")->nullable();
            });
        }

        if (!Schema::hasColumn("products", "available_until")) {
            Schema::table("products", function (Blueprint $table) {
                $table->timestamp("available_until")->nullable();
            });
        }
        
        // Update existing columns if needed
        if (Schema::hasColumn("products", "required_rank")) {
            Schema::table("products", function (Blueprint $table) {
                $table->string("required_rank")->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table("products", function (Blueprint $table) {
            $table->dropColumn([
                "short_description",
                "required_rank_key",
                "is_featured",
                "is_new",
                "category_id",
                "brand",
                "strain_type",
                "thc_content",
                "cbd_content",
                "product_form",
                "marketing_snippet",
                "image_urls",
                "tags",
                "sort_order",
                "available_from",
                "available_until",
            ]);
        });
    }
};
