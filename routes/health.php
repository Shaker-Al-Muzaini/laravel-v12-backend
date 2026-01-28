<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;

/*
|--------------------------------------------------------------------------
| Health Check Routes for Kubernetes Probes
|--------------------------------------------------------------------------
|
| These routes are used by Kubernetes for readiness and liveness probes
| to determine the health status of the application.
|
*/

/**
 * Liveness Probe - Checks if the application is alive
 * This should be a lightweight check that only verifies the basic application state
 * If this fails, Kubernetes will restart the pod
 */
Route::get('/health/live', function () {
    return response()->json([
        'status' => 'alive',
        'timestamp' => now()->toISOString(),
        'service' => 'laravel-app'
    ], 200);
});

/**
 * Readiness Probe - Checks if the application is ready to serve traffic
 * This should check all dependencies (database, cache, redis etc.)
 * If this fails, Kubernetes will stop sending traffic to this pod
 */
Route::get('/health/ready', function () {
    $checks = [];
    $allHealthy = true;
    
    // Check Database Connection
    try {
        DB::connection()->getPdo();
        $checks['database'] = [
            'status' => 'healthy',
            'message' => 'Database connection successful'
        ];
    } catch (\Exception $e) {
        $checks['database'] = [
            'status' => 'unhealthy',
            'message' => 'Database connection failed: ' . $e->getMessage()
        ];
        $allHealthy = false;
    }
    
    // Check Redis Connection (if using Redis)
    try {
        if (config('cache.default') === 'redis' || config('session.driver') === 'redis') {
            Redis::ping();
            $checks['redis'] = [
                'status' => 'healthy',
                'message' => 'Redis connection successful'
            ];
        } else {
            $checks['redis'] = [
                'status' => 'skipped',
                'message' => 'Redis not configured'
            ];
        }
    } catch (\Exception $e) {
        $checks['redis'] = [
            'status' => 'unhealthy',
            'message' => 'Redis connection failed: ' . $e->getMessage()
        ];
        $allHealthy = false;
    }
    
    // Check Cache System
    try {
        $testKey = 'health_check_' . time();
        Cache::put($testKey, 'test', 10);
        $retrieved = Cache::get($testKey);
        Cache::forget($testKey);
        
        if ($retrieved === 'test') {
            $checks['cache'] = [
                'status' => 'healthy',
                'message' => 'Cache system working'
            ];
        } else {
            throw new \Exception('Cache test failed');
        }
    } catch (\Exception $e) {
        $checks['cache'] = [
            'status' => 'unhealthy',
            'message' => 'Cache system failed: ' . $e->getMessage()
        ];
        $allHealthy = false;
    }
    
    // Check Storage Path
    try {
        $storagePath = storage_path();
        if (is_writable($storagePath)) {
            $checks['storage'] = [
                'status' => 'healthy',
                'message' => 'Storage path is writable'
            ];
        } else {
            throw new \Exception('Storage path not writable');
        }
    } catch (\Exception $e) {
        $checks['storage'] = [
            'status' => 'unhealthy',
            'message' => 'Storage check failed: ' . $e->getMessage()
        ];
        $allHealthy = false;
    }
    
    // Check Application Environment
    $checks['environment'] = [
        'app_env' => config('app.env'),
        'app_debug' => config('app.debug'),
        'php_version' => PHP_VERSION,
        'laravel_version' => app()->version()
    ];
    
    $response = [
        'status' => $allHealthy ? 'ready' : 'not_ready',
        'timestamp' => now()->toISOString(),
        'service' => 'laravel-app',
        'checks' => $checks
    ];

    
    
    return response()->json($response, $allHealthy ? 200 : 503);
});

/**
 * Startup Probe - Checks if the application has started successfully
 * This is used for applications that take longer to start
 */
Route::get('/health/startup', function () {
    // Check if critical initialization is complete
    $startupChecks = [];
    $isStarted = true;
    
    // Check if config is loaded
    try {
        $appName = config('app.name');
        $startupChecks['config'] = [
            'status' => 'ready',
            'message' => 'Application config loaded'
        ];
    } catch (\Exception $e) {
        $startupChecks['config'] = [
            'status' => 'not_ready',
            'message' => 'Config loading failed: ' . $e->getMessage()
        ];
        $isStarted = false;
    }
    
    // Check if routes are loaded
    try {
        $routeCount = count(Route::getRoutes());
        $startupChecks['routes'] = [
            'status' => 'ready',
            'message' => "Routes loaded ($routeCount routes)"
        ];
    } catch (\Exception $e) {
        $startupChecks['routes'] = [
            'status' => 'not_ready',
            'message' => 'Routes loading failed: ' . $e->getMessage()
        ];
        $isStarted = false;
    }
    
    // Check if services are bound
    try {
        app('db');
        $startupChecks['services'] = [
            'status' => 'ready',
            'message' => 'Core services bound'
        ];
        
    } catch (\Exception $e) {
        $startupChecks['services'] = [
            'status' => 'not_ready',
            'message' => 'Services binding failed: ' . $e->getMessage()
        ];
        $isStarted = false;
    }
    
    $response = [
        'status' => $isStarted ? 'started' : 'starting',
        'timestamp' => now()->toISOString(),
        'service' => 'laravel-app',
        'checks' => $startupChecks
    ];
    
    return response()->json($response, $isStarted ? 200 : 503);
});

 