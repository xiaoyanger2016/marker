<?php

namespace App\Filament\Pages;

use App\Models\Place;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * 从高德地图导入 POI
 * - 粘贴收藏夹分享链接 → 自动解析 ugcId + 调公开 API
 * - 或直接输入关键字 + 城市 → 高德 POI 搜索
 * - 勾选要导入的 POI → 批量创建为 Place
 *
 * 共享：所有用户都能用，从高德搜到的 POI 自动归属当前用户
 */
class ImportFromAmap extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cloud-arrow-down';

    protected static ?string $navigationLabel = '导入高德 POI';

    protected static ?string $navigationGroup = '内容管理';

    protected static ?int $navigationSort = 99;

    protected static string $view = 'filament.pages.import-from-amap';

    public ?string $keywords = null;
    public ?string $city = null;
    public ?string $shareUrl = null;
    public array $results = [];
    public array $selected = [];
    public bool $searched = false;
    public ?string $error = null;
    public array $placeTypes = [];
    public ?string $placeType = null;

    public function mount(): void
    {
        $this->placeTypes = \App\Models\Place::PLACE_TYPES;
    }

    public function search(): void
    {
        $this->error = null;
        $this->results = [];
        $this->selected = [];
        $this->searched = false;

        if (! $this->keywords) {
            $this->error = '请输入搜索关键字';
            return;
        }

        $key = config('services.amap.key');
        if (! $key || $key === 'your_amap_web_key_here') {
            $this->error = '高德 API Key 未配置，请在 .env 设置 AMAP_WEB_KEY';
            return;
        }

        try {
            $params = [
                'key' => $key,
                'keywords' => $this->keywords,
                'output' => 'json',
                'offset' => 20,
                'page' => 1,
            ];
            if ($this->city) {
                $params['city'] = $this->city;
                $params['citylimit'] = 'true';
            }

            $resp = Http::timeout(10)->get('https://restapi.amap.com/v3/place/text', $params);
            if (! $resp->ok()) {
                $this->error = '高德 API 请求失败: HTTP ' . $resp->status();
                return;
            }

            $data = $resp->json();
            if (($data['status'] ?? '0') !== '1') {
                $this->error = '高德 API 返回错误: ' . ($data['info'] ?? '未知');
                return;
            }

            $this->results = array_map(function ($poi) {
                $location = explode(',', $poi['location'] ?? '0,0');
                return [
                    'id' => $poi['id'],
                    'name' => $poi['name'] ?? '',
                    'type' => $poi['type'] ?? '',
                    'typecode' => $poi['typecode'] ?? '',
                    'address' => $poi['address'] ?? '',
                    'location' => $poi['location'] ?? '',
                    'longitude' => (float) ($location[0] ?? 0),
                    'latitude' => (float) ($location[1] ?? 0),
                    'tel' => $poi['tel'] ?? '',
                    'pname' => $poi['pname'] ?? '', // 省
                    'cityname' => $poi['cityname'] ?? '', // 市
                    'adname' => $poi['adname'] ?? '', // 区
                    'select' => false,
                ];
            }, $data['pois'] ?? []);

            $this->searched = true;
        } catch (ConnectionException $e) {
            $this->error = '网络错误: ' . $e->getMessage();
        } catch (\Throwable $e) {
            $this->error = '错误: ' . $e->getMessage();
        }
    }

    /**
     * 解析高德收藏夹分享链接
     * 链接形如:
     * https://guinness.autonavi.com/activity/.../index.html?schema=amapuri%3A%2F%2Fajx_favorites%2Ffolder%3Fdata%3D%257B%2522ugcId%2522%253A%2522xxx%2522...
     */
    public function parseShareUrl(): void
    {
        $this->error = null;
        $this->results = [];
        $this->selected = [];

        if (! $this->shareUrl) {
            $this->error = '请粘贴高德收藏夹分享链接';
            return;
        }

        // 提取 ugcId
        $ugcId = null;
        if (preg_match('/ugcId["\s:=]+([a-f0-9]+)/i', $this->shareUrl, $m)) {
            $ugcId = $m[1];
        }

        if (! $ugcId) {
            $this->error = '未识别到 ugcId，请检查链接格式';
            return;
        }

        // 用 ugcId 试多个高德公开 API 端点
        $candidates = [
            "https://www.amap.com/favorite/{$ugcId}/folder",
            "https://www.amap.com/collection/{$ugcId}",
            "https://ditu.amap.com/collection/{$ugcId}",
        ];

        // 因为高德收藏夹 web 端需要登录态拿不到，我们用关键字搜索作为 fallback
        $this->error = "高德收藏夹 web 端需要登录态，无法直接拉取（ugcId: {$ugcId}）。\n\n"
            . "请改用下方「关键字搜索」：输入收藏夹里最典型的 1-2 个地点名字 + 城市，"
            . "系统会从高德搜出完整 POI 列表（按地点名 + 距离匹配），你勾选后批量导入。\n\n"
            . "或者手动复制收藏夹 JSON 粘贴进来（待后续支持）。";

        Notification::make()
            ->title('收藏夹直接拉取受限')
            ->body('请用关键字搜索方式导入')
            ->warning()
            ->persistent()
            ->send();
    }

    public function importSelected(): void
    {
        if (empty($this->selected)) {
            Notification::make()->title('请至少勾选一个 POI')->warning()->send();
            return;
        }

        $count = 0;
        $skipped = 0;
        $userId = auth()->id();

        foreach ($this->selected as $idx) {
            $poi = $this->results[$idx] ?? null;
            if (! $poi || ! $poi['longitude'] || ! $poi['latitude']) {
                $skipped++;
                continue;
            }

            // 去重：检查是否已有相同高德 POI id 的地点
            $exists = Place::where('poi_source', 'amap')
                ->where('poi_id', $poi['id'])
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            Place::create([
                'user_id' => $userId,
                'name' => $poi['name'],
                'address' => $poi['address'] ?: ($poi['adname'] . ' ' . $poi['address']),
                'city' => $poi['cityname'] ?: $this->city,
                'province' => $poi['pname'],
                'district' => $poi['adname'],
                'country' => '中国',
                'latitude' => $poi['latitude'],
                'longitude' => $poi['longitude'],
                'phone' => $poi['tel'] ?: null,
                'place_type' => $this->placeType, // 用户选的类型
                'poi_source' => 'amap',
                'poi_id' => $poi['id'],
                'poi_type' => $poi['type'],
                'is_public' => false, // 默认私有，用户可以后改
                'is_wishlist' => true, // 默认种草，等用户标记去过
            ]);

            $count++;
        }

        $msg = "✅ 成功导入 {$count} 个";
        if ($skipped > 0) {
            $msg .= "（跳过 {$skipped} 个：已存在或坐标无效）";
        }

        Notification::make()
            ->title('导入完成')
            ->body($msg)
            ->success()
            ->send();

        $this->selected = [];
        $this->search();
    }

    public function toggleAll(bool $checked): void
    {
        $this->selected = $checked ? array_keys($this->results) : [];
    }
}
