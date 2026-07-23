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
                $infocode = $data['infocode'] ?? '';
                $info = $data['info'] ?? '未知';
                $hint = $this->amapInfocodeHint($infocode);
                $this->setBanner('danger', "高德 API 返回错误 · {$info}", $hint);
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

            if ($count === 0) {
                // 0 结果：明确告诉用户下一步
                $hint = '';
                if ($this->lastUgcId && $this->keywords === $this->lastUgcId) {
                    $hint = "（你刚解析的 ugcId=" . $this->lastUgcId . " — 高德公开 API 搜不到纯数字 ID，\n请换成收藏夹里具体的地点名 + 城市再搜。）";
                } else {
                    $hint = "试试：1) 加城市限定 · 2) 用更具体的地点名 · 3) 改用英文 / 全称 · 4) 减少关键字数。";
                }
                $this->setBanner('warning', "步骤 2/3 · 搜不到「{$this->keywords}」相关 POI", $hint);
            } else {
                $bannerBody = "步骤 3/3 · 搜到 {$count} 个 POI · 在下方勾选要导入的 → 顶部选 8 大类归类 → 「导入所选」一键加入你的位置库。";
                $this->setBanner('success', "✓ 搜索完成 · 「{$this->keywords}」· {$count} 个结果", $bannerBody);
            }
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
        $this->keywords = '';  // 不要用 ugcId 当 keyword (数字搜不到)
        $this->searched = false;
        $this->results = [];
        $this->selected = [];

        $this->setBanner('info', "✓ 步骤 1/3 · ugcId 已识别 · ugcId = {$ugcId}",
            "⚠ 高德收藏夹 web 端需要登录态，没开放直拉列表 API。\n"
            . "接下来：在下方「关键字搜索」输入收藏夹里 1-2 个最典型的地点名 + 城市 → 点「搜索 POI」→ 勾选要导入的 → 一键批量加入你的位置库。\n"
            . "（你也可以跳过 ugcId 步骤，直接在下方输地点名 + 城市搜）"
        );

        // UX：解析成功后自动滚到 B 区 + 聚焦关键词输入框
        $this->dispatch('amap-parsed', focusSelector: '[data-amap-keywords-input]');
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

    /**
     * 高德 infocode → 详细错误提示
     * 复用 PlaceService::geocodeFromAddress 的提示逻辑
     */
    protected function amapInfocodeHint(string $infocode): string
    {
        $hints = [
            '10001' => "❌ INVALID_USER_KEY\nKey 不存在或已禁用 → 去 lbs.amap.com 控制台「应用管理」查 Key 状态",
            '10003' => "❌ 访问频次超限 / IP 白名单 / 域名白名单 拒绝\n本机测试把 127.0.0.1 + localhost 加到 Web 服务 Key 的白名单",
            '10004' => "❌ Key 未启用 Web 服务平台\n进控制台 → 应用 → Key 设置 → 服务平台勾选「Web 服务」",
            '10005' => "❌ Key 与请求平台不匹配\n如果是服务端调用 (而不是浏览器 JS) → 改用 Web 服务 Key，不是 JS API Key",
            '10006' => "❌ 配额超限（个人账户 2000 次/日）\n明日 0 点重置，或申请企业认证提额",
            '10007' => "❌ 引用已被使用 / 签名错误\n检查 key 是否被复制错位",
            '10008' => "❌ IP / 域名白名单未通过\n本机测试用 127.0.0.1，部署后用真实域名",
            '10009' => "❌ 私钥未配置 HTTPS\nWeb 服务 Key 必须配 TLS",
            '10010' => "❌ IP 备案未通过\n国内 Key 需要服务器 IP 备案",
            '10011' => "❌ 余额不足\n个人开发者基本不会遇到",
            '10012' => "❌ 超出 Key 配额\nWeb 服务 Key 个人 2000/日",
            '10013' => "❌ Key 被冻结\n违规或被举报，去控制台解封",
            '10014' => "❌ Key 已删除\n重新创建一个",
            '10020' => "❌ 服务端异常\n高德服务临时挂了，过会儿重试",
            '20000' => "❌ 请求参数非法\nkeywords/city 有非法字符",
            '30000' => "❌ 未知错误\n重试，或联系高德客服",
        ];
        return $hints[$infocode] ?? ("infocode={$infocode}，参考：https://lbs.amap.com/api/webservice/guide/tools/info");
    }
}
