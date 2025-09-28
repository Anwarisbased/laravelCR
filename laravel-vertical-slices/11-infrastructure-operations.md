# Laravel Vertical Slice 11: Infrastructure & Operations

## Overview
This vertical slice implements the infrastructure and operations components including deployment, monitoring, logging, caching, and system maintenance using Laravel's native features, replacing WordPress hosting and operational dependencies.

## Key Components

### Laravel Components
- Laravel Forge for server management
- Laravel Envoyer for zero-downtime deployment
- Laravel Horizon for queue monitoring
- Laravel Telescope for debugging
- Laravel Scout for search indexing
- Laravel Cashier for payment processing (if needed)
- Laravel Broadcasting for real-time features
- Laravel Sail for development environment

### Infrastructure Services
- Server Management (Forge)
- Deployment Automation (Envoyer)
- Database Management
- Cache Management (Redis)
- Queue Management (Horizon)
- Monitoring & Alerting
- Logging & Analytics
- Backup & Recovery
- Security Management

### Operational Features
- Health Checks
- Performance Monitoring
- Error Tracking
- Resource Utilization
- System Scaling
- Maintenance Windows
- Data Migration
- Backup Procedures
- Disaster Recovery

### Laravel Services
- HealthCheckService (system health monitoring)
- PerformanceMonitorService (performance tracking)
- ErrorTrackingService (error monitoring)
- ResourceMonitorService (resource utilization)
- BackupService (backup management)
- MaintenanceService (maintenance operations)

### Laravel Models
- SystemLog (operational logging)
- HealthCheck (health check results)
- PerformanceMetric (performance data)
- ErrorReport (error tracking)
- ResourceUsage (resource utilization)

### Laravel Jobs
- PerformHealthCheck
- CollectPerformanceMetrics
- RotateLogs
- CreateBackup
- CleanUpOldData
- SendSystemAlert

### Laravel Notifications
- SystemAlertNotification
- PerformanceDegradationNotification
- HealthCheckFailedNotification
- BackupCompletedNotification

### Laravel Events
- SystemHealthCheckPerformed
- PerformanceThresholdExceeded
- CriticalErrorOccurred
- BackupCompleted
- MaintenanceWindowStarted

## Implementation Details

