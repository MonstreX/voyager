<?php

namespace TCG\Voyager\Http\Controllers;

use Illuminate\Http\Request;
use TCG\Voyager\Facades\Voyager;
use Illuminate\Support\Facades\Log;

class VoyagerModelController extends Controller
{
    /**
     * Update Tree Order (Nestable).
     */
    public function order(Request $request)
    {
        $slug = $request->input('slug');
        $itemsOrder = json_decode($request->input('order'));

        if (!$slug || !$itemsOrder) {
             return response()->json(['status' => 'error', 'message' => 'Invalid parameters'], 400);
        }

        // GET THE DataType based on the slug
        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        if (!$dataType) {
            return response()->json(['status' => 'error', 'message' => 'Missing DataType'], 404);
        }

        $modelClass = $dataType->model_name;
        if (!class_exists($modelClass)) {
             return response()->json(['status' => 'error', 'message' => "Model $modelClass not found"], 500);
        }

        // Load model
        $model = app($modelClass);

        // Check permission
        $this->authorize('edit', $model);

        try {
            $this->orderTree($itemsOrder, null, $model);
        } catch (\Exception $e) {
            Log::error("Voyager Tree Order Error: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }

        return response()->json(['status' => 'success', 'message' => __('voyager::bread.updated_order')]);
    }

    private function orderTree(array $children, $parentId, $model)
    {
        foreach ($children as $index => $child) {
            $item = $model->find($child->id);
            if ($item) {
                $oldOrder = $item->order;
                $item->order = $index + 1;
                $item->parent_id = $parentId;
                $item->save();
                
                // Log::info("Saved Item ID: {$item->id}, Order: {$index + 1} (was {$oldOrder}), Parent: {$parentId}");

                if (isset($child->children)) {
                    $this->orderTree($child->children, $item->id, $model);
                }
            }
        }
    }

    // Future methods for inline-edit, clone, etc. will go here.
}
