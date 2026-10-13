<?php

namespace App\Http\Requests;

use App\Enums\TaskStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskProgressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('updateProgress', $this->route('task')) ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(TaskStatus::class)],
            'progress_note' => ['required', 'string', 'max:2000'],
        ];
    }
}
