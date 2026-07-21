<?php

namespace App\Filament\Resources\PlaceResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class CollectionsRelationManager extends RelationManager
{
    protected static string $relationship = 'collections';

    protected static ?string $title = '所属收藏集';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('id')
                    ->label('收藏集')
                    ->relationship('collections', 'name', fn ($query) => $query->where('user_id', auth()->id()))
                    ->required(),
                Forms\Components\TextInput::make('pivot.sort')->numeric()->default(0),
                Forms\Components\Textarea::make('pivot.note')->rows(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('收藏集')
                    ->searchable(),

                Tables\Columns\TextColumn::make('pivot.sort')
                    ->label('排序')
                    ->numeric(),

                Tables\Columns\TextColumn::make('pivot.note')
                    ->label('备注')
                    ->limit(50),

                Tables\Columns\TextColumn::make('is_public')
                    ->label('公开')
                    ->formatStateUsing(fn ($state) => $state ? '是' : '否')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'gray'),
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
