<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $rows = [
            ['observed_on' => '2026-01-01', 'title' => "New Year's Day"],
            ['observed_on' => '2026-01-14', 'title' => 'Makar Sankranti / Pongal'],
            ['observed_on' => '2026-01-26', 'title' => 'Republic Day'],
            ['observed_on' => '2026-02-15', 'title' => 'Maha Shivratri (optional)'],
            ['observed_on' => '2026-03-04', 'title' => 'Holi'],
            ['observed_on' => '2026-08-15', 'title' => 'Independence Day'],
            ['observed_on' => '2026-08-28', 'title' => 'Raksha Bandhan'],
            ['observed_on' => '2026-09-04', 'title' => 'Janmashtami'],
            ['observed_on' => '2026-09-14', 'title' => 'Ganesh Chaturthi'],
            ['observed_on' => '2026-10-02', 'title' => 'Gandhi Jayanti'],
            ['observed_on' => '2026-10-20', 'title' => 'Dussehra'],
            ['observed_on' => '2026-11-08', 'title' => 'Diwali'],
            ['observed_on' => '2026-11-11', 'title' => 'Bhai Dooj (optional)'],
            ['observed_on' => '2026-11-24', 'title' => 'Guru Nanak Jayanti (optional)'],
            ['observed_on' => '2026-12-25', 'title' => 'Christmas Day'],
        ];

        foreach ($rows as $row) {
            DB::table('holidays')->updateOrInsert(
                ['observed_on' => $row['observed_on']],
                ['title' => $row['title'], 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }

    public function down(): void
    {
        DB::table('holidays')->whereBetween('observed_on', ['2026-01-01', '2026-12-31'])->delete();
    }
};

