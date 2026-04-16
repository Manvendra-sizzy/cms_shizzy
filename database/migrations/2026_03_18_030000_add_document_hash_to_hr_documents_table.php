<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hr_documents', function (Blueprint $table) {
            $table->string('document_hash', 64)->nullable()->unique()->after('file_path');
        });

        // Backfill existing rows.
        $docs = DB::table('hr_documents')->select('id', 'issued_at', 'document_hash')->get();
        foreach ($docs as $d) {
            if (!empty($d->document_hash)) {
                continue;
            }
            $seed = $d->id.'|'.($d->issued_at ?? now()->toDateTimeString()).'|'.Str::random(16);
            $hash = strtoupper(hash('sha256', $seed));
            DB::table('hr_documents')->where('id', $d->id)->update(['document_hash' => $hash]);
        }
    }

    public function down(): void
    {
        Schema::table('hr_documents', function (Blueprint $table) {
            $table->dropUnique(['document_hash']);
            $table->dropColumn('document_hash');
        });
    }
};

