<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salary_slips', function (Blueprint $table) {
            $table->string('document_hash', 64)->nullable()->unique()->after('file_path');
        });

        // Backfill existing slips.
        $slips = DB::table('salary_slips')->select('id', 'issued_at', 'document_hash')->get();
        foreach ($slips as $s) {
            if (!empty($s->document_hash)) {
                continue;
            }
            $seed = $s->id.'|'.($s->issued_at ?? now()->toDateTimeString()).'|'.Str::random(16);
            $hash = strtoupper(hash('sha256', $seed));
            DB::table('salary_slips')->where('id', $s->id)->update(['document_hash' => $hash]);
        }
    }

    public function down(): void
    {
        Schema::table('salary_slips', function (Blueprint $table) {
            $table->dropUnique(['document_hash']);
            $table->dropColumn('document_hash');
        });
    }
};

