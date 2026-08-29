<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add per-locale SEO-only fields (custom robots directive and Open Graph
     * image) to the existing catalog translation tables. Title, description
     * and keywords already live on these tables as meta_* columns.
     *
     * @see app/Models/Concerns/HasTranslations.php
     */
    public function up(): void
    {
        foreach ($this->tables() as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->string('robots')->nullable()->after('meta_keywords');
                $table->string('og_image')->nullable()->after('robots');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->tables() as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn(['robots', 'og_image']);
            });
        }
    }

    /**
     * @return array<int, string>
     */
    private function tables(): array
    {
        return ['product_translations', 'category_translations', 'brand_translations'];
    }
};
