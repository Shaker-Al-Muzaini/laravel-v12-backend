<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

class HealthController extends Controller
{
    /**
     * Liveness probe endpoint - Basic health check
     * Used by Kubernetes to determine if the pod should be restarted
     */
    public function live(): JsonResponse
    {
        return response()->json([
            'status' => 'alive',
            'timestamp' => now()->toISOString(),
            'service' => config('app.name', 'laravel-app'),
            'pod' => gethostname()
        ], 200);
    }

    /**
     * Readiness probe endpoint - Comprehensive dependency check
     * Used by Kubernetes to determine if the pod can receive traffic
     */
    public function ready(): JsonResponse
    {
        $checks = [];
        $allHealthy = true;

        // Database connectivity check
        $checks['database'] = $this->checkDatabase();
        if ($checks['database']['status'] !== 'healthy') {
            $allHealthy = false;
        }

        // Cache system check
        $checks['cache'] = $this->checkCache();
        if ($checks['cache']['status'] !== 'healthy') {
            $allHealthy = false;
        }

        // Redis check (if configured)
        $checks['redis'] = $this->checkRedis();
        if ($checks['redis']['status'] === 'unhealthy') {
            $allHealthy = false;
        }

        // Storage writability check
        $checks['storage'] = $this->checkStorage();
        if ($checks['storage']['status'] !== 'healthy') {
            $allHealthy = false;
        }

        // Environment information
        $checks['environment'] = $this->getEnvironmentInfo();

        $response = [
            'status' => $allHealthy ? 'ready' : 'not_ready',
            'timestamp' => now()->toISOString(),
            'service' => config('app.name', 'laravel-app'),
            'pod' => gethostname(),
            'checks' => $checks
        ];

        return response()->json($response, $allHealthy ? 200 : 503);
    }

    /**
     * Startup probe endpoint - Application initialization check
     * Used by Kubernetes to determine if the application has started
     */
    public function startup(): JsonResponse
    {
        $startupChecks = [];
        $isStarted = true;

        // Configuration loading check
        $startupChecks['config'] = $this->checkConfiguration();
        if ($startupChecks['config']['status'] !== 'ready') {
            $isStarted = false;
        }

        // Routes loading check
        $startupChecks['routes'] = $this->checkRoutes();
        if ($startupChecks['routes']['status'] !== 'ready') {
            $isStarted = false;
        }

        // Service container check
        $startupChecks['services'] = $this->checkServices();
        if ($startupChecks['services']['status'] !== 'ready') {
            $isStarted = false;
        }

        $response = [
            'status' => $isStarted ? 'started' : 'starting',
            'timestamp' => now()->toISOString(),
            'service' => config('app.name', 'laravel-app'),
            'pod' => gethostname(),
            'checks' => $startupChecks
        ];

        return response()->json($response, $isStarted ? 200 : 503);
    }

    /**
     * General health endpoint - Comprehensive system information
     */
    public function health(): JsonResponse
    {
        return response()->json([
            'status' => 'healthy',
            'timestamp' => now()->toISOString(),
            'service' => config('app.name', 'laravel-app'),
            'version' => config('app.version', '1.0.0'),
            'environment' => config('app.env'),
            'pod' => gethostname(),
            'uptime' => $this->getUptimeInfo(),
            'system' => $this->getSystemInfo(),
            'git' => $this->getGitInfo()
        ], 200);
    }

