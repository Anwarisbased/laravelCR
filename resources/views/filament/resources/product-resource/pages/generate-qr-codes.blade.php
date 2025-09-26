<div class="space-y-6">
    <div class="p-6 bg-white rounded-xl shadow">
        <h2 class="text-xl font-bold mb-4 text-gray-900 dark:text-gray-100">Generate QR Codes for {{ $this->record->name }}</h2>
        
        <form wire:submit.prevent="generateQrCodes" class="space-y-4">
            {{ $this->form }}
            
            <div class="flex justify-end gap-3 pt-4">
                <x-filament::button
                    type="submit"
                    color="primary"
                >
                    Generate QR Codes
                </x-filament::button>
            </div>
        </form>
    </div>
    
    <!-- Download instructions -->
    <div class="p-6 bg-white rounded-xl shadow">
        <h3 class="text-lg font-semibold mb-2 text-gray-900 dark:text-gray-100">About QR Code Generation</h3>
        <p class="text-gray-600 dark:text-gray-300 mb-4">When you generate QR codes, they are stored in the database and associated with this product. The CSV file will be downloaded automatically after generation.</p>
    </div>
</div>