<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * 高德 Web API 代理
 * 文档：https://lbs.amap.com/api/webservice/guide/api/search
 */
class AmapController extends Controller
{
    public function textSearch(Request $request): JsonResponse
    {
        $data = $request->validate([
            'keywords' => 'required|string|max:100',
            'city' => 'nullable|string|max:60',
            'citylimit' => 'sometimes|boolean',
            'types' => 'nullable|string|max:200',
            'offset' => 'sometimes|integer|min:1|max:50',
            'page' => 'sometimes|integer|min:1',
        ]);

        $key = config('services.amap.key');
        if (! $key || $key === 'your_amap_web_key_here') {
            return response()->json([
                'message' => '高德 API Key 未配置',
                'hint' => '请在 .env 设置 AMAP_WEB_KEY',
            ], 503);
        }

        // 缓存 5 分钟，避免超限
        $cacheKey = 'amap:text:' . md5(json_encode($data));
        $result = Cache::remember($cacheKey, 300, function () use ($data, $key) {
            try {
                $resp = Http::timeout(10)->get('https://restapi.amap.com/v3/place/text', array_merge([
                    'key' => $key,
                    'output' => 'json',
                    'offset' => 20,
                    'page' => 1,
                ], $data));

                if (! $resp->ok()) {
                    return ['error' => '请求失败', 'status' => $resp->status()];
                }
                return $resp->json();
            } catch (ConnectionException $e) {
                return ['error' => '无法连接高德 API', 'detail' => $e->getMessage()];
            }
        });

        return response()->json($result);
    }

    public function aroundSearch(Request $request): JsonResponse
    {
        $data = $request->validate([
            'location' => 'required|string', // lng,lat
            'keywords' => 'nullable|string|max:100',
            'radius' => 'sometimes|integer|min:100|max:50000',
            'types' => 'nullable|string',
        ]);

        $key = config('services.amap.key');
        if (! $key || $key === 'your_amap_web_key_here') {
            return response()->json(['message' => 'AMAP_WEB_KEY 未配置'], 503);
        }

        $cacheKey = 'amap:around:' . md5(json_encode($data));
        $result = Cache::remember($cacheKey, 300, function () use ($data, $key) {
            try {
                $resp = Http::timeout(10)->get('https://restapi.amap.com/v3/place/around', array_merge([
                    'key' => $key,
                    'output' => 'json',
                    'radius' => 3000,
                ], $data));
                return $resp->ok() ? $resp->json() : ['error' => '请求失败'];
            } catch (ConnectionException $e) {
                return ['error' => '无法连接高德 API'];
            }
        });

        return response()->json($result);
    }

    public function geocode(Request $request): JsonResponse
    {
        $data = $request->validate([
            'address' => 'required|string|max:200',
            'city' => 'nullable|string',
        ]);

        $key = config('services.amap.key');
        if (! $key || $key === 'your_amap_web_key_here') {
            return response()->json(['message' => 'AMAP_WEB_KEY 未配置'], 503);
        }

        $cacheKey = 'amap:geo:' . md5(json_encode($data));
        $result = Cache::remember($cacheKey, 3600, function () use ($data, $key) {
            try {
                $resp = Http::timeout(10)->get('https://restapi.amap.com/v3/geocode/geo', array_merge([
                    'key' => $key,
                    'output' => 'json',
                ], $data));
                return $resp->ok() ? $resp->json() : ['error' => '请求失败'];
            } catch (ConnectionException $e) {
                return ['error' => '无法连接高德 API'];
            }
        });

        return response()->json($result);
    }
}
