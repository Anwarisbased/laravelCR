<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use App\Services\ReferralService;
use Illuminate\Support\Str;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('User Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')->required(),
                        Forms\Components\TextInput::make('email')->email()->required()->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create'),
                        Forms\Components\Toggle::make('is_admin')->label('Is Administrator'),
                    ])->columns(2),
                
                Forms\Components\Section::make('Rewards Data')
                    ->schema([
                        Forms\Components\TextInput::make('meta._canna_points_balance')
                            ->label('Points Balance')
                            ->numeric()
                            ->default(0),
                        Forms\Components\TextInput::make('meta._canna_lifetime_points')
                            ->label('Lifetime Points')
                            ->numeric()
                            ->default(0),
                        Forms\Components\TextInput::make('meta._canna_current_rank_key')
                            ->label('Current Rank Key (e.g., member, gold)'),
                        Forms\Components\TextInput::make('meta._canna_referral_code')
                            ->label('Referral Code')
                            ->helperText('Auto-generated for the user')
                            ->readOnly(),
                    ])->columns(2),
                
                // We will add Custom Fields here in the next step
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('email')->searchable(),
                Tables\Columns\TextColumn::make('meta._canna_points_balance')->label('Points')->sortable(),
                Tables\Columns\TextColumn::make('meta._canna_current_rank_key')->label('Rank'),
                Tables\Columns\IconColumn::make('is_admin')->boolean(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
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

    public static function mutateFormDataBeforeCreate(array $data): array
    {
        // If no referral code exists, we'll let the system generate one after creation
        if (empty($data['meta']['_canna_referral_code'])) {
            unset($data['meta']['_canna_referral_code']);
        }
        
        return $data;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}