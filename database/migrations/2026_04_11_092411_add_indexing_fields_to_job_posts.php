<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('job_posts', function (Blueprint $table) {
            $table->boolean('submitted_to_indexing')->default(false)->after('is_indexed');
            $table->timestamp('indexing_submitted_at')->nullable()->after('submitted_to_indexing');
        });
    }

    public function down()
    {
        Schema::table('job_posts', function (Blueprint $table) {
            $table->dropColumn(['submitted_to_indexing', 'indexing_submitted_at']);
        });
    }
};