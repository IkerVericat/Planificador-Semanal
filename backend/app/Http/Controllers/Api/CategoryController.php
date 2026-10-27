<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Http\Resources\CategoryResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class CategoryController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $categories = $request->user()->categories()->orderBy('position')->orderBy('name')->get();

        return CategoryResource::collection($categories);
    }

    public function store(CategoryRequest $request): CategoryResource
    {
        $category = $request->user()->categories()->create($request->validated());

        return new CategoryResource($category);
    }

    public function update(CategoryRequest $request): CategoryResource
    {
        $category = $request->category();
        Gate::authorize('update', $category);

        $category->update($request->validated());

        return new CategoryResource($category);
    }

    /**
     * Las tareas de la categoría se quedan sin categoría (FK con ON DELETE SET NULL).
     */
    public function destroy(Request $request, string $category): Response
    {
        $category = $request->user()->categories()->findOrFail($category);
        Gate::authorize('delete', $category);

        $category->delete();

        return response()->noContent();
    }
}
