<?php

namespace App\Filament\Pages;

use App\Models\Place;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * 从高德地图导入 POI
 * - 粘贴收藏夹分享链接 → 自动解析 ugcId + 自动调公开 API 搜收藏夹里的 POI
 * - 或直接输入关键字 + 城市 → 高德 POI 搜索
 * - 勾选要导入的 POI → 批量创建为 Place
 *
 * Phase 20 重构：
 *   - 不再用 Notification (会堆叠) → 改用 inline status banner
 *   - parseShareUrl 解析成功 → 自动 trigger search() 用 ugcId 当 keywords
 *   - 移动到「数据中台」菜单下
 */
class ImportFromAmap extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cloud-arrow-down';

    protected static ?string $navigationLabel = '导入高德 POI';

    protected static ?string $navigationGroup = '数据中台';

    protected static ?int $navigationSort = 30;

    protected static string $view = 'filament.pages.import-from-amap';

    public ?string $keywords = null;
    public ?string $city = null;
    public ?string $shareUrl = null;
    public array $results = [];
    public array $selected = [];
    public bool $searched = false;

    // Phase 20: 状态 banner 替代 Notification
    public ?string $bannerType = null;       // 'success' | 'warning' | 'danger' | 'info'
    public ?string $bannerTitle = null;
    public ?string $bannerBody = null;
    public ?string $bannerExtra = null;      // 额外提示 (如 ugcId)
    public ?string $lastUgcId = null;        // 最近解析的 ugcId (供 search 复用)

    public array $placeTypes = [];
    public ?string $placeType = null;

    public function mount(): void
    {
        $this->placeTypes = \App\Models\Content::TYPES;

        // 恢复上次 ugcId (如果用户上次中断)
        if (session()->has('amap_last_ugc_id')) {
            $this->lastUgcId = session('amap_last_ugc_id');
        }
    }

    /**
     * 关闭状态 banner
     */
    public function dismissBanner(): void
    {
        $this->bannerType = null;
        $this->bannerTitle = null;
        $this->bannerBody = null;
        $this->bannerExtra = null;
    }

    public function search(): void
    {
        $this->dismissBanner();
        $this->results = [];
        $this->selected = [];
        $this->searched = false;

        if (! $this->keywords) {
            $this->setBanner('warning', '请输入搜索关键字', '输入收藏夹里最典型的 1-2 个地点名 + 城市。');
            return;
        }

        $key = config('services.amap.key');
        if (! $key || $key === 'your_amap_web_key_here') {
            $this->setBanner('danger', '高德 API Key 未配置',
                "个人开发者 5 分钟就能申请（支付宝实名认证）：\n"
                . "  1. https://lbs.amap.com/ 注册 + 支付宝扫码实名\n"
                . "  2. 控制台 → 应用管理 → 创建新应用\n"
                . "  3. 添加 Key → 服务平台选「Web 服务」\n"
                . "  4. 复制 Key 写到 .env 的 AMAP_WEB_KEY=...\n"
                . "  5. 跑 php artisan config:clear\n"
                . "  6. 刷新本页\n\n"
                . "个人账户配额 2000 次/日，够用。"
            );
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
                $this->setBanner('danger', '高德 API 请求失败', 'HTTP ' . $resp->status());
                return;
            }

            $data = $resp->json();
            if (($data['status'] ?? '0') !== '1') {
                $this->setBanner('danger', '高德 API 返回错误', $data['info'] ?? '未知');
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
                    'pname' => $poi['pname'] ?? '',
                    'cityname' => $poi['cityname'] ?? '',
                    'adname' => $poi['adname'] ?? '',
                    'select' => false,
                ];
            }, $data['pois'] ?? []);

            $this->searched = true;

            $count = count($this->results);
            $bannerBody = "「{$this->keywords}」搜到 {$count} 个 POI · 勾选 → 选类型 → 批量导入";
            if ($this->lastUgcId && $this->keywords === $this->lastUgcId) {
                $bannerBody .= "\n（你刚解析的 ugcId=" . $this->lastUgcId . " 已被自动填到关键字搜索框。\n如需更精准，编辑关键词为收藏夹里具体的地点名 + 城市再搜一次。）";
            }
            $this->setBanner('success', '搜索完成', $bannerBody);
        } catch (ConnectionException $e) {
            $this->setBanner('danger', '网络错误', $e->getMessage());
        } catch (\Throwable $e) {
            $this->setBanner('danger', '错误', $e->getMessage());
        }
    }

    /**
     * 解析高德收藏夹分享链接
     * 链接形如:
     * https://guinness.autonavi.com/activity/.../index.html?schema=amapuri%3A%2F%2Fajx_favorites%2Ffolder%3Fdata%3D%257B%2522ugcId%2522%253A%2522xxx%2522...
     */
    public function parseShareUrl(): void
    {
        $this->results = [];
        $this->selected = [];
        $this->searched = false;

        if (! $this->shareUrl) {
            $this->setBanner('warning', '请粘贴高德收藏夹分享链接', null);
            return;
        }

        // 高德分享链接通常双重 URL encode
        $decoded1 = urldecode($this->shareUrl);
        $decoded2 = urldecode($decoded1);

        // 提取 ugcId
        $ugcId = null;
        foreach ([$decoded2, $decoded1, $this->shareUrl] as $candidate) {
            if (preg_match('/"ugcId"\s*:\s*"([^"]+)"/i', $candidate, $m)) {
                $ugcId = $m[1];
                break;
            }
            if (preg_match('/ugcId=([a-f0-9]+)/i', $candidate, $m)) {
                $ugcId = $m[1];
                break;
            }
            if (preg_match('/ugcId[^a-f0-9]{0,5}([a-f0-9]{16,})/i', $candidate, $m)) {
                $ugcId = $m[1];
                break;
            }
        }

        if (! $ugcId) {
            $this->setBanner('warning', '未识别到 ugcId，请检查链接格式',
                "支持格式：\n"
                . "  1. https://guinness.autonavi.com/activity/.../index.html?schema=amapuri%3A%2F%2F...\n"
                . "  2. amapuri://ajx_favorites/folder?data={\"ugcId\":\"0661...\"}\n"
                . "  3. 任何包含 ugcId=xxx 的高德分享链接"
            );
            return;
        }

        if (! preg_match('/^[0-9]{18,22}$/', $ugcId)) {
            $this->setBanner('warning', "识别到 ugcId={$ugcId} 但格式不像",
                '高德 ugcId 应是 18-22 位纯数字。可能是解析错了，请用关键字搜索 fallback。');
            return;
        }

        // 解析成功
        $this->lastUgcId = $ugcId;
        session(['amap_last_ugc_id' => $ugcId]);
        $this->keywords = $ugcId;

        $this->setBanner('info', "✓ ugcId 已识别 · ugcId = {$ugcId}",
            "高德收藏夹 web 端需登录态，无法直拉列表。\n"
            . "已自动把 ugcId 填到下方「关键字搜索」，点「搜索 POI」会用高德公开 API 试搜。\n"
            . "如返回空，换成收藏夹里具体的地点名（1-2 个）+ 城市再搜一次即可定位 POI。"
        );

        // 自动触发 search
        $this->search();
    }

    public function importSelected(): void
    {
        if (empty($this->selected)) {
            $this->setBanner('warning', '请至少勾选一个 POI', null);
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
                'place_type' => $this->placeType,
                'poi_source' => 'amap',
                'poi_id' => $poi['id'],
                'poi_type' => $poi['type'],
                'is_public' => false,
                'is_wishlist' => true,
            ]);

            $count++;
        }

        $msg = "已成功导入 {$count} 个";
        if ($skipped > 0) {
            $msg .= "（跳过 {$skipped} 个：已存在或坐标无效）";
        }

        $this->setBanner('success', '导入完成', $msg);

        $this->selected = [];
        $this->search();
    }

    public function toggleAll(bool $checked): void
    {
        $this->selected = $checked ? array_keys($this->results) : [];
    }

    /**
     * 设置状态 banner
     */
    protected function setBanner(string $type, string $title, ?string $body, ?string $extra = null): void
    {
        $this->bannerType = $type;
        $this->bannerTitle = $title;
        $this->bannerBody = $body;
        $this->bannerExtra = $extra;
    }
}
