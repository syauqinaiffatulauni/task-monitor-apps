<?php

namespace App\Http\Requests;

class UpdateTaskRequest extends StoreTaskRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('task')) ?? false;
    }

    public function rules(): array
    {
        $rules = parent::rules();

        if (! $this->user()?->isSupervisor()) {
            $rules['owner_id'] = ['nullable'];
            $rules['user_id'] = ['nullable'];
        }

        return $rules + [
            'progress_note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
