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
        // Phase 16: 8 大类从 Content::TYPES 拿（POI 导入后会作为 place 给 Content 用）
        $this->placeTypes = \App\Models\Content::TYPES;
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

        // 高德分享链接通常双重 URL encode（因为是 query string 里再嵌套 query string）
        // 例如: ...schema=amapuri%3A%2F%2Fajx_favorites%2Ffolder%3Fdata%3D%257B%2522ugcId%2522%253A%2522066...%257D
        //     解 1 次 → amapuri://ajx_favorites/folder?data=%7B%22ugcId%22%3A%22066...%22%7D
        //     解 2 次 → amapuri://ajx_favorites/folder?data={"ugcId":"066..."}
        $decoded1 = urldecode($this->shareUrl);
        $decoded2 = urldecode($decoded1);

        // 提取 ugcId（按解码深度逐一尝试，兼容 1-3 层编码）
        $ugcId = null;
        foreach ([$decoded2, $decoded1, $this->shareUrl] as $candidate) {
            // 模式 1: JSON-style  "ugcId":"xxx"  或  "ugcId": "xxx"
            if (preg_match('/"ugcId"\s*:\s*"([^"]+)"/i', $candidate, $m)) {
                $ugcId = $m[1];
                break;
            }
            // 模式 2: query-style ugcId=xxx&...  或  ugcId=xxx
            if (preg_match('/ugcId=([a-f0-9]+)/i', $candidate, $m)) {
                $ugcId = $m[1];
                break;
            }
            // 模式 3: 任意分隔符后跟 [a-f0-9]+ 至少 16 位（ugcId 是 20 位数字）
            if (preg_match('/ugcId[^a-f0-9]{0,5}([a-f0-9]{16,})/i', $candidate, $m)) {
                $ugcId = $m[1];
                break;
            }
        }

        if (! $ugcId) {
            $this->error = "未识别到 ugcId，请检查链接格式。\n\n"
                . "支持格式：\n"
                . "1. https://guinness.autonavi.com/activity/.../index.html?schema=amapuri%3A%2F%2F...\n"
                . "2. amapuri://ajx_favorites/folder?data={\"ugcId\":\"0661...\"}\n"
                . "3. 任何包含 ugcId=xxx 的高德分享链接";
            return;
        }

        // 验：ugcId 必须是 18-22 位数字（高德格式）
        if (! preg_match('/^[0-9]{18,22}$/', $ugcId)) {
            $this->error = "识别到 ugcId={$ugcId} 但格式不像（高德 ugcId 应是 18-22 位纯数字）。"
                . "可能是解析错了，请用关键字搜索 fallback。";
            return;
        }

        // 因为高德收藏夹 web 端需要登录态拿不到，我们用 ugcId + 关键字搜索作为 fallback
        $this->error = "✓ 已识别 ugcId = {$ugcId}\n\n"
            . "高德收藏夹 web 端需要登录态，无法直接拉取列表。\n"
            . "请改用下方「关键字搜索」：输入收藏夹里最典型的 1-2 个地点名字 + 城市，"
            . "系统会从高德搜出完整 POI 列表（按地点名 + 距离匹配），你勾选后批量导入。";

        Notification::make()
            ->title('ugcId 已识别 · ugcId: ' . $ugcId)
            ->body('收藏夹直拉受限，请用关键字搜索方式')
            ->info()
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

        $msg = "已成功导入 {$count} 个";
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
