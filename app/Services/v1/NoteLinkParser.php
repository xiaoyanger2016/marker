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
     *   warning: ?string
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
        }

        // 2. 试 fetch og tags (timeout 5s, 不致命)
        $og = $this->fetchOg($url);
        $result['external_meta']['og'] = $og;
        if (! empty($og['title'])) {
            $result['title'] = $this->cleanText($og['title']);
        }
        if (! empty($og['image'])) {
            $result['cover_url'] = $og['image'];
        }
        if (! empty($og['description'])) {
            $result['content'] = $this->cleanText($og['description']);
        }
        if (! empty($og['site_name']) && ! $result['author']) {
            $result['author'] = $this->cleanText($og['site_name']);
        }

        $result['success'] = true;
        if (! $externalId) {
            $result['warning'] = "未提取到 {$cfg['name']} 笔记 ID（已尽力解析 OG meta）";
        }
        return $result;
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
