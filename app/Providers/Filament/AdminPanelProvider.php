<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
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
            // brandLogo 不传（Filament 会找 svg 文件），用 brandName 即可
            // 副标"VOL.01 · 公路杂志"通过 renderHook 注入到 topbar 右侧
            ->colors([
                'primary' => Color::rgb('rgb(17, 75, 95)'),  // #114B5F
            ])
            ->sidebarCollapsibleOnDesktop()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                // FilamentInfoWidget 移除（默认显示 "filament v3.x + GitHub"，AI 味）
            ])
            // 注入 editorial 主题 CSS（必须比 Filament 自身 CSS 后加载）
            ->renderHook(
                'panels::body.end',
                fn (): string => '<link rel="stylesheet" href="' . asset('css/filament-admin.css') . '?v=' . filemtime(public_path('css/filament-admin.css')) . '">',
            )
            ->renderHook(
                'panels::head.end',
                fn (): string => '<link rel="preconnect" href="https://fonts.googleapis.com"><link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,700&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">',
            )
            // 在 topbar 头部注入副标 + 主题切换器
            ->renderHook(
                'panels::topbar.start',
                fn (): string => '<span class="hidden sm:inline font-mono text-[10px] uppercase tracking-[0.2em] text-gray-500 ml-3 border-l border-line pl-3">VOL.01 · 公路杂志</span>',
            )
            // 注入主题切换器（用户菜单之前）
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

