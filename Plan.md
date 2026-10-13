# Implementation Plan: Officers Can View All Tasks (Edit & Delete Restricted to Owner)

## Goal Description
Allow signed-in officers to view tasks listed from every officer on the **All Tasks** page (`/tasks`) and view task details (`/tasks/{task}`), while strictly enforcing that **only the assigned owner** can edit or delete their task.
For supervisors, **no changes** will be made to their existing permissions or behavior.
On **My Tasks** (`/my-tasks`), officers continue to see only tasks assigned to them.

---

## Authorization & Policy Matrix

| Route / Action | Officer (Assigned Owner) | Officer (Non-Owner) | Supervisor (Unchanged) |
| :--- | :--- | :--- | :--- |
| **All Tasks (`/tasks`)** | View all | View all | View all |
| **My Tasks (`/my-tasks`)** | View own only | View own only | View own only |
| **View Details (`tasks.show`)** | View (200) | View (200, read-only) | View (200) |
| **Edit Page (`tasks.edit`)** | Allowed (200) | **Forbidden (403)** | Allowed (200) |
| **Update Task (`tasks.update`)** | Allowed (Redirect) | **Forbidden (403)** | Allowed (Redirect) |
| **Delete Task (`tasks.destroy`)** | Allowed (Redirect) | **Forbidden (403)** | **Forbidden (403)** |
| **Update Progress / Status** | Allowed | **Forbidden (403)** | Allowed |

---

## Detailed Changes

### 1. Policies (`app/Policies/TaskPolicy.php`)
- `view(User $user, Task $task)`: Return `true` (any authenticated user can view task details).
- `viewAll(User $user)`: Keep for supervisors (or return `true` for all task-viewing routes).
- `update(User $user, Task $task)`: Kept as `$user->isSupervisor() || $task->owner_id === $user->id`.
- `delete(User $user, Task $task)`: Kept as `! $user->isSupervisor() && $task->owner_id === $user->id`.
- `changeStatus(User $user, Task $task)`: Kept as `$user->isSupervisor() || $task->owner_id === $user->id`.
- `updateProgress(User $user, Task $task)`: Kept as `$user->isSupervisor() || $task->owner_id === $user->id`.

### 2. Controller Query Handling (`app/Http/Controllers/TaskController.php`)
- In `taskList(FilterTasksRequest $request, bool $assignedOnly = false)`:
  - If `$assignedOnly` is `true` (i.e. `/my-tasks`), use `$user->tasks()->with('assignedOfficer')`.
  - If `$assignedOnly` is `false` (i.e. `/tasks`), query all tasks `Task::query()->with('assignedOfficer')`.
  - Ensure filters (status, priority, search, overdue) and pagination apply consistently to the query.

### 3. Views (`resources/views/tasks/index.blade.php` & `resources/views/tasks/show.blade.php`)
- `index.blade.php`: Edit and Delete buttons and inline status update form are already gated by `@can('update', $task)`, `@can('delete', $task)`, and `@can('changeStatus', $task)`. Non-owners viewing other officers' tasks will see rows without Edit, Delete, or inline status update controls.
- `show.blade.php`: Action buttons and progress update form are already gated by `@can('update', $task)`, `@can('delete', $task)`, and `@can('updateProgress', $task)`. Non-owners will see a read-only details view.

### 4. Tests
- Add comprehensive new feature test: `tests/Feature/OfficerAllTasksViewTest.php`:
  - `test_officer_sees_all_officers_tasks_on_all_tasks_page`
  - `test_officer_sees_only_own_tasks_on_my_tasks_page`
  - `test_officer_can_view_show_page_for_another_officers_task`
  - `test_officer_does_not_see_edit_or_delete_buttons_for_another_officers_task_on_all_tasks`
  - `test_officer_cannot_edit_another_officers_task_and_gets_403`
  - `test_officer_cannot_update_another_officers_task_and_gets_403`
  - `test_officer_cannot_delete_another_officers_task_and_gets_403`
  - `test_supervisor_permissions_remain_unchanged`
- Update existing tests (`TaskWorkflowTest.php` and `TaskCrudTest.php`) that asserted officers cannot see other tasks on `/tasks` and `tasks.show` to match the new business specification approved by the user.

---

## Verification Plan
1. Run PHPUnit feature tests:
   `& "C:\laragon\bin\php\php\php.exe" artisan test`
2. Run code style check:
   `vendor/bin/pint --dirty`
