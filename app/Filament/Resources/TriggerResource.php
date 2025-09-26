<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TriggerResource\Pages;
use App\Filament\Resources\TriggerResource\RelationManagers;
use App\Models\Trigger;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TriggerResource extends Resource
{
    protected static ?string $model = Trigger::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')->required()->label('Trigger Name'),
                Forms\Components\Select::make('event_key')->options(['referral_converted' => 'Referral Converted'])->required(),
                Forms\Components\Select::make('action_type')->options(['grant_points' => 'Grant Points'])->required(),
                Forms\Components\TextInput::make('action_value')->required()->label('Value (e.g., 500)'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('event_key'),
                Tables\Columns\TextColumn::make('action_type'),
                Tables\Columns\TextColumn::make('action_value'),
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTriggers::route('/'),
            'create' => Pages\CreateTrigger::route('/create'),
            'edit' => Pages\EditTrigger::route('/{record}/edit'),
        ];
    }
}