    /**
     * Check database connectivity
     */
    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            return [
                'status' => 'healthy',
                'message' => 'Database connection successful',
                'connection' => config('database.default')
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Database connection failed: ' . $e->getMessage(),
                'connection' => config('database.default')
            ];
        }
    }

    /**
     * Check cache system
     */
    private function checkCache(): array
    {
        try {
            $testKey = 'health_check_' . time() . '_' . uniqid();
            $testValue = 'test_' . time();
            
            Cache::put($testKey, $testValue, 10);
            $retrieved = Cache::get($testKey);
            Cache::forget($testKey);

            if ($retrieved === $testValue) {
                return [
                    'status' => 'healthy',
                    'message' => 'Cache system working',
                    'driver' => config('cache.default')
                ];
            } else {
                throw new \Exception('Cache test failed - value mismatch');
            }
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Cache system failed: ' . $e->getMessage(),
                'driver' => config('cache.default')
            ];
        }
    }

    /**
     * Check Redis connectivity
     */
    private function checkRedis(): array
    {
        try {
            if (config('cache.default') === 'redis' || 
                config('session.driver') === 'redis' || 
                config('queue.default') === 'redis') {
                
                Redis::ping();
                return [
                    'status' => 'healthy',
                    'message' => 'Redis connection successful'
                ];
            } else {
                return [
                    'status' => 'skipped',
                    'message' => 'Redis not configured'
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Redis connection failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check storage writability
     */
    private function checkStorage(): array
    {
        try {
            $storagePath = storage_path();
            $testFile = $storagePath . '/health_check_' . time() . '.tmp';
            
            if (!is_writable($storagePath)) {
                throw new \Exception('Storage path not writable');
            }
            
            // Test file write
            file_put_contents($testFile, 'health_check');
            if (file_get_contents($testFile) !== 'health_check') {
                throw new \Exception('File write/read test failed');
            }
            unlink($testFile);
            
            return [
                'status' => 'healthy',
                'message' => 'Storage path is writable',
                'path' => $storagePath
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Storage check failed: ' . $e->getMessage(),
                'path' => $storagePath ?? 'unknown'
            ];
        }
    }

    /**
     * Check application configuration
     */
    private function checkConfiguration(): array
    {
        try {
            $appName = config('app.name');
            $appKey = config('app.key');
            
            if (empty($appName) || empty($appKey)) {
                throw new \Exception('Missing required configuration');
            }
            
            return [
                'status' => 'ready',
                'message' => 'Application config loaded'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'not_ready',
                'message' => 'Config loading failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check routes loading
     */
    private function checkRoutes(): array
    {
        try {
            $routeCount = count(Route::getRoutes());
            return [
                'status' => 'ready',
                'message' => "Routes loaded ($routeCount routes)"
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'not_ready',
                'message' => 'Routes loading failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check core services
     */
    private function checkServices(): array
    {
        try {
            app('db');
            app('cache');
            app('config');
            
            return [
                'status' => 'ready',
                'message' => 'Core services bound'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'not_ready',
                'message' => 'Services binding failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get environment information
     */
    private function getEnvironmentInfo(): array
    {
        return [
            'app_env' => config('app.env'),
            'app_debug' => config('app.debug'),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'timezone' => config('app.timezone')
        ];
    }

    /**
     * Get uptime information
     */
    private function getUptimeInfo(): array
    {
        $uptime = defined('LARAVEL_START') ? time() - LARAVEL_START : 0;
        
        return [
            'seconds' => $uptime,
            'human' => gmdate('H:i:s', $uptime),
            'started_at' => defined('LARAVEL_START') ? 
                date('Y-m-d H:i:s', LARAVEL_START) : 'unknown'
        ];
    }

    /**
     * Get system information
     */
    private function getSystemInfo(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
            'memory_usage' => [
                'current' => memory_get_usage(true),
                'peak' => memory_get_peak_usage(true),
                'current_formatted' => $this->formatBytes(memory_get_usage(true)),
                'peak_formatted' => $this->formatBytes(memory_get_peak_usage(true))
            ],
            'disk_usage' => $this->getDiskUsage(),
            'server_time' => now()->toDateTimeString()
        ];
    }

    /**
     * Get Git information
     */
    private function getGitInfo(): array
    {
        try {
            $gitPath = base_path('.git/HEAD');
            if (file_exists($gitPath)) {
                $head = trim(file_get_contents($gitPath));
                if (strpos($head, 'ref:') === 0) {
                    $branch = trim(substr($head, 4));
                    $commitPath = base_path('.git/' . $branch);
                    $commit = file_exists($commitPath) ? trim(file_get_contents($commitPath)) : null;
                } else {
                    $commit = $head;
                    $branch = 'detached';
                }
                
                return [
                    'branch' => basename($branch ?? 'unknown'),
                    'commit' => substr($commit ?? 'unknown', 0, 8)
                ];
            }
        } catch (\Exception $e) {
            // Ignore git errors
        }
        
        return [
            'branch' => 'unknown',
            'commit' => 'unknown'
        ];
    }

    /**
     * Get disk usage information
     */
    private function getDiskUsage(): array
    {
        try {
            $path = base_path();
            $total = disk_total_space($path);
            $free = disk_free_space($path);
            $used = $total - $free;
            
            return [
                'total' => $this->formatBytes($total),
                'used' => $this->formatBytes($used),
                'free' => $this->formatBytes($free),
                'percentage' => round(($used / $total) * 100, 2)
            ];
        } catch (\Exception $e) {
            return [
                'error' => 'Unable to get disk usage: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $size, int $precision = 2): string
    {
        if ($size === 0) return '0 B';
        
        $base = log($size, 1024);
        $suffixes = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
    }
}