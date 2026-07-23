<?php

namespace App\Services\v1;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * 笔记链接解析器 (Phase 19)
 *
 * 识别外部平台 (小红书 / 大众点评 / 马蜂窝) 链接：
 *  - 提取平台 + 笔记 ID
 *  - 试 fetch Open Graph meta tags 拿 title / cover / description
 *  - 返回可填入 notes 表的字段数组
 *
 * 失败也不会抛异常 —— 至少返回 source + source_url + 自定义占位 title
 */
class NoteLinkParser
{
    /** 平台识别规则 */
    public const PLATFORMS = [
        'xiaohongshu' => [
            'host_match' => ['xiaohongshu.com', 'xhslink.com', 'xhs.cn'],
            'id_pattern' => '#/(?:explore|discovery|search-result|note-detail)/([a-f0-9]+)#i',
            'id_fallback' => '/([a-f0-9]{20,})/i',
            'name' => '小红书',
        ],
        'dianping' => [
            'host_match' => ['dianping.com', 'dping.cn'],
            'id_pattern' => '/(?:shop|note|review)/l([0-9]+)/?$/i',
            'id_fallback' => '/l([0-9]+)\b/i',
            'name' => '大众点评',
        ],
        'mafengwo' => [
            'host_match' => ['mafengwo.com', 'mafengwo.cn'],
            'id_pattern' => '/i/([0-9]+)\.html',
            'id_fallback' => '/i/([0-9]+)\.html',
            'name' => '马蜂窝',
        ],
    ];

    /**
     * 解析 URL → notes 表字段
     *
     * @return array{
     *   source: string,
     *   source_url: string,
     *   title: string,
     *   author: ?string,
     *   cover_url: ?string,
     *   content: ?string,
     *   xhs_note_id: ?string,
     *   xhs_xsec_token: ?string,
     *   published_at: ?\Carbon\Carbon,
     *   external_meta: array,
     *   success: bool,
     *   warning: ?string,
     *   needs_manual: bool,    // 哪些字段需要用户手动补全
     *   fillable: array        // 实际可填的字段 (UI 友好提示)
     * }
     */
    public function parse(string $url): array
    {
        $url = trim($url);
        $result = [
            'source'        => 'manual',
            'source_url'    => $url,
            'title'         => '未命名笔记',
            'author'        => null,
            'cover_url'     => null,
            'content'       => null,
            'xhs_note_id'   => null,
            'xhs_xsec_token' => null,
            'published_at'  => null,
            'external_meta' => [],
            'success'       => false,
            'warning'       => null,
            'needs_manual'  => false,
            'fillable'      => ['source_url', 'source'],
        ];

        if (! $url) {
            $result['warning'] = 'URL 为空';
            return $result;
        }

        $host = parse_url($url, PHP_URL_HOST) ?? '';
        $platform = null;
        foreach (self::PLATFORMS as $key => $cfg) {
            foreach ($cfg['host_match'] as $m) {
                if (Str::contains($host, $m)) {
                    $platform = $key;
                    break 2;
                }
            }
        }

        if (! $platform) {
            $result['warning'] = '未识别的平台: ' . $host;
            return $result;
        }

        $cfg = self::PLATFORMS[$platform];
        $result['source'] = $platform;
        $result['fillable'][] = 'source';

        // 1. 提外部 ID
        $externalId = null;
        if (preg_match($cfg['id_pattern'], $url, $m)) {
            $externalId = $m[1];
        } elseif (! empty($cfg['id_fallback']) && preg_match($cfg['id_fallback'], $url, $m)) {
            $externalId = $m[1];
        }
        $result['external_meta']['external_id'] = $externalId;

        // 小红书专用：xsec_token
        if ($platform === 'xiaohongshu') {
            $result['xhs_note_id'] = $externalId;
            $query = parse_url($url, PHP_URL_QUERY) ?? '';
            parse_str($query, $params);
            $result['xhs_xsec_token'] = $params['xsec_token'] ?? null;
            $result['fillable'][] = 'xhs_note_id';
            $result['fillable'][] = 'xhs_xsec_token';
        }

        // 2. 试 fetch og tags (timeout 5s, 不致命)
        $og = $this->fetchOg($url);
        $result['external_meta']['og'] = $og;
        $ogHit = false;
        if (! empty($og['title']) && ! $this->looksLikeAntiBot($og['title'], $platform)) {
            $result['title'] = $this->cleanText($og['title']);
            $result['fillable'][] = 'title';
            $ogHit = true;
        }
        if (! empty($og['image']) && ! $this->looksLikeAntiBot($og['image'], $platform)) {
            $result['cover_url'] = $og['image'];
            $result['fillable'][] = 'cover_url';
            $ogHit = true;
        }
        if (! empty($og['description']) && ! $this->looksLikeAntiBot($og['description'], $platform)) {
            $result['content'] = $this->cleanText($og['description']);
            $result['fillable'][] = 'content';
            $ogHit = true;
        }
        if (! empty($og['site_name']) && ! $result['author']) {
            $result['author'] = $this->cleanText($og['site_name']);
            $result['fillable'][] = 'author';
        }

        $result['success'] = true;
        $result['needs_manual'] = ! $ogHit;

        // Phase 20：anti-bot 平台给明确提示
        $autoFilled = array_diff($result['fillable'], ['source_url', 'source']);
        if ($result['needs_manual']) {
            $platformName = $cfg['name'];
            $manualFields = array_values(array_diff(['title', 'cover_url', 'content', 'author'], $autoFilled));
            $result['warning'] = "✓ 已识别「{$platformName}」笔记 ID，但标题/封面/正文需手动补全。\n"
                . "原因：{$platformName} web 端对非登录用户 anti-bot，不返回 og meta。\n"
                . "建议：从小红书 APP 复制笔记文本 + 截图，手动粘贴到下方对应字段。\n"
                . "已自动填写：" . implode(' / ', array_merge(['source_url', 'source'], $result['fillable'] ? array_diff($result['fillable'], ['source_url', 'source']) : []));
        } else {
            if (! $externalId) {
                $result['warning'] = "未提取到 {$cfg['name']} 笔记 ID（已尽力解析 OG meta）";
            }
        }
        return $result;
    }