### Health Check System
```php
// app/Services/HealthCheckService.php
namespace App\Services;

use App\Models\HealthCheck;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

class HealthCheckService
{
    public function performFullHealthCheck(): array
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'queue' => $this->checkQueue(),
            'storage' => $this->checkStorage(),
            'external_services' => $this->checkExternalServices(),
            'application' => $this->checkApplication(),
        ];
        
        $overallStatus = $this->calculateOverallStatus($checks);
        
        $healthCheck = HealthCheck::create([
            'status' => $overallStatus,
            'details' => $checks,
            'performed_at' => now(),
        ]);
        
        return [
            'status' => $overallStatus,
            'checks' => $checks,
            'checked_at' => $healthCheck->performed_at->toISOString(),
        ];
    }
    
    protected function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            $connectionTime = DB::selectOne('SELECT NOW() as time');
            
            return [
                'status' => 'healthy',
                'message' => 'Database connection successful',
                'latency' => $this->measureLatency(function() {
                    DB::selectOne('SELECT 1');
                }),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Database connection failed: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ];
        }
    }
    
    protected function checkCache(): array
    {
        try {
            $key = 'health_check_cache_test_' . uniqid();
            $value = 'test_value_' . time();
            
            Redis::setex($key, 10, $value);
            $retrievedValue = Redis::get($key);
            Redis::del($key);
            
            if ($retrievedValue === $value) {
                return [
                    'status' => 'healthy',
                    'message' => 'Cache connection successful',
                    'latency' => $this->measureLatency(function() use ($key, $value) {
                        Redis::setex($key, 1, $value);
                        Redis::get($key);
                        Redis::del($key);
                    }),
                ];
            } else {
                return [
                    'status' => 'degraded',
                    'message' => 'Cache connection successful but data integrity check failed',
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Cache connection failed: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ];
        }
    }
    
    protected function checkQueue(): array
    {
        try {
            // Check if queue workers are running by checking recent jobs
            $recentJobs = DB::table('jobs')
                ->where('created_at', '>', now()->subMinutes(5))
                ->count();
                
            $failedJobs = DB::table('failed_jobs')
                ->where('failed_at', '>', now()->subHour())
                ->count();
                
            return [
                'status' => $failedJobs > 10 ? 'degraded' : 'healthy',
                'message' => $failedJobs > 10 ? 
                    "Queue has {$failedJobs} failed jobs in the last hour" : 
                    "Queue processing normally",
                'metrics' => [
                    'pending_jobs' => $recentJobs,
                    'failed_jobs_last_hour' => $failedJobs,
                ],
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Queue check failed: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ];
        }
    }
    
    protected function checkStorage(): array
    {
        try {
            $disk = Storage::disk('local');
            $testFile = 'health_check_test_' . uniqid() . '.txt';
            $testContent = 'Health check test content';
            
            $disk->put($testFile, $testContent);
            $retrievedContent = $disk->get($testFile);
            $disk->delete($testFile);
            
            if ($retrievedContent === $testContent) {
                return [
                    'status' => 'healthy',
                    'message' => 'Storage access successful',
                    'free_space' => $this->getFreeSpace(),
                ];
            } else {
                return [
                    'status' => 'degraded',
                    'message' => 'Storage access successful but data integrity check failed',
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Storage access failed: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ];
        }
    }
    
    protected function checkExternalServices(): array
    {
        $services = config('services.external_health_checks', []);
        $results = [];
        
        foreach ($services as $serviceName => $serviceConfig) {
            $results[$serviceName] = $this->checkExternalService(
                $serviceConfig['url'],
                $serviceConfig['expected_status'] ?? 200,
                $serviceConfig['timeout'] ?? 10
            );
        }
        
        return $results;
    }
    
    protected function checkExternalService(string $url, int $expectedStatus, int $timeout): array
    {
        try {
            $response = Http::timeout($timeout)->get($url);
            
            if ($response->status() === $expectedStatus) {
                return [
                    'status' => 'healthy',
                    'message' => "Service check successful (Status: {$response->status()})",
                    'latency' => $response->transferStats->getTransferTime() * 1000,
                ];
            } else {
                return [
                    'status' => 'degraded',
                    'message' => "Unexpected status code: {$response->status()} (expected: {$expectedStatus})",
                    'status_code' => $response->status(),
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => "Service check failed: " . $e->getMessage(),
                'error' => $e->getMessage(),
            ];
        }
    }
    
    protected function checkApplication(): array
    {
        return [
            'status' => 'healthy',
            'message' => 'Application is running',
            'version' => config('app.version', 'unknown'),
            'environment' => config('app.env'),
            'debug_mode' => config('app.debug'),
        ];
    }
    
    protected function calculateOverallStatus(array $checks): string
    {
        $statuses = array_column($checks, 'status');
        
        if (in_array('unhealthy', $statuses)) {
            return 'unhealthy';
        }
        
        if (in_array('degraded', $statuses)) {
            return 'degraded';
        }
        
        return 'healthy';
    }
    
    protected function measureLatency(callable $operation): float
    {
        $start = microtime(true);
        $operation();
        $end = microtime(true);
        
        return ($end - $start) * 1000; // Convert to milliseconds
    }
    
    protected function getFreeSpace(): string
    {
        $freeSpace = disk_free_space(storage_path());
        $totalSpace = disk_total_space(storage_path());
        
        return sprintf(
            '%.2f GB free of %.2f GB (%.1f%%)',
            $freeSpace / (1024 * 1024 * 1024),
            $totalSpace / (1024 * 1024 * 1024),
            ($freeSpace / $totalSpace) * 100
        );
    }
}
```

