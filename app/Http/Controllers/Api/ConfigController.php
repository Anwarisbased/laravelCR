<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ConfigService;

class ConfigController extends Controller
{
    public function __construct(private ConfigService $configService) {}

    public function getAppConfig()
    {
        $config = $this->configService->get_app_config();
        return response()->json(['success' => true, 'data' => $config]);
    }
}