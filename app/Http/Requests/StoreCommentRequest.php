<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        if (auth()->check()) {
            return [
                'post_id' => 'required|exists:posts,id',
                'parent_id' => 'nullable|exists:comments,id',
                'comment' => 'required|string|max:5000',
            ];
        }

        return [
            'post_id' => 'required|exists:posts,id',
            'parent_id' => 'nullable|exists:comments,id',

            'guest_name' => 'required|string|max:255',
            'guest_email' => 'required|email|max:255',

            'comment' => 'required|string|max:5000',
        ];
    }
}