### Performance Monitoring
```php
// app/Services/PerformanceMonitorService.php
namespace App\Services;

use App\Models\PerformanceMetric;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class PerformanceMonitorService
{
    public function collectMetrics(): void
    {
        $metrics = [
            'database_query_time' => $this->getDatabaseQueryTime(),
            'cache_hit_rate' => $this->getCacheHitRate(),
            'queue_processing_time' => $this->getQueueProcessingTime(),
            'memory_usage' => $this->getMemoryUsage(),
            'cpu_usage' => $this->getCpuUsage(),
            'response_time' => $this->getResponseTime(),
            'active_users' => $this->getActiveUsers(),
        ];
        
        PerformanceMetric::create([
            'metrics' => $metrics,
            'collected_at' => now(),
        ]);
    }
    
    protected function getDatabaseQueryTime(): float
    {
        // This would integrate with database query logging
        // For now, return placeholder data
        return rand(10, 100);
    }
    
    protected function getCacheHitRate(): float
    {
        // This would integrate with cache statistics
        // For now, return placeholder data
        return rand(80, 99);
    }
    
    protected function getQueueProcessingTime(): float
    {
        // This would integrate with queue monitoring
        // For now, return placeholder data
        return rand(50, 500);
    }
    
    protected function getMemoryUsage(): string
    {
        return format_bytes(memory_get_usage(true));
    }
    
    protected function getCpuUsage(): float
    {
        // This would integrate with system monitoring
        // For now, return placeholder data
        return rand(10, 80);
    }
    
    protected function getResponseTime(): float
    {
        // This would integrate with request timing middleware
        // For now, return placeholder data
        return rand(50, 500);
    }
    
    protected function getActiveUsers(): int
    {
        return Redis::scard('active_users');
    }
    
    public function getPerformanceReport(int $hours = 24): array
    {
        $metrics = PerformanceMetric::where('collected_at', '>', now()->subHours($hours))
            ->orderBy('collected_at')
            ->get();
            
        return [
            'period' => [
                'start' => now()->subHours($hours)->toISOString(),
                'end' => now()->toISOString(),
            ],
            'metrics' => $metrics->toArray(),
            'averages' => $this->calculateAverages($metrics),
            'trends' => $this->calculateTrends($metrics),
        ];
    }
    
    protected function calculateAverages($metrics): array
    {
        if ($metrics->isEmpty()) {
            return [];
        }
        
        $averages = [];
        $metricKeys = array_keys($metrics->first()->metrics);
        
        foreach ($metricKeys as $key) {
            $values = $metrics->pluck("metrics.{$key}")->filter()->values();
            if (!$values->isEmpty()) {
                $averages[$key] = $values->avg();
            }
        }
        
        return $averages;
    }
    
    protected function calculateTrends($metrics): array
    {
        if ($metrics->count() < 2) {
            return [];
        }
        
        $firstHalf = $metrics->slice(0, intval($metrics->count() / 2));
        $secondHalf = $metrics->slice(intval($metrics->count() / 2));
        
        $trends = [];
        $metricKeys = array_keys($metrics->first()->metrics);
        
        foreach ($metricKeys as $key) {
            $firstAvg = $firstHalf->pluck("metrics.{$key}")->filter()->avg();
            $secondAvg = $secondHalf->pluck("metrics.{$key}")->filter()->avg();
            
            if ($firstAvg > 0) {
                $percentageChange = (($secondAvg - $firstAvg) / $firstAvg) * 100;
                $trends[$key] = [
                    'change_percentage' => $percentageChange,
                    'trend' => $percentageChange > 0 ? 'increasing' : ($percentageChange < 0 ? 'decreasing' : 'stable'),
                ];
            }
        }
        
        return $trends;
    }
}
```

## Monitoring & Alerting

### Error Tracking Service
```php
// app/Services/ErrorTrackingService.php
namespace App\Services;

use App\Models\ErrorReport;
use Illuminate\Support\Facades\Log;
use Throwable;

class ErrorTrackingService
{
    public function reportError(Throwable $exception, array $context = []): void
    {
        $errorReport = ErrorReport::create([
            'exception_class' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'context' => $context,
            'reported_at' => now(),
            'severity' => $this->determineSeverity($exception),
        ]);
        
        // Send alert if critical error
        if ($this->shouldAlert($errorReport)) {
            $this->sendAlert($errorReport);
        }
        
        // Log the error
        Log::error('Application Error Reported', [
            'error_report_id' => $errorReport->id,
            'exception_class' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ]);
    }
    
    protected function determineSeverity(Throwable $exception): string
    {
        $criticalExceptions = [
            'Illuminate\Database\QueryException',
            'Symfony\Component\HttpKernel\Exception\HttpException',
        ];
        
        if (in_array(get_class($exception), $criticalExceptions)) {
            return 'critical';
        }
        
        $warningExceptions = [
            'Illuminate\Validation\ValidationException',
            'Illuminate\Auth\AuthenticationException',
        ];
        
        if (in_array(get_class($exception), $warningExceptions)) {
            return 'warning';
        }
        
        return 'error';
    }
    
    protected function shouldAlert(ErrorReport $errorReport): bool
    {
        // Alert on critical errors
        if ($errorReport->severity === 'critical') {
            return true;
        }
        
        // Alert on frequent errors (more than 10 in an hour)
        $similarErrors = ErrorReport::where('exception_class', $errorReport->exception_class)
            ->where('message', $errorReport->message)
            ->where('reported_at', '>', now()->subHour())
            ->count();
            
        if ($similarErrors > 10) {
            return true;
        }
        
        // Alert during business hours for important errors
        $businessHours = now()->hour >= 9 && now()->hour < 17;
        if ($businessHours && $errorReport->severity === 'error') {
            return true;
        }
        
        return false;
    }
    
    protected function sendAlert(ErrorReport $errorReport): void
    {
        // Send to Slack, Email, or other notification channels
        // This would integrate with notification services
        $this->notifyAdministrators($errorReport);
    }
    
    protected function notifyAdministrators(ErrorReport $errorReport): void
    {
        // Implementation would depend on notification preferences
        // For now, log the alert
        Log::alert('Critical Error Alert', [
            'error_report_id' => $errorReport->id,
            'exception_class' => $errorReport->exception_class,
            'message' => $errorReport->message,
            'severity' => $errorReport->severity,
        ]);
    }
    
    public function getErrorReport(int $days = 7): array
    {
        $reports = ErrorReport::where('reported_at', '>', now()->subDays($days))
            ->orderBy('reported_at', 'desc')
            ->get();
            
        return [
            'period' => [
                'start' => now()->subDays($days)->toISOString(),
                'end' => now()->toISOString(),
            ],
            'total_errors' => $reports->count(),
            'error_distribution' => $this->getErrorDistribution($reports),
            'most_common_errors' => $this->getMostCommonErrors($reports),
            'reports' => $reports->toArray(),
        ];
    }
    
    protected function getErrorDistribution($reports): array
    {
        return $reports->groupBy('severity')->map->count()->toArray();
    }
    
    protected function getMostCommonErrors($reports): array
    {
        return $reports->groupBy('exception_class')
            ->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'latest_message' => $group->first()->message,
                    'first_occurrence' => $group->sortBy('reported_at')->first()->reported_at->toISOString(),
                    'last_occurrence' => $group->sortByDesc('reported_at')->first()->reported_at->toISOString(),
                ];
            })
            ->sortByDesc('count')
            ->take(10)
            ->toArray();
    }
}
```

