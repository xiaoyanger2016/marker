<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Phase 18.3: 全文搜索 (Postgres tsvector)
     *  - contents 加 search_vector 列 (to_tsvector('simple', title || ' ' || subtitle || ' ' || summary || ' ' || description))
     *  - GIN 索引加速
     *  - 同时给 places / users / activities 加搜索
     */
    public function up(): void
    {
        // contents.search_vector (tsvector 类型，GIN 索引)
        DB::statement('ALTER TABLE contents ADD COLUMN search_vector tsvector');
        DB::statement("UPDATE contents SET search_vector = setweight(to_tsvector('simple', coalesce(title, '')), 'A') || setweight(to_tsvector('simple', coalesce(subtitle, '')), 'B') || setweight(to_tsvector('simple', coalesce(summary, '')), 'C') || setweight(to_tsvector('simple', coalesce(description, '')), 'D')");
        DB::statement("CREATE INDEX contents_search_vector_idx ON contents USING GIN(search_vector) WHERE deleted_at IS NULL");

        // places.search_vector
        DB::statement('ALTER TABLE places ADD COLUMN search_vector tsvector');
        DB::statement("UPDATE places SET search_vector = setweight(to_tsvector('simple', coalesce(name, '')), 'A') || setweight(to_tsvector('simple', coalesce(address, '')), 'B') || setweight(to_tsvector('simple', coalesce(description, '')), 'C')");
        DB::statement("CREATE INDEX places_search_vector_idx ON places USING GIN(search_vector) WHERE deleted_at IS NULL");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS contents_search_vector_idx');
        DB::statement('DROP INDEX IF EXISTS places_search_vector_idx');
        DB::statement('ALTER TABLE contents DROP COLUMN IF EXISTS search_vector');
        DB::statement('ALTER TABLE places DROP COLUMN IF EXISTS search_vector');
    }
};
