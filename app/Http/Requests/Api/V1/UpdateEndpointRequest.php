<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Rules\SafeExternalUrl;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEndpointRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'url' => ['sometimes', 'string', 'max:2048', new SafeExternalUrl],
            'is_active' => ['sometimes', 'boolean'],
            'max_retries' => ['sometimes', 'integer', 'min:1', 'max:20'],
            'timeout_seconds' => ['sometimes', 'integer', 'min:1', 'max:60'],
        ];
    }
}