## Backup & Recovery

### Backup Service
```php
// app/Services/BackupService.php
namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class BackupService
{
    public function createBackup(array $options = []): array
    {
        $startTime = now();
        
        try {
            // Create database backup
            $databaseBackup = $this->createDatabaseBackup($options);
            
            // Create file backup
            $fileBackup = $this->createFileBackup($options);
            
            $endTime = now();
            
            $backupInfo = [
                'id' => uniqid(),
                'type' => 'full_backup',
                'status' => 'completed',
                'database_backup' => $databaseBackup,
                'file_backup' => $fileBackup,
                'started_at' => $startTime->toISOString(),
                'completed_at' => $endTime->toISOString(),
                'duration_seconds' => $endTime->diffInSeconds($startTime),
            ];
            
            // Store backup metadata
            Storage::disk('backups')->put(
                "metadata/{$backupInfo['id']}.json",
                json_encode($backupInfo, JSON_PRETTY_PRINT)
            );
            
            Log::info('Backup completed successfully', $backupInfo);
            
            return $backupInfo;
        } catch (\Exception $e) {
            Log::error('Backup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return [
                'status' => 'failed',
                'error' => $e->getMessage(),
                'started_at' => $startTime->toISOString(),
                'completed_at' => now()->toISOString(),
            ];
        }
    }
    
    protected function createDatabaseBackup(array $options): array
    {
        $backupName = 'database_' . date('Y-m-d_H-i-s') . '.sql';
        $backupPath = storage_path("app/backups/{$backupName}");
        
        // Use mysqldump or equivalent for database backup
        $command = "mysqldump --host=" . config('database.connections.mysql.host') .
                  " --user=" . config('database.connections.mysql.username') .
                  " --password=" . config('database.connections.mysql.password') .
                  " " . config('database.connections.mysql.database') .
                  " > " . $backupPath;
                  
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new \Exception('Database backup failed with return code: ' . $returnCode);
        }
        
        // Upload to cloud storage
        $cloudPath = "backups/database/{$backupName}";
        Storage::disk('s3')->put($cloudPath, file_get_contents($backupPath));
        
        // Clean up local file
        unlink($backupPath);
        
        return [
            'name' => $backupName,
            'path' => $cloudPath,
            'size' => Storage::disk('s3')->size($cloudPath),
            'created_at' => now()->toISOString(),
        ];
    }
    
    protected function createFileBackup(array $options): array
    {
        $backupName = 'files_' . date('Y-m-d_H-i-s') . '.tar.gz';
        $backupPath = storage_path("app/backups/{$backupName}");
        
        // Create archive of important files
        $filesToBackup = [
            storage_path('app/public/images'),
            storage_path('app/documents'),
            // Add other important file directories
        ];
        
        $filesList = implode(' ', array_map('escapeshellarg', $filesToBackup));
        $command = "tar -czf {$backupPath} {$filesList}";
        
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new \Exception('File backup failed with return code: ' . $returnCode);
        }
        
        // Upload to cloud storage
        $cloudPath = "backups/files/{$backupName}";
        Storage::disk('s3')->put($cloudPath, file_get_contents($backupPath));
        
        // Clean up local file
        unlink($backupPath);
        
        return [
            'name' => $backupName,
            'path' => $cloudPath,
            'size' => Storage::disk('s3')->size($cloudPath),
            'created_at' => now()->toISOString(),
        ];
    }
    
    public function listBackups(): array
    {
        $backups = [];
        
        // Get database backups
        $databaseBackups = Storage::disk('s3')->files('backups/database');
        foreach ($databaseBackups as $backup) {
            $backups[] = [
                'type' => 'database',
                'name' => basename($backup),
                'path' => $backup,
                'size' => Storage::disk('s3')->size($backup),
                'modified' => Storage::disk('s3')->lastModified($backup),
            ];
        }
        
        // Get file backups
        $fileBackups = Storage::disk('s3')->files('backups/files');
        foreach ($fileBackups as $backup) {
            $backups[] = [
                'type' => 'files',
                'name' => basename($backup),
                'path' => $backup,
                'size' => Storage::disk('s3')->size($backup),
                'modified' => Storage::disk('s3')->lastModified($backup),
            ];
        }
        
        // Sort by modification time (newest first)
        usort($backups, function ($a, $b) {
            return $b['modified'] <=> $a['modified'];
        });
        
        return $backups;
    }
    
    public function restoreBackup(string $backupPath): void
    {
        // Download backup from cloud storage
        $localPath = storage_path('app/temp_restore.sql');
        Storage::disk('s3')->get($backupPath);
        file_put_contents($localPath, Storage::disk('s3')->get($backupPath));
        
        // Restore database
        $command = "mysql --host=" . config('database.connections.mysql.host') .
                  " --user=" . config('database.connections.mysql.username') .
                  " --password=" . config('database.connections.mysql.password') .
                  " " . config('database.connections.mysql.database') .
                  " < " . $localPath;
                  
        exec($command, $output, $returnCode);
        
        // Clean up
        unlink($localPath);
        
        if ($returnCode !== 0) {
            throw new \Exception('Database restore failed with return code: ' . $returnCode);
        }
        
        Log::info('Backup restored successfully', ['backup_path' => $backupPath]);
    }
}
```

