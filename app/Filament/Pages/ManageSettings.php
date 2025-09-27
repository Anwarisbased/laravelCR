<?php

namespace App\Filament\Pages;

use App\Settings\GeneralSettings;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\SettingsPage;
use App\Models\Product; // <-- Import Product model

class ManageSettings extends SettingsPage
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string $settings = GeneralSettings::class;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('General Brand Configuration')
                    ->schema([
                        Forms\Components\TextInput::make('frontendUrl')
                            ->label('PWA Frontend URL')
                            ->required()
                            ->url()
                            ->helperText('The base URL of your PWA for password resets and QR code links.'),
                        Forms\Components\TextInput::make('supportEmail')
                            ->label('Support Email Address')
                            ->required()
                            ->email(),
                        Forms\Components\Select::make('welcomeRewardProductId')
                            ->label('First Scan Reward Product')
                            ->options(Product::all()->pluck('name', 'id'))
                            ->searchable()
                            ->nullable()
                            ->helperText("Select the product offered for a user's first scan."),
                        Forms\Components\Select::make('referralSignupGiftId')
                            ->label('Referral Sign-up Gift')
                            ->options(Product::all()->pluck('name', 'id'))
                            ->searchable()
                            ->nullable()
                            ->helperText('Select the gift for new users who sign up via referral.'),
                        Forms\Components\TextInput::make('referralBannerText')
                            ->label('Referral Banner Text'),
                    ]),

                Forms\Components\Section::make('Brand Personality Engine')
                    ->description('Define the core language and feel of your rewards program.')
                    ->schema([
                        Forms\Components\TextInput::make('pointsName')
                            ->label('Name for "Points"')
                            ->required(),
                        Forms\Components\TextInput::make('rankName')
                            ->label('Name for "Rank"')
                            ->required(),
                        Forms\Components\TextInput::make('welcomeHeaderText')
                            ->label('Welcome Header Text')
                            ->helperText('Use {firstName} as a placeholder.'),
                        Forms\Components\TextInput::make('scanButtonCta')
                            ->label('Scan Button CTA'),
                    ]),
            ]);
    }
    
    public function save(): void
    {
        // Get the form data
        $formData = $this->form->getState();
        
        // Get the settings instance to access group info
        $settingsInstance = app(static::$settings);
        
        // Get current settings values to preserve non-form fields
        $currentData = $settingsInstance->toArray();
        
        // Merge form data with current data
        $mergedData = array_merge($currentData, $formData);
        
        // Get the repository and update the properties payload directly
        $repository = $settingsInstance->getRepository();
        
        // Update the properties for this settings class's group
        $repository->updatePropertiesPayload(
            $settingsInstance->group(),
            [static::$settings => $mergedData]
        );
        
        $this->getSavedNotification()?->send();
    }
}
