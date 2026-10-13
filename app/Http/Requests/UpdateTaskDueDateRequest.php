<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskDueDateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('changeDueDate', $this->route('task')) ?? false;
    }

    public function rules(): array
    {
        return [
            'due_date' => ['required', 'date'],
        ];
    }
}
