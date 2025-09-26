<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AchievementResource\Pages;
use App\Filament\Resources\AchievementResource\RelationManagers;
use App\Models\Achievement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AchievementResource extends Resource
{
    protected static ?string $model = Achievement::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('achievement_key')->required()->label('Key (e.g., first_scan)'),
                Forms\Components\TextInput::make('title')->required(),
                Forms\Components\Textarea::make('description')->columnSpanFull(),
                Forms\Components\TextInput::make('points_reward')->numeric()->default(0),
                Forms\Components\Select::make('rarity')->options(['common' => 'Common', 'uncommon' => 'Uncommon', 'rare' => 'Rare'])->required(),
                Forms\Components\Select::make('trigger_event')->options(['first_product_scanned' => 'First Scanned', 'standard_product_scanned' => 'Standard Scanned'])->required(),
                Forms\Components\TextInput::make('trigger_count')->numeric()->default(1)->required(),
                Forms\Components\Toggle::make('is_active')->default(true),
                Forms\Components\Textarea::make('conditions')->label('Conditions (JSON)')->columnSpanFull()->nullable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('achievement_key'),
                Tables\Columns\TextColumn::make('trigger_event'),
                Tables\Columns\TextColumn::make('points_reward')->sortable(),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
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
            'index' => Pages\ListAchievements::route('/'),
            'create' => Pages\CreateAchievement::route('/create'),
            'edit' => Pages\EditAchievement::route('/{record}/edit'),
        ];
    }
}
