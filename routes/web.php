<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\SupervisorController;
use App\Http\Controllers\TaskDueDateController;
use App\Http\Controllers\TaskProgressController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::middleware(['auth', 'no-cache'])->group(function () {
    Route::get('/', fn () => redirect()->route(auth()->user()->isSupervisor() ? 'supervisor.index' : 'tasks.mine', request()->query()));
    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar');
    Route::get('/calendar/events', [CalendarController::class, 'events'])->name('calendar.events');
    Route::get('/tasks', [TaskController::class, 'index'])->middleware('can:viewAny,App\Models\Task')->name('tasks.index');
    Route::get('/my-tasks', [TaskController::class, 'mine'])->middleware('can:viewAny,App\Models\Task')->name('tasks.mine');
    Route::get('/tasks/create', [TaskController::class, 'create'])->middleware('can:create,App\Models\Task')->name('tasks.create');
    Route::post('/tasks', [TaskController::class, 'store'])->middleware('can:create,App\Models\Task')->name('tasks.store');
    Route::get('/tasks/{task}', [TaskController::class, 'show'])->middleware('can:view,task')->name('tasks.show');
    Route::get('/tasks/{task}/edit', [TaskController::class, 'edit'])->middleware('can:update,task')->name('tasks.edit');
    Route::put('/tasks/{task}', [TaskController::class, 'update'])->middleware('can:update,task')->name('tasks.update');
    Route::put('/tasks/{task}/progress', [TaskProgressController::class, 'update'])->middleware('can:updateProgress,task')->name('tasks.progress.update');
    Route::patch('/tasks/{task}/status', [TaskProgressController::class, 'updateStatus'])->middleware('can:changeStatus,task')->name('tasks.status.update');
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->middleware('can:delete,task')->name('tasks.destroy');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::post('/locale', [LocaleController::class, 'update'])->name('locale.update');

    Route::middleware('supervisor')->group(function () {
        Route::get('/supervisor', [SupervisorController::class, 'index'])->name('supervisor.index');
        Route::get('/tasks/{task}/due-date', [TaskDueDateController::class, 'edit'])->middleware('can:changeDueDate,task')->name('tasks.due-date.edit');
        Route::put('/tasks/{task}/due-date', [TaskDueDateController::class, 'update'])->middleware('can:changeDueDate,task')->name('tasks.due-date.update');
    });
});
