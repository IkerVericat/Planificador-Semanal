<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TaskRequest;
use App\Http\Resources\TaskResource;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/**
 * CRUD de la tarea "plantilla". Para listar por fechas se usa OccurrenceController.
 */
class TaskController extends Controller
{
    public function store(TaskRequest $request): TaskResource
    {
        $task = $request->user()->tasks()->create($request->validated());

        return new TaskResource($task);
    }

    public function show(Request $request, string $task): TaskResource
    {
        $task = $request->user()->tasks()->findOrFail($task);
        Gate::authorize('view', $task);

        return new TaskResource($task);
    }

    public function update(TaskRequest $request): TaskResource
    {
        $task = $request->task();
        Gate::authorize('update', $task);

        $task->update($request->validated());

        return new TaskResource($task);
    }

    public function destroy(Request $request, string $task): Response
    {
        $task = $request->user()->tasks()->findOrFail($task);
        Gate::authorize('delete', $task);

        $task->delete();

        return response()->noContent();
    }
}