    /**
     * 检测 anti-bot 平台返回的占位/空内容
     *  - 小红书 web 端返: "小红书" / "当前内容仅支持在小红书 APP 内查看"
     *  - 大众点评返: 通用首页 title
     *  - 马蜂窝返: 空 HTML
     */
    protected function looksLikeAntiBot(string $text, string $platform): bool
    {
        $lower = mb_strtolower($text);
        $signals = [
            'xiaohongshu' => ['小红书', 'xhs', '当前内容仅支持', 'app 内查看', '打开 app', '打开小程序'],
            'dianping'    => ['美食, 餐厅餐饮', '大众点评网', '大众点评, 本地'],
            'mafengwo'    => [],
        ];
        foreach ($signals[$platform] ?? [] as $sig) {
            if (Str::contains($lower, $sig)) return true;
        }
        return false;
    }

    /**
     * 抓 og:title / og:image / og:description / og:site_name
     * 5s 超时；失败返空数组
     */
    protected function fetchOg(string $url): array
    {
        try {
            $resp = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.0 Mobile/15E148 Safari/604.1',
                'Accept' => 'text/html,application/xhtml+xml',
                'Accept-Language' => 'zh-CN,zh;q=0.9,en;q=0.8',
            ])->timeout(5)->get($url);
            if (! $resp->ok()) return [];
            $html = $resp->body();
            return [
                'title'       => $this->extractMeta($html, 'og:title') ?? $this->extractMeta($html, 'twitter:title'),
                'image'       => $this->extractMeta($html, 'og:image') ?? $this->extractMeta($html, 'twitter:image'),
                'description' => $this->extractMeta($html, 'og:description') ?? $this->extractMeta($html, 'description') ?? $this->extractMeta($html, 'twitter:description'),
                'site_name'   => $this->extractMeta($html, 'og:site_name'),
            ];
        } catch (ConnectionException|\Throwable $e) {
            return [];
        }
    }

    protected function extractMeta(string $html, string $property): ?string
    {
        // 兼容 property="og:title" / name="description" 两种
        $pattern = '/<meta\s+(?:[^>]*?\s+)?(?:property|name)\s*=\s*["\']' . preg_quote($property, '/') . '["\']\s+content\s*=\s*["\']([^"\']*)["\']/i';
        if (preg_match($pattern, $html, $m)) {
            return html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        // 反序 property 在前
        $pattern2 = '/<meta\s+(?:[^>]*?\s+)?content\s*=\s*["\']([^"\']*)["\']\s+(?:property|name)\s*=\s*["\']' . preg_quote($property, '/') . '["\']/i';
        if (preg_match($pattern2, $html, $m)) {
            return html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        // twitter:title / description 可能直接 name=
        $pattern3 = '/<meta\s+name\s*=\s*["\']' . preg_quote($property, '/') . '["\']\s+content\s*=\s*["\']([^"\']*)["\']/i';
        if (preg_match($pattern3, $html, $m)) {
            return html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        return null;
    }

    protected function cleanText(string $s): string
    {
        $s = trim($s);
        // 去 "- 小红书" / "- 大众点评" 等尾巴
        $s = preg_replace('/\s*[-—–]\s*(小红书|大众点评|马蜂窝|MAFENGWO|DIANPING|XIAOHONGSHU).*$/iu', '', $s);
        return Str::limit($s, 200, '');
    }
}
