<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CollectionResource\Pages;
use App\Filament\Resources\CollectionResource\RelationManagers;
use App\Models\Collection;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CollectionResource extends Resource
{
    protected static ?string $model = Collection::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder';

    protected static ?string $navigationLabel = '收藏集';

    protected static ?string $modelLabel = '收藏集';

    protected static ?int $navigationSort = 15;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('user_id')
                    ->default(fn () => auth()->id()),

                Forms\Components\TextInput::make('name')
                    ->label('收藏集名称')
                    ->required()
                    ->maxLength(200)
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('description')
                    ->label('描述')
                    ->rows(3)
                    ->columnSpanFull(),

                Forms\Components\Grid::make(3)->schema([
                    Forms\Components\TextInput::make('sort')
                        ->label('排序')
                        ->numeric()
                        ->default(0),
                    Forms\Components\Toggle::make('is_public')
                        ->label('公开分享')
                        ->default(false)
                        ->live(),
                ]),

                Forms\Components\Section::make('分享设置')
                    ->visible(fn (Forms\Get $get) => $get('is_public'))
                    ->schema([
                        Forms\Components\TextInput::make('share_password')
                            ->label('访问密码（可选）')
                            ->placeholder('留空则免密码')
                            ->password()
                            ->revealable(),

                        Forms\Components\DateTimePicker::make('share_expires_at')
                            ->label('过期时间（可选）')
                            ->native(false)
                            ->minDate(now()),

                        Forms\Components\Placeholder::make('share_url')
                            ->label('分享链接')
                            ->content(fn (?Collection $record) => $record?->share_url ?: '保存后生成'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('名称')
                    ->searchable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('description')
                    ->label('描述')
                    ->limit(50),
                Tables\Columns\TextColumn::make('places_count')
                    ->counts('places')
                    ->label('地点数')
                    ->badge(),
                Tables\Columns\IconColumn::make('is_public')
                    ->label('公开')
                    ->boolean(),
                Tables\Columns\TextColumn::make('share_view_count')
                    ->label('浏览')
                    ->numeric()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('share_url')
                    ->label('分享链接')
                    ->copyable()
                    ->limit(40)
                    ->toggleable(),
            ])
            ->actions([
                Tables\Actions\Action::make('copyShareLink')
                    ->label('复制分享')
                    ->icon('heroicon-o-link')
                    ->visible(fn ($record) => $record->is_public)
                    ->action(function ($record) {
                        \Filament\Notifications\Notification::make()
                            ->title('分享链接已复制')
                            ->body($record->share_url)
                            ->success()
                            ->send();
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PlacesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCollections::route('/'),
            'create' => Pages\CreateCollection::route('/create'),
            'edit' => Pages\EditCollection::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }
}
