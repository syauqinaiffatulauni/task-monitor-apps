<?php

namespace App\Http\Requests;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Task::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
            'owner_id' => ['required', Rule::exists('users', 'id')->where('role', 'officer')],
            'due_date' => ['required', 'date'],
            'priority' => ['required', Rule::enum(TaskPriority::class)],
            'status' => ['required', Rule::enum(TaskStatus::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => __('Please enter a task title.'),
            'title.max' => __('The task title must not exceed 255 characters.'),
            'description.required' => __('Please enter a task description.'),
            'description.max' => __('The description must not exceed 2,000 characters.'),
            'owner_id.required' => __('Please choose a task owner.'),
            'owner_id.exists' => __('Please choose a valid task owner.'),
            'due_date.required' => __('Please choose a due date.'),
            'due_date.date' => __('Please enter a valid due date.'),
            'priority.required' => __('Please choose a priority.'),
            'priority.enum' => __('Please choose a valid priority.'),
            'status.required' => __('Please choose a status.'),
            'status.enum' => __('Please choose a valid status.'),
        ];
    }
}
