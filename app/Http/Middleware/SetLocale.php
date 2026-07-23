<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * 语言切换 - 优先级：
 *  1. ?lang=xx query 参数（调试 / 分享链接用）
 *  2. session('locale')
 *  3. Accept-Language 头
 *  4. config('app.locale') 默认
 */
class SetLocale
{
    public const SUPPORTED = ['zh-CN', 'zh-TW', 'en', 'ja', 'ko'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->query('lang')
            ?? $request->session()->get('locale')
            ?? $this->fromAcceptLanguage($request)
            ?? config('app.locale');

        if (! in_array($locale, self::SUPPORTED, true)) {
            $locale = config('app.locale');
        }

        App::setLocale($locale);
        // Phase 20: 短横 zh-CN/zh-TW 给 App 用，下划线版给 Filament vendor 翻译用
        $underscore = str_replace('-', '_', $locale);
        if ($underscore !== $locale) {
            \Illuminate\Support\Facades\Lang::setLocale($underscore);
        }

        $request->session()->put('locale', $locale);
        view()->share('locale', $locale);
        view()->share('availableLocales', self::SUPPORTED);

        // 主题（从 session / query 读，让切换结果跨页面保留；支持 ?theme=paper/sand/ink/mono/auto）
        $theme = $request->query('theme')
            ?? $request->session()->get('theme')
            ?? 'paper';
        if (! in_array($theme, ['paper', 'sand', 'ink', 'mono', 'auto'], true)) {
            $theme = 'paper';
        }
        if ($request->query('theme')) {
            $request->session()->put('theme', $theme);
        }
        view()->share('theme', $theme);
        view()->share('availableThemes', ['paper', 'sand', 'ink', 'mono', 'auto']);

        return $next($request);
    }

    private function fromAcceptLanguage(Request $request): ?string
    {
        $al = $request->header('Accept-Language');
        if (! $al) {
            return null;
        }
        // 简单匹配
        foreach (self::SUPPORTED as $code) {
            if (stripos($al, $code) !== false) {
                return $code;
            }
        }
        return null;
    }
}
