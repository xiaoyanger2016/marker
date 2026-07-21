<?php

namespace App\Filament\Resources\CollectionResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PlacesRelationManager extends RelationManager
{
    protected static string $relationship = 'places';

    protected static ?string $title = '收藏的地点';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')->required(),
                Forms\Components\TextInput::make('pivot.sort')->numeric()->default(0),
                Forms\Components\Textarea::make('pivot.note')->rows(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultSort('collection_place.sort', 'asc')
            ->columns([
                Tables\Columns\ImageColumn::make('media.path')
                    ->label('封面')
                    ->disk('public')
                    ->height(40)
                    ->width(40)
                    ->defaultImageUrl(url('/images/placeholder.png')),

                Tables\Columns\TextColumn::make('name')
                    ->label('名称')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('分类')
                    ->badge(),

                Tables\Columns\TextColumn::make('city')->label('城市'),

                Tables\Columns\TextColumn::make('pivot.sort')->label('排序')->numeric(),

                Tables\Columns\TextColumn::make('pivot.note')
                    ->label('备注')
                    ->limit(40),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->preloadRecordSelect()
                    ->recordSelectOptionsQuery(fn ($query) => $query->where('user_id', auth()->id())),
            ])
            ->actions([
                Tables\Actions\DetachAction::make(),
                Tables\Actions\EditAction::make()
                    ->using(function ($record, array $data) {
                        $record->pivot->update([
                            'sort' => $data['pivot']['sort'] ?? 0,
                            'note' => $data['pivot']['note'] ?? null,
                        ]);
                        return $record;
                    }),
            ]);
    }
}
