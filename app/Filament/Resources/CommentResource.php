<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CommentResource\Pages;
use App\Models\Comment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * 多态评论管理
 * commentable_type + commentable_id 关联到任意 model (Content/Place/...)
 */
class CommentResource extends Resource
{
    protected static ?string $model = Comment::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = '评论';

    protected static ?string $modelLabel = '评论';

    protected static ?string $pluralModelLabel = '评论';

    protected static ?string $navigationGroup = '社区';

    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Grid::make(2)->schema([
                Forms\Components\Select::make('commentable_type')
                    ->label('对象类型')
                    ->options([
                        'App\\Models\\Content' => '内容 (Content)',
                        'App\\Models\\Place' => '地点 (Place)',
                        'App\\Models\\Activity' => '活动 (Activity)',
                    ])
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn (Forms\Set $set) => $set('commentable_id', null)),

                Forms\Components\TextInput::make('commentable_id')
                    ->label('对象 ID')
                    ->required()
                    ->numeric()
                    ->placeholder('对应对象的主键 ID'),
            ]),

            Forms\Components\Grid::make(2)->schema([
                Forms\Components\Select::make('user_id')
                    ->label('评论人')
                    ->options(fn () => \App\Models\User::orderBy('id')->limit(200)->get()
                        ->mapWithKeys(fn ($u) => [$u->id => $u->name . ($u->email ? ' · ' . $u->email : '')])
                        ->toArray())
                    ->searchable()
                    ->required()
                    ->default(fn () => auth()->id()),

                Forms\Components\Select::make('parent_id')
                    ->label('父评论 ID（回复）')
                    ->options(fn () => Comment::latest()->limit(200)->get()
                        ->mapWithKeys(fn ($c) => [
                            $c->id => '#' . $c->id . ' · ' . mb_substr($c->body, 0, 30),
                        ])
                        ->toArray())
                    ->searchable()
                    ->placeholder('一级评论（不选）'),
            ]),

            Forms\Components\Textarea::make('body')
                ->label('内容')
                ->required()
                ->rows(4)
                ->maxLength(2000)
                ->columnSpanFull(),

            Forms\Components\Grid::make(3)->schema([
                Forms\Components\Select::make('rating_label')
                    ->label('评分（可选）')
                    ->options(collect(\App\Models\Content::RATING_LABELS)
                        ->mapWithKeys(fn ($v, $k) => [$k => $v['label']])->toArray())
                    ->placeholder('不带评分'),
                Forms\Components\TextInput::make('rating_value')
                    ->label('评分值 1-5')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(5),
                Forms\Components\Toggle::make('is_public')
                    ->label('公开')
                    ->default(true)
                    ->inline(false),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->formatStateUsing(fn ($state) => str_pad($state, 2, '0', STR_PAD_LEFT))
                    ->fontFamily('mono')
                    ->width('50px')
                    ->color('gray'),

                Tables\Columns\TextColumn::make('commentable_type')
                    ->label('对象')
                    ->formatStateUsing(function ($state, $record) {
                        $short = match ($state) {
                            'App\\Models\\Content' => '内容',
                            'App\\Models\\Place' => '地点',
                            'App\\Models\\Activity' => '活动',
                            default => class_basename($state),
                        };
                        return $short . ' #' . $record->commentable_id;
                    })
                    ->fontFamily('mono')
                    ->size('xs'),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('作者')
                    ->searchable(),

                Tables\Columns\TextColumn::make('body')
                    ->label('内容')
                    ->limit(60)
                    ->wrap(),

                Tables\Columns\TextColumn::make('rating_label')
                    ->label('评分')
                    ->formatStateUsing(fn ($state) => $state ? (\App\Models\Content::RATING_LABELS[$state]['label'] ?? $state) : '—')
                    ->size('sm')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('parent_id')
                    ->label('回复')
                    ->formatStateUsing(fn ($state) => $state ? '↳ #' . $state : '—')
                    ->fontFamily('mono')
                    ->size('xs')
                    ->color('gray')
                    ->alignCenter(),

                Tables\Columns\IconColumn::make('is_public')
                    ->label('公开')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('时间')
                    ->dateTime('Y-m-d H:i')
                    ->fontFamily('mono')
                    ->size('xs')
                    ->color('gray')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('commentable_type')
                    ->label('对象类型')
                    ->options([
                        'App\\Models\\Content' => '内容',
                        'App\\Models\\Place' => '地点',
                        'App\\Models\\Activity' => '活动',
                    ]),
                Tables\Filters\TernaryFilter::make('is_public')->label('公开'),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()->label('编辑'),
                    Tables\Actions\DeleteAction::make()->label('删除'),
                ])->icon('heroicon-o-ellipsis-horizontal')->iconPosition('after')->label('操作'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListComments::route('/'),
            'create' => Pages\CreateComment::route('/create'),
            'edit' => Pages\EditComment::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getEloquentQuery()->count() ?: null;
    }
}
