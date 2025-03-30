<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Biller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BillerController extends Controller
{
    /**
     * Display a listing of billers.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $billers = Biller::all();

        return response()->json([
            'success' => true,
            'data' => $billers
        ]);
    }

    /**
     * Store a newly created biller.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:billers',
            'category_id' => 'required|exists:biller_categories,id',
            'logo_url' => 'nullable|string',
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $biller = Biller::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Biller created successfully',
            'data' => $biller
        ], 201);
    }

    /**
     * Display the specified biller.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $biller = Biller::find($id);

        if (!$biller) {
            return response()->json([
                'success' => false,
                'message' => 'Biller not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $biller
        ]);
    }

    /**
     * Update the specified biller.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $biller = Biller::find($id);

        if (!$biller) {
            return response()->json([
                'success' => false,
                'message' => 'Biller not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'code' => 'string|max:50|unique:billers,code,' . $id,
            'category_id' => 'exists:biller_categories,id',
            'logo_url' => 'nullable|string',
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $biller->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Biller updated successfully',
            'data' => $biller
        ]);
    }

    /**
     * Remove the specified biller.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $biller = Biller::find($id);

        if (!$biller) {
            return response()->json([
                'success' => false,
                'message' => 'Biller not found'
            ], 404);
        }

        $biller->delete();

        return response()->json([
            'success' => true,
            'message' => 'Biller deleted successfully'
        ]);
    }

    /**
     * Get billers by category.
     *
     * @param  int  $categoryId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getByCategory($categoryId)
    {
        $billers = Biller::where('category_id', $categoryId)
            ->where('is_active', true)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $billers
        ]);
    }
}
