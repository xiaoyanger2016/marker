<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('MARKER')
            // Linear 紫 #5E6AD2 (商务严谨风 — 不再是 editorial 深青)
            ->colors([
                'primary' => Color::rgb('rgb(94, 106, 210)'),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                \App\Filament\Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                // AccountWidget / FilamentInfoWidget 都不要（AI 味）
            ])
            // 注入 Linear 主题 CSS（必须比 Filament 自身 CSS 后加载）
            ->renderHook(
                'panels::body.end',
                fn (): string => '<link rel="stylesheet" href="' . asset('css/filament-admin.css') . '?v=' . filemtime(public_path('css/filament-admin.css')) . '">',
            )
            ->renderHook(
                'panels::head.end',
                fn (): string => '<link rel="preconnect" href="https://fonts.googleapis.com"><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">',
            )
            // 注入 5 主题 SSR 脚本 + .dark class (Linear 商务风：sidebar 永远深色，但 content 主题切换)
            // 必须放在 <head> 渲染前，避免主题闪烁
            ->renderHook(
                'panels::head.end',
                fn (): string => '<script>(function(){try{var t=localStorage.getItem("marker.theme");var ok=["paper","sand","ink","mono","auto"];if(!t||ok.indexOf(t)===-1)t=document.documentElement.getAttribute("data-theme")||"paper";if(ok.indexOf(t)===-1)t="paper";document.documentElement.setAttribute("data-theme",t);var d=t==="ink"||(t==="auto"&&window.matchMedia&&window.matchMedia("(prefers-color-scheme: dark)").matches);if(d)document.documentElement.classList.add("dark");else document.documentElement.classList.remove("dark");}catch(e){}})();</script>',
            )
            // 关键: Filament 的 loadDarkMode() 会在我们的脚本之后再次覆盖 .dark class。
            // 用 panels::body.end 注入一个延迟脚本，等 Filament 处理完再重新设一次 .dark class。
            ->renderHook(
                'panels::body.end',
                fn (): string => '<script>(function(){function fixDark(){try{var t=localStorage.getItem("marker.theme")||document.documentElement.getAttribute("data-theme")||"paper";var d=t==="ink"||(t==="auto"&&window.matchMedia&&window.matchMedia("(prefers-color-scheme: dark)").matches);if(d)document.documentElement.classList.add("dark");else document.documentElement.classList.remove("dark");}catch(e){}}fixDark();document.addEventListener("livewire:navigated",fixDark);})();</script>',
            )
            // Linear 风格 topbar 副标 (无) - 顶部只保留主题切换器 + 用户菜单
            ->renderHook(
                'panels::user-menu.before',
                fn (): string => view('filament.hooks.theme-switcher')->render(),
            )
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}

