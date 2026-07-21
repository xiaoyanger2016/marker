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
        $request->session()->put('locale', $locale);
        view()->share('locale', $locale);
        view()->share('availableLocales', self::SUPPORTED);

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
