<?php
namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use App\Commands\ProcessProductScanCommand;
use App\Domain\ValueObjects\UserId;
use App\Domain\ValueObjects\RewardCode;

class ClaimRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array { return ['code' => ['required', 'string']]; }

    public function toCommand(): ProcessProductScanCommand
    {
        return new ProcessProductScanCommand(
            UserId::fromInt($this->user()->id),
            RewardCode::fromString($this->validated()['code'])
        );
    }
}