## Resource Monitoring

### Resource Monitor Service
```php
// app/Services/ResourceMonitorService.php
namespace App\Services;

use App\Models\ResourceUsage;
use Illuminate\Support\Facades\Log;

class ResourceMonitorService
{
    public function collectResourceUsage(): void
    {
        $usage = [
            'cpu_usage' => $this->getCpuUsage(),
            'memory_usage' => $this->getMemoryUsage(),
            'disk_usage' => $this->getDiskUsage(),
            'network_usage' => $this->getNetworkUsage(),
            'process_count' => $this->getProcessCount(),
            'load_average' => $this->getLoadAverage(),
        ];
        
        ResourceUsage::create([
            'usage_data' => $usage,
            'collected_at' => now(),
        ]);
        
        // Check for resource thresholds
        $this->checkResourceThresholds($usage);
    }
    
    protected function getCpuUsage(): float
    {
        // This would integrate with system monitoring tools
        // For now, return placeholder data
        return rand(10, 90);
    }
    
    protected function getMemoryUsage(): array
    {
        $totalMemory = $this->getSystemMemoryTotal();
        $usedMemory = $this->getSystemMemoryUsed();
        
        return [
            'total' => $totalMemory,
            'used' => $usedMemory,
            'percentage' => $totalMemory > 0 ? ($usedMemory / $totalMemory) * 100 : 0,
        ];
    }
    
    protected function getSystemMemoryTotal(): int
    {
        // This would read from system files or use sys_get_temp_dir()
        // For now, return placeholder data
        return 8 * 1024 * 1024 * 1024; // 8GB
    }
    
    protected function getSystemMemoryUsed(): int
    {
        // This would read from system files or use memory_get_usage()
        // For now, return placeholder data
        return memory_get_usage(true);
    }
    
    protected function getDiskUsage(): array
    {
        $totalSpace = disk_total_space(storage_path());
        $freeSpace = disk_free_space(storage_path());
        $usedSpace = $totalSpace - $freeSpace;
        
        return [
            'total' => $totalSpace,
            'used' => $usedSpace,
            'free' => $freeSpace,
            'percentage' => $totalSpace > 0 ? ($usedSpace / $totalSpace) * 100 : 0,
        ];
    }
    
    protected function getNetworkUsage(): array
    {
        // This would integrate with network monitoring tools
        // For now, return placeholder data
        return [
            'bytes_in' => rand(1000000, 10000000),
            'bytes_out' => rand(1000000, 10000000),
            'connections' => rand(10, 1000),
        ];
    }
    
    protected function getProcessCount(): int
    {
        // This would integrate with process monitoring tools
        // For now, return placeholder data
        return rand(50, 200);
    }
    
    protected function getLoadAverage(): array
    {
        // This would read from /proc/loadavg or use system calls
        // For now, return placeholder data
        return [
            '1_minute' => rand(0.1, 2.0),
            '5_minute' => rand(0.1, 2.0),
            '15_minute' => rand(0.1, 2.0),
        ];
    }
    
    protected function checkResourceThresholds(array $usage): void
    {
        // Check CPU usage threshold
        if ($usage['cpu_usage'] > 90) {
            $this->sendAlert('High CPU usage detected', $usage);
        }
        
        // Check memory usage threshold
        if ($usage['memory_usage']['percentage'] > 90) {
            $this->sendAlert('High memory usage detected', $usage);
        }
        
        // Check disk usage threshold
        if ($usage['disk_usage']['percentage'] > 90) {
            $this->sendAlert('High disk usage detected', $usage);
        }
        
        // Check load average threshold
        if ($usage['load_average']['1_minute'] > 2.0) {
            $this->sendAlert('High system load detected', $usage);
        }
    }
    
    protected function sendAlert(string $message, array $usage): void
    {
        Log::warning($message, $usage);
        
        // Send to notification service
        // This would integrate with monitoring tools like Sentry, New Relic, etc.
    }
    
    public function getResourceReport(int $hours = 24): array
    {
        $usageRecords = ResourceUsage::where('collected_at', '>', now()->subHours($hours))
            ->orderBy('collected_at')
            ->get();
            
        return [
            'period' => [
                'start' => now()->subHours($hours)->toISOString(),
                'end' => now()->toISOString(),
            ],
            'usage_records' => $usageRecords->toArray(),
            'averages' => $this->calculateResourceAverages($usageRecords),
            'peaks' => $this->findResourcePeaks($usageRecords),
        ];
    }
    
    protected function calculateResourceAverages($records): array
    {
        if ($records->isEmpty()) {
            return [];
        }
        
        $averages = [
            'cpu_usage' => $records->avg('usage_data.cpu_usage'),
            'memory_percentage' => $records->avg('usage_data.memory_usage.percentage'),
            'disk_percentage' => $records->avg('usage_data.disk_usage.percentage'),
        ];
        
        return $averages;
    }
    
    protected function findResourcePeaks($records): array
    {
        if ($records->isEmpty()) {
            return [];
        }
        
        return [
            'cpu_peak' => $records->max('usage_data.cpu_usage'),
            'memory_peak' => $records->max('usage_data.memory_usage.percentage'),
            'disk_peak' => $records->max('usage_data.disk_usage.percentage'),
        ];
    }
}
```

