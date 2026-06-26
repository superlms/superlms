<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Log;

class CleanMaliciousContent extends Command
{
    protected $signature = 'security:clean-malicious';
    protected $description = 'Remove malicious scripts from database';

    private $maliciousPatterns = [
        'carto.run.place',
        '<script src="https://carto.run.place/index.js?a"></script>',
        'javascript:',
        'onload=',
        'base64_decode',
        'eval(',
    ];

    public function handle()
    {
        $this->info('🔍 Scanning database for malicious content...');

        // Get all tables
        $tables = DB::select('SHOW TABLES');
        $cleanedCount = 0;

        foreach ($tables as $table) {
            $tableName = array_values((array)$table)[0];

            // Skip migrations table
            if (str_contains($tableName, 'migrations')) {
                continue;
            }

            // Get all text columns
            $columns = DB::select("SHOW COLUMNS FROM `$tableName`");

            foreach ($columns as $column) {
                if ($this->isTextColumn($column->Type)) {
                    foreach ($this->maliciousPatterns as $pattern) {
                        $sql = "UPDATE `$tableName` 
                                SET `$column->Field` = REPLACE(`$column->Field`, ?, '')
                                WHERE `$column->Field` LIKE ?";

                        $affected = DB::affectingStatement($sql, [
                            $pattern,
                            "%$pattern%"
                        ]);

                        if ($affected > 0) {
                            $cleanedCount += $affected;
                            $this->warn("Cleaned $affected records in $tableName.{$column->Field} for pattern: $pattern");
                            Log::warning("Malicious content cleaned", [
                                'table' => $tableName,
                                'column' => $column->Field,
                                'pattern' => $pattern,
                                'count' => $affected
                            ]);
                        }
                    }
                }
            }
        }

        // Create security logs table if not exists
        $this->createSecurityLogsTable();

        $this->info("✅ Cleanup complete! $cleanedCount records cleaned.");
    }

    private function isTextColumn($type)
    {
        $textTypes = ['varchar', 'text', 'char', 'longtext', 'mediumtext', 'json'];
        foreach ($textTypes as $textType) {
            if (str_contains(strtolower($type), $textType)) {
                return true;
            }
        }
        return false;
    }

    private function createSecurityLogsTable()
    {
        if (!Schema::hasTable('security_logs')) {
            Schema::create('security_logs', function (Blueprint $table) {
                $table->id();
                $table->string('event_type'); 
                $table->text('details')->nullable();
                $table->string('ip_address')->nullable();
                $table->string('user_agent')->nullable();
                $table->string('endpoint')->nullable();
                $table->timestamps();

                $table->index('event_type');
                $table->index('ip_address');
            });

            $this->info('✅ Created security_logs table');
        }
    }
}
