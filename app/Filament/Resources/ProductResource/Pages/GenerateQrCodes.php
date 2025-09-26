<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\Page;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GenerateQrCodes extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = ProductResource::class;

    protected static string $view = 'filament.resources.product-resource.pages.generate-qr-codes';

    public $record;

    protected function getRedirectUrl(): ?string
    {
        return $this->getResource()::getUrl('index');
    }

    public static function canAccess(array $parameters = []): bool
    {
        // Allow any authenticated user to access this page
        // In production, you'd have more specific permission checking
        return auth()->check();
    }

    public int $quantity = 10;

    public function mount($record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    protected function resolveRecord($record): mixed
    {
        return static::getModel()::find($record);
    }

    protected function getFormSchema(): array
    {
        return [
            TextInput::make('quantity')
                ->label('Number of QR Codes')
                ->numeric()
                ->minValue(1)
                ->maxValue(1000)
                ->default(10)
                ->required()
                ->helperText('Enter how many QR codes you want to generate (max 1000)')
                ->extraInputAttributes([
                    'class' => 'text-gray-900 dark:text-gray-100',
                    'style' => 'color: black !important;'
                ]),
        ];
    }

    public function generateQrCodes(): void
    {
        $data = $this->form->getState();
        $quantity = (int) $data['quantity'];

        $codes = [];
        for ($i = 0; $i < $quantity; $i++) {
            $code = 'QR-' . strtoupper(Str::random(12));
            $codes[] = [
                'code' => $code,
                'sku' => $this->record->sku,
                'product_id' => $this->record->id, // Include product ID
                'is_used' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Insert the QR codes into the database
        DB::table('reward_codes')->insert($codes);

        // Create a generation session record to store this history
        $session = \App\Models\QrCodeGenerationSession::create([
            'product_id' => $this->record->id,
            'user_id' => auth()->id(), // Store the user who generated the codes
            'quantity_generated' => $quantity,
            'session_identifier' => 'session_' . now()->format('Ymd_His') . '_' . uniqid(),
            'qr_codes' => $codes, // Store codes as JSON array
        ]);

        // Redirect to the download route with the session ID in a separate request after the Livewire action completes
        $this->js("window.location.href = '/admin/download-qr-session/" . $session->id . "'");
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function getTitle(): string|Htmlable
    {
        return 'Generate QR Codes for ' . $this->record->name;
    }
}