<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = '系统';

    protected static ?string $navigationLabel = '用户管理';

    protected static ?string $modelLabel = '用户';

    protected static ?string $pluralModelLabel = '用户';

    protected static ?int $navigationSort = 90;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('基础信息')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('姓名')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->label('邮箱')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        Forms\Components\TextInput::make('password')
                            ->label('密码')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->minLength(8)
                            ->maxLength(255)
                            ->placeholder('留空 = 不修改')
                            ->helperText('仅在创建或重置时填写'),
                    ])->columns(2),

                Forms\Components\Section::make('权限')
                    ->schema([
                        Forms\Components\Select::make('role')
                            ->label('角色')
                            ->options(User::ROLES)
                            ->default('user')
                            ->required()
                            ->live(),

                        Forms\Components\Toggle::make('is_admin')
                            ->label('管理员')
                            ->helperText('管理员可访问全部后台')
                            ->default(false),

                        Forms\Components\Placeholder::make('role_hint')
                            ->label('权限说明')
                            ->content(fn ($get) => match ($get('role')) {
                                'admin' => '✓ 访问全部后台 / 编辑任何内容 / 管理用户',
                                'editor' => '✓ 编辑内容/活动/评论 · ✗ 管理用户',
                                'user' => '✓ 浏览/创建自己的内容',
                                default => '—',
                            }),
                    ])->columns(2),

                Forms\Components\Section::make('资料')
                    ->schema([
                        Forms\Components\TextInput::make('avatar')
                            ->label('头像 URL')
                            ->maxLength(255)
                            ->placeholder('https://...'),
                        Forms\Components\Textarea::make('bio')
                            ->label('简介')
                            ->rows(2)
                            ->maxLength(500),
                    ])->columns(2),

                Forms\Components\Section::make('状态')
                    ->schema([
                        Forms\Components\Placeholder::make('created_at')
                            ->label('注册时间')
                            ->content(fn ($record) => $record?->created_at?->format('Y-m-d H:i') ?? '—'),
                        Forms\Components\Placeholder::make('last_login_at')
                            ->label('最后登录')
                            ->content(fn ($record) => $record?->last_login_at?->diffForHumans() ?? '从未'),
                        Forms\Components\Placeholder::make('last_login_ip')
                            ->label('登录 IP')
                            ->content(fn ($record) => $record?->last_login_ip ?? '—'),
                    ])->columns(3)
                    ->visible(fn (string $context) => $context === 'edit'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->label('头像')
                    ->circular()
                    ->defaultImageUrl(fn ($record) => $record?->getFilamentAvatarUrl())
                    ->size(36),

                Tables\Columns\TextColumn::make('name')
                    ->label('姓名')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('email')
                    ->label('邮箱')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-o-envelope')
                    ->iconColor('gray'),

                Tables\Columns\TextColumn::make('role')
                    ->label('角色')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'admin' => 'danger',
                        'editor' => 'warning',
                        'user' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => User::ROLES[$state] ?? $state)
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_admin')
                    ->label('管理员')
                    ->boolean()
                    ->trueIcon('heroicon-o-shield-check')
                    ->falseIcon('heroicon-o-shield-exclamation')
                    ->trueColor('danger'),

                Tables\Columns\TextColumn::make('contents_count')
                    ->label('内容')
                    ->counts('contents')
                    ->badge()
                    ->color('info')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('places_count')
                    ->label('地点')
                    ->counts('places')
                    ->badge()
                    ->color('gray')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('last_login_at')
                    ->label('最后登录')
                    ->since()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('注册')
                    ->date('Y-m-d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label('角色')
                    ->options(User::ROLES),
                Tables\Filters\TernaryFilter::make('is_admin')
                    ->label('管理员'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('impersonate')
                    ->label('模拟登录')
                    ->icon('heroicon-o-finger-print')
                    ->color('warning')
                    ->visible(fn () => auth()->user()?->isAdmin())
                    ->requiresConfirmation()
                    ->action(function (User $record) {
                        auth()->login($record);
                        return redirect('/me');
                    }),
                Tables\Actions\Action::make('toggle_admin')
                    ->label(fn ($record) => $record->is_admin ? '取消管理员' : '设为管理员')
                    ->icon(fn ($record) => $record->is_admin ? 'heroicon-o-shield-exclamation' : 'heroicon-o-shield-check')
                    ->color(fn ($record) => $record->is_admin ? 'gray' : 'danger')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->id !== auth()->id())
                    ->action(fn (User $record) => $record->update(['is_admin' => !$record->is_admin, 'role' => !$record->is_admin ? 'admin' : 'user'])),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn ($record) => $record->id !== auth()->id()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('make_admin')
                        ->label('设为管理员')
                        ->icon('heroicon-o-shield-check')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['is_admin' => true, 'role' => 'admin'])),
                    Tables\Actions\BulkAction::make('remove_admin')
                        ->label('取消管理员')
                        ->icon('heroicon-o-shield-exclamation')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['is_admin' => false, 'role' => 'user'])),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $n = static::getModel()::count();
        return $n > 0 ? (string) $n : null;
    }
}
