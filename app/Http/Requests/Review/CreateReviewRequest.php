<?php

declare(strict_types=1);

namespace App\Http\Requests\Review;

use Illuminate\Foundation\Http\FormRequest;

class CreateReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'booking_uuid' => ['required', 'string', 'exists:bookings,uuid'],
            'rating'       => ['required', 'integer', 'min:1', 'max:5'],
            'comment'      => ['nullable', 'string', 'max:1000'],
        ];
    }
}
