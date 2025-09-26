<?php
namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use App\Commands\ProcessUnauthenticatedClaimCommand;
use App\Domain\ValueObjects\RewardCode;

class UnauthenticatedClaimRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array { return ['code' => ['required', 'string']]; }

    public function toCommand(): ProcessUnauthenticatedClaimCommand
    {
        return new ProcessUnauthenticatedClaimCommand(
            RewardCode::fromString($this->validated()['code'])
        );
    }
}
