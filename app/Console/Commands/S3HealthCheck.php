<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Throwable;

class S3HealthCheck extends Command
{
    protected $signature = 's3:health
                            {--keep : Skip the delete step so you can manually inspect the test object in the bucket}';

    protected $description = 'Verify the configured S3 disk is reachable and read/write/delete works end-to-end.';

    public function handle(): int
    {
        // 1. Config sanity
        $bucket = (string) config('filesystems.disks.s3.bucket');
        $region = (string) config('filesystems.disks.s3.region');
        $key    = (string) config('filesystems.disks.s3.key');
        $url    = (string) config('filesystems.disks.s3.url');

        if ($bucket === '' || $region === '' || $key === '') {
            $this->error('S3 disk is not fully configured. Required env vars: AWS_BUCKET, AWS_DEFAULT_REGION, AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY.');

            return self::FAILURE;
        }

        $this->info("✓ Configuration looks valid (bucket: {$bucket}, region: {$region})");

        // Step path uses a timestamp so concurrent runs don't collide.
        $path     = 's3-health-check/' . now()->format('YmdHis') . '-' . substr(bin2hex(random_bytes(3)), 0, 6) . '.txt';
        $contents = 'superlms s3:health probe @ ' . now()->toIso8601String();

        try {
            // 2. PUT
            $okPut = Storage::disk('s3')->put($path, $contents);
            if (! $okPut) {
                $this->error("✗ PUT failed — bucket name, region, or IAM PutObject permission is wrong.");

                return self::FAILURE;
            }
            $this->info("✓ PUT  succeeded → {$path}");

            // 3. EXISTS
            if (! Storage::disk('s3')->exists($path)) {
                $this->error('✗ EXISTS check failed (PUT seemed to succeed but the object is not visible).');

                return self::FAILURE;
            }
            $this->info('✓ EXISTS check passed');

            // 4. GET
            $roundtrip = Storage::disk('s3')->get($path);
            if ($roundtrip !== $contents) {
                $this->error('✗ GET returned different content than was PUT.');

                return self::FAILURE;
            }
            $this->info('✓ GET  succeeded → content matched');

            // 5. URL
            $publicUrl = Storage::disk('s3')->url($path);
            $this->info("✓ URL  generated  → {$publicUrl}");

            if ($url === '') {
                $this->warn('  (tip: set AWS_URL in .env to "https://' . $bucket . '.s3.' . $region . '.amazonaws.com" for consistent URLs)');
            }

            // 6. HEAD via HTTP — only meaningful if the bucket policy makes objects public,
            //    which this app expects (FileUploadHelper sets public visibility).
            try {
                $head = Http::timeout(10)->head($publicUrl);
                if ($head->successful()) {
                    $this->info("✓ HEAD via HTTP   → {$head->status()} OK");
                } else {
                    $this->warn("△ HEAD via HTTP returned {$head->status()} — the bucket policy may not allow public reads. Re-check step 2 in S3_SETUP.md.");
                }
            } catch (Throwable $e) {
                $this->warn('△ HEAD via HTTP could not be performed (' . $e->getMessage() . '). Non-fatal — direct S3 SDK calls still work.');
            }

            // 7. DELETE
            if ($this->option('keep')) {
                $this->warn("△ DELETE skipped (--keep). You'll need to delete {$path} from the S3 console manually.");
            } else {
                if (! Storage::disk('s3')->delete($path)) {
                    $this->error('✗ DELETE failed — IAM may be missing s3:DeleteObject permission.');

                    return self::FAILURE;
                }
                $this->info('✓ DELETE succeeded');
            }

            $this->newLine();
            $this->info('S3 disk is healthy and ready.');

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('✗ ' . get_class($e) . ': ' . $e->getMessage());
            $this->line('');
            $this->line('Common causes:');
            $this->line('  • Wrong AWS_ACCESS_KEY_ID / AWS_SECRET_ACCESS_KEY → SignatureDoesNotMatch / InvalidAccessKeyId');
            $this->line('  • Wrong AWS_BUCKET → NoSuchBucket');
            $this->line('  • Wrong AWS_DEFAULT_REGION → PermanentRedirect or timeout');
            $this->line('  • IAM user missing s3:PutObject / s3:GetObject / s3:DeleteObject → AccessDenied');
            $this->line('  • Bucket Object Ownership = "ACLs disabled" but code calls setVisibility("public") → AccessControlListNotSupported');

            return self::FAILURE;
        }
    }
}
