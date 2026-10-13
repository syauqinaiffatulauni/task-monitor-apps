<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateTaskDueDateRequest;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TaskDueDateController extends Controller
{
    public function edit(Task $task): View
    {
        return view('tasks.edit-due-date', compact('task'));
    }

    public function update(UpdateTaskDueDateRequest $request, Task $task): RedirectResponse
    {
        $validated = $request->validated();

        $task->update($validated);

        return to_route('tasks.show', $task)->with('success', __('Due date updated successfully.'));
    }
}
