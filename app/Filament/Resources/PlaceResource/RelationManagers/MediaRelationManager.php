<?php

namespace App\Filament\Resources\PlaceResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class MediaRelationManager extends RelationManager
{
    protected static string $relationship = 'media';

    protected static ?string $title = '媒体（图片/视频）';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('user_id')
                    ->default(fn () => auth()->id()),

                Forms\Components\Select::make('type')
                    ->label('类型')
                    ->options([
                        'image' => '图片',
                        'video' => '视频',
                    ])
                    ->required()
                    ->default('image')
                    ->live()
                    ->afterStateUpdated(fn (Forms\Set $set) => $set('path', null)),

                Forms\Components\FileUpload::make('path')
                    ->label('文件')
                    ->required()
                    ->disk('public')
                    ->directory(fn (Forms\Get $get) => 'places/' . $this->getOwnerRecord()->id . '/' . $get('type') . 's')
                    ->image(fn (Forms\Get $get) => $get('type') === 'image')
                    ->acceptedFileTypes(fn (Forms\Get $get) => $get('type') === 'image'
                        ? ['image/jpeg', 'image/png', 'image/webp', 'image/gif']
                        : ['video/mp4', 'video/quicktime', 'video/webm']
                    )
                    ->maxSize(51200) // 50MB
                    ->columnSpanFull()
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        if (! $state) {
                            return;
                        }
                        $first = is_array($state) ? ($state[0] ?? null) : $state;
                        if (! $first) {
                            return;
                        }
                        // 写 size
                        try {
                            $size = \Storage::disk('public')->size($first);
                            $set('size', $size);
                            $set('mime', \Storage::disk('public')->mimeType($first));
                            // 图片读尺寸
                            if (str_starts_with(\Storage::disk('public')->mimeType($first) ?? '', 'image/')) {
                                [$w, $h] = getimagesize(\Storage::disk('public')->path($first));
                                $set('width', $w);
                                $set('height', $h);
                            }
                        } catch (\Throwable $e) {
                            // ignore
                        }
                    }),

                Forms\Components\Grid::make(3)->schema([
                    Forms\Components\TextInput::make('width')->label('宽(px)')->numeric()->disabled(),
                    Forms\Components\TextInput::make('height')->label('高(px)')->numeric()->disabled(),
                    Forms\Components\TextInput::make('size')->label('大小(字节)')->numeric()->disabled(),
                ]),

                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\TextInput::make('title')->label('标题')->maxLength(255),
                    Forms\Components\TextInput::make('duration')->label('时长(秒,仅视频)')->numeric(),
                ]),

                Forms\Components\Textarea::make('caption')->label('说明')->rows(2)->columnSpanFull(),

                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\Toggle::make('is_cover')->label('设为封面'),
                    Forms\Components\TextInput::make('sort')->label('排序')->numeric()->default(0),
                ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->defaultSort('sort', 'asc')
            ->columns([
                Tables\Columns\ImageColumn::make('path')
                    ->label('预览')
                    ->disk('public')
                    ->height(60)
                    ->width(60)
                    ->extraImgAttributes(['style' => 'object-fit: cover; border-radius: 4px;']),

                Tables\Columns\TextColumn::make('type')
                    ->label('类型')
                    ->badge()
                    ->color(fn ($state) => $state === 'image' ? 'success' : 'warning')
                    ->formatStateUsing(fn ($state) => $state === 'image' ? '图片' : '视频'),

                Tables\Columns\TextColumn::make('title')->label('标题')->searchable(),
                Tables\Columns\TextColumn::make('size')->label('大小')->formatStateUsing(fn ($state) => $state ? round($state / 1024, 1) . ' KB' : '-'),
                Tables\Columns\TextColumn::make('width')->label('尺寸')->formatStateUsing(fn ($record) => $record->width ? "{$record->width}×{$record->height}" : '-'),
                Tables\Columns\TextColumn::make('duration')->label('时长(秒)'),
                Tables\Columns\IconColumn::make('is_cover')->label('封面')->boolean(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['user_id'] = auth()->id();
                        $data['disk'] = 'public';
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
