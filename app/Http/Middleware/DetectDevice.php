<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 设备检测 - 把 is_mobile 放到 view 共享
 *  - 默认用 UA 判断
 *  - ?desktop=1 强制 PC，?mobile=1 强制 MWeb（方便调试）
 */
class DetectDevice
{
    public function handle(Request $request, Closure $next): Response
    {
        $force = $request->query('view');
        if ($force === 'desktop') {
            $isMobile = false;
        } elseif ($force === 'mobile') {
            $isMobile = true;
        } else {
            $ua = $request->userAgent() ?? '';
            $isMobile = (bool) preg_match('/Mobile|Android|iPhone|iPad|iPod|Opera Mini|IEMobile/i', $ua);
        }

        // 给所有 view 共享
        view()->share('isMobile', $isMobile);
        view()->share('isPC', ! $isMobile);

        return $next($request);
    }
}