## Maintenance Operations

### Maintenance Service
```php
// app/Services/MaintenanceService.php
namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class MaintenanceService
{
    public function performDailyMaintenance(): array
    {
        $results = [];
        $startTime = now();
        
        try {
            // Clear expired cache
            $results['cache_clear'] = $this->clearExpiredCache();
            
            // Optimize database
            $results['database_optimize'] = $this->optimizeDatabase();
            
            // Clean up old logs
            $results['log_cleanup'] = $this->cleanupOldLogs();
            
            // Clean up temporary files
            $results['temp_cleanup'] = $this->cleanupTempFiles();
            
            // Rotate logs
            $results['log_rotate'] = $this->rotateLogs();
            
            // Update search indexes
            $results['search_index'] = $this->updateSearchIndexes();
            
            $endTime = now();
            
            $results['status'] = 'completed';
            $results['duration'] = $endTime->diffInSeconds($startTime);
            
            Log::info('Daily maintenance completed', $results);
            
        } catch (\Exception $e) {
            $results['status'] = 'failed';
            $results['error'] = $e->getMessage();
            
            Log::error('Daily maintenance failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
        
        return $results;
    }
    
    protected function clearExpiredCache(): array
    {
        $startTime = now();
        
        // Clear expired cache entries
        Cache::flush();
        
        $endTime = now();
        
        return [
            'status' => 'completed',
            'duration' => $endTime->diffInSeconds($startTime),
        ];
    }
    
    protected function optimizeDatabase(): array
    {
        $startTime = now();
        
        // Optimize database tables
        Artisan::call('optimize:database');
        
        $endTime = now();
        
        return [
            'status' => 'completed',
            'duration' => $endTime->diffInSeconds($startTime),
        ];
    }
    
    protected function cleanupOldLogs(): array
    {
        $startTime = now();
        
        // Clean up logs older than 30 days
        $deletedCount = DB::table('logs')
            ->where('created_at', '<', now()->subDays(30))
            ->delete();
            
        $endTime = now();
        
        return [
            'status' => 'completed',
            'duration' => $endTime->diffInSeconds($startTime),
            'deleted_records' => $deletedCount,
        ];
    }
    
    protected function cleanupTempFiles(): array
    {
        $startTime = now();
        
        $tempDir = storage_path('app/temp');
        $deletedCount = 0;
        
        if (is_dir($tempDir)) {
            $files = glob("{$tempDir}/*");
            foreach ($files as $file) {
                if (is_file($file) && filemtime($file) < strtotime('-1 day')) {
                    unlink($file);
                    $deletedCount++;
                }
            }
        }
        
        $endTime = now();
        
        return [
            'status' => 'completed',
            'duration' => $endTime->diffInSeconds($startTime),
            'deleted_files' => $deletedCount,
        ];
    }
    
    protected function rotateLogs(): array
    {
        $startTime = now();
        
        // Rotate logs using Laravel's built-in log rotation
        // This is typically handled by the logging configuration
        
        $endTime = now();
        
        return [
            'status' => 'completed',
            'duration' => $endTime->diffInSeconds($startTime),
        ];
    }
    
    protected function updateSearchIndexes(): array
    {
        $startTime = now();
        
        // Update search indexes
        Artisan::call('scout:import', [
            '--class' => 'App\Models\Product',
        ]);
        
        Artisan::call('scout:import', [
            '--class' => 'App\Models\User',
        ]);
        
        $endTime = now();
        
        return [
            'status' => 'completed',
            'duration' => $endTime->diffInSeconds($startTime),
        ];
    }
    
    public function performWeeklyMaintenance(): array
    {
        $results = [];
        $startTime = now();
        
        try {
            // Database backup
            $results['database_backup'] = $this->performDatabaseBackup();
            
            // Archive old data
            $results['data_archive'] = $this->archiveOldData();
            
            // Update statistics
            $results['statistics_update'] = $this->updateStatistics();
            
            $endTime = now();
            
            $results['status'] = 'completed';
            $results['duration'] = $endTime->diffInSeconds($startTime);
            
            Log::info('Weekly maintenance completed', $results);
            
        } catch (\Exception $e) {
            $results['status'] = 'failed';
            $results['error'] = $e->getMessage();
            
            Log::error('Weekly maintenance failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
        
        return $results;
    }
    
    protected function performDatabaseBackup(): array
    {
        $startTime = now();
        
        // Perform database backup
        $backupService = new BackupService();
        $backupResult = $backupService->createBackup();
        
        $endTime = now();
        
        return [
            'status' => $backupResult['status'],
            'duration' => $endTime->diffInSeconds($startTime),
            'backup_id' => $backupResult['id'] ?? null,
        ];
    }
    
    protected function archiveOldData(): array
    {
        $startTime = now();
        
        // Archive old action logs
        $archivedActionLogs = DB::table('action_logs')
            ->where('created_at', '<', now()->subMonths(6))
            ->update(['is_archived' => true]);
            
        // Archive old system logs
        $archivedSystemLogs = DB::table('system_logs')
            ->where('created_at', '<', now()->subMonths(6))
            ->update(['is_archived' => true]);
        
        $endTime = now();
        
        return [
            'status' => 'completed',
            'duration' => $endTime->diffInSeconds($startTime),
            'archived_action_logs' => $archivedActionLogs,
            'archived_system_logs' => $archivedSystemLogs,
        ];
    }
    
    protected function updateStatistics(): array
    {
        $startTime = now();
        
        // Update system statistics
        Artisan::call('app:update-statistics');
        
        $endTime = now();
        
        return [
            'status' => 'completed',
            'duration' => $endTime->diffInSeconds($startTime),
        ];
    }
}
```

