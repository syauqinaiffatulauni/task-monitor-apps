<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateTaskProgressRequest;
use App\Http\Requests\UpdateTaskStatusRequest;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;

class TaskProgressController extends Controller
{
    public function updateStatus(UpdateTaskStatusRequest $request, Task $task): RedirectResponse
    {
        $validated = $request->validated();

        $task->update($validated + ['progress_updated_at' => now()]);

        return back()->with('success', __('Status updated successfully.'));
    }

    public function update(UpdateTaskProgressRequest $request, Task $task): RedirectResponse
    {
        $validated = $request->validated();

        $task->update($validated + ['progress_updated_at' => now()]);

        return to_route('tasks.show', $task)->with('success', __('Progress updated successfully.'));
    }
}
