<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\BillerCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BillerCategoryController extends Controller
{
    /**
     * Display a listing of biller categories.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $categories = BillerCategory::all();

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    /**
     * Store a newly created biller category.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:biller_categories',
            'icon' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $category = BillerCategory::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Biller category created successfully',
            'data' => $category
        ], 201);
    }

    /**
     * Display the specified biller category.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $category = BillerCategory::find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Biller category not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $category
        ]);
    }

    /**
     * Update the specified biller category.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $category = BillerCategory::find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Biller category not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'code' => 'string|max:50|unique:biller_categories,code,' . $id,
            'icon' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $category->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Biller category updated successfully',
            'data' => $category
        ]);
    }

    /**
     * Remove the specified biller category.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $category = BillerCategory::find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Biller category not found'
            ], 404);
        }

        // Check if category has billers
        if ($category->billers()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete category with associated billers'
            ], 400);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Biller category deleted successfully'
        ]);
    }
}