## Laravel-Native Features Utilized

### Forge Integration
- Laravel Forge for server provisioning and management
- Zero-downtime deployment with Envoyer
- SSL certificate management
- Server monitoring and alerts

### Horizon Integration
- Laravel Horizon for queue monitoring
- Job metrics and performance tracking
- Failed job management and retry
- Queue worker scaling

### Telescope Integration
- Laravel Telescope for debugging
- Request monitoring and analysis
- Exception tracking and debugging
- Database query monitoring

### Scout Integration
- Laravel Scout for search indexing
- Algolia or Elasticsearch integration
- Search query optimization
- Index maintenance

### Cashier Integration
- Laravel Cashier for payment processing
- Subscription management
- Invoice generation
- Payment method management

## System Monitoring Implementation

### System Health Controller
```php
// app/Http/Controllers/System/HealthController.php
namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Services\HealthCheckService;
use Illuminate\Http\Request;

class HealthController extends Controller
{
    protected $healthCheckService;
    
    public function __construct(HealthCheckService $healthCheckService)
    {
        $this->healthCheckService = $healthCheckService;
    }
    
    public function index()
    {
        $health = $this->healthCheckService->performFullHealthCheck();
        
        $statusCode = $health['status'] === 'healthy' ? 200 : 503;
        
        return response()->json($health, $statusCode);
    }
    
    public function database()
    {
        $result = $this->healthCheckService->checkDatabase();
        return response()->json($result);
    }
    
    public function cache()
    {
        $result = $this->healthCheckService->checkCache();
        return response()->json($result);
    }
    
    public function queue()
    {
        $result = $this->healthCheckService->checkQueue();
        return response()->json($result);
    }
}
```

