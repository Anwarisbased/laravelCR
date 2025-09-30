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
        Schema::table("pages", function (Blueprint $table) {
            $table->string("title")->after("id");
            $table->string("slug")->unique()->after("title");
            $table->text("content")->nullable()->after("slug");
            $table->text("excerpt")->nullable()->after("content");
            $table->string("status")->default("draft")->after("excerpt");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table("pages", function (Blueprint $table) {
            $table->dropColumn(["title", "slug", "content", "excerpt", "status"]);
        });
    }
};
