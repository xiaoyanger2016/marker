<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Region;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 区域三级联动 API（国家/省份/城市）
 *  - 公开接口，前台筛选 & 后台管理共用
 *  - GET /api/v1/regions          列出国家（可加 ?level=province 列出省）
 *  - GET /api/v1/regions/{code}   列出该 code 下的子级（省→市）
 *  - GET /api/v1/regions/search?q=北京  搜索城市
 */
class RegionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $level = $request->query('level', 'country');
        $parent = $request->query('parent');

        $q = Region::query()->where('level', $level);

        if ($parent !== null && $parent !== '') {
            // 显式传了 parent（包含 'null' 表示顶级）
            $q->where('parent_code', $parent === 'null' ? null : $parent);
        } elseif ($level === 'country') {
            $q->whereNull('parent_code');
        } elseif ($level === 'province') {
            // 不传 parent 时，省份默认挂在 CN（中国）下
            $q->where('parent_code', 'CN');
        } elseif ($level === 'city') {
            // 城市必须有 parent，不自动查全部
            $q->whereRaw('1=0');
        }

        $rows = $q->orderBy('sort')->orderBy('id')->get()
            ->map(fn ($r) => $r->toOption() + [
                'level' => $r->level,
                'parent' => $r->parent_code,
                'hot' => $r->is_hot,
            ]);

        return response()->json(['data' => $rows]);
    }

    public function children(string $code): JsonResponse
    {
        $rows = Region::where('parent_code', $code)
            ->orderBy('sort')->orderBy('id')->get()
            ->map(fn ($r) => $r->toOption() + [
                'level' => $r->level,
                'parent' => $r->parent_code,
                'hot' => $r->is_hot,
            ]);

        return response()->json(['data' => $rows]);
    }

    public function search(Request $request): JsonResponse
    {
        $keyword = trim((string) $request->query('q', ''));
        if ($keyword === '') {
            return response()->json(['data' => []]);
        }

        $rows = Region::query()
            ->where('level', 'city')
            ->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('pinyin', 'like', "%{$keyword}%")
                  ->orWhere('short_name', 'like', "%{$keyword}%");
            })
            ->orderByDesc('is_hot')
            ->orderBy('sort')
            ->limit(20)
            ->get()
            ->map(fn ($r) => $r->toOption() + [
                'level' => $r->level,
                'parent' => $r->parent_code,
            ]);

        return response()->json(['data' => $rows]);
    }
}