### Performance Monitoring Middleware
```php
// app/Http/Middleware/PerformanceMonitor.php
namespace App\Http\Middleware;

use Closure;
use App\Services\PerformanceMonitorService;

class PerformanceMonitor
{
    protected $performanceMonitor;
    
    public function __construct(PerformanceMonitorService $performanceMonitor)
    {
        $this->performanceMonitor = $performanceMonitor;
    }
    
    public function handle($request, Closure $next)
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);
        
        $response = $next($request);
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        
        // Record performance metrics
        $this->recordMetrics($request, $response, $endTime - $startTime, $endMemory - $startMemory);
        
        return $response;
    }
    
    protected function recordMetrics($request, $response, $duration, $memoryUsage): void
    {
        // This would integrate with the performance monitoring service
        // to record request duration and memory usage
    }
}
```

## Data Migration Strategy

### Infrastructure Migration
- Migrate server configuration to Laravel Forge
- Set up deployment automation with Laravel Envoyer
- Configure monitoring with Laravel Horizon and Telescope
- Implement backup strategies with custom backup service
- Set up logging and alerting systems
- Configure security measures and access controls
- Implement disaster recovery procedures
- Set up performance monitoring and optimization

## Dependencies
- Laravel Framework
- Laravel Forge (server management)
- Laravel Envoyer (deployment)
- Laravel Horizon (queue monitoring)
- Laravel Telescope (debugging)
- Laravel Scout (search)
- Laravel Cashier (payments)
- Database (MySQL/PostgreSQL)
- Redis (caching and queues)
- Cloud storage (AWS S3, etc.)
- Monitoring tools (New Relic, Datadog, etc.)

## Definition of Done
- [ ] System health checks provide comprehensive infrastructure status
- [ ] Performance monitoring tracks all key metrics with alerts
- [ ] Error tracking captures and reports all application errors
- [ ] Backup procedures ensure data protection and recoverability
- [ ] Resource monitoring prevents system overload and capacity issues
- [ ] Maintenance operations keep the system optimized and secure
- [ ] Deployment automation ensures zero-downtime releases
- [ ] Monitoring dashboards provide real-time system visibility
- [ ] Alerting systems notify administrators of critical issues
- [ ] Logging provides comprehensive audit trails
- [ ] Security measures protect against threats and vulnerabilities
- [ ] Disaster recovery procedures ensure business continuity
- [ ] Performance benchmarks met (response time < 200ms)
- [ ] Uptime targets achieved (> 99.9% availability)
- [ ] Resource utilization stays within acceptable limits
- [ ] Backup and restore procedures tested regularly
- [ ] Security audits conducted periodically
- [ ] Capacity planning ensures scalability
- [ ] Incident response procedures established
- [ ] Documentation maintained for all operational procedures
- [ ] Training provided for operational staff
- [ ] Compliance requirements met for operational procedures
- [ ] Cost optimization measures implemented
- [ ] Vendor management procedures established
- [ ] Change management processes implemented
- [ ] Knowledge transfer completed for operational procedures
- [ ] Service level agreements established and monitored
- [ ] Continuous improvement processes implemented
- [ ] Feedback loops established for operational improvements
- [ ] Adequate test coverage for infrastructure components
- [ ] Error handling for edge cases with proper fallbacks
- [ ] Monitoring coverage for all critical system components
- [ ] Alert thresholds properly configured for all metrics
- [ ] Backup verification procedures implemented
- [ ] Recovery time objectives (RTO) and recovery point objectives (RPO) defined and met
- [ ] Security incident response procedures established and tested
- [ ] Performance optimization techniques implemented
- [ ] Scalability testing completed and documented
- [ ] Disaster recovery testing completed and documented
- [ ] Compliance auditing implemented and documented
- [ ] Cost monitoring and optimization implemented
- [ ] Vendor performance monitoring implemented
- [ ] Change management processes documented and followed
- [ ] Knowledge management processes implemented
- [ ] Service level monitoring and reporting implemented
- [ ] Continuous improvement initiatives established
- [ ] Customer feedback integration for operational improvements