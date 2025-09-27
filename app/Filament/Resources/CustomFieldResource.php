<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomFieldResource\Pages;
use App\Models\CustomField;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CustomFieldResource extends Resource
{
    protected static ?string $model = CustomField::class;
    protected static ?string $navigationIcon = 'heroicon-o-cog';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('label')->required(),
                Forms\Components\TextInput::make('key')
                    ->required()
                    ->helperText('Machine-readable key (e.g., favorite_strain).'),
                Forms\Components\Select::make('type')
                    ->options([
                        'text' => 'Text',
                        'date' => 'Date',
                        'dropdown' => 'Dropdown',
                    ])
                    ->required(),
                Forms\Components\KeyValue::make('options')
                    ->helperText('For Dropdown type only.')
                    ->visible(fn ($get) => $get('type') === 'dropdown'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('label')->sortable(),
                Tables\Columns\TextColumn::make('key'),
                Tables\Columns\TextColumn::make('type'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListCustomFields::route('/'),
            'create' => Pages\CreateCustomField::route('/create'),
            'edit' => Pages\EditCustomField::route('/{record}/edit'),
        ];
    }
}