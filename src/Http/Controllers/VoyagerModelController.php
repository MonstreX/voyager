<?php

namespace TCG\Voyager\Http\Controllers;

use Illuminate\Http\Request;
use TCG\Voyager\Facades\Voyager;
use Illuminate\Support\Facades\Log;
use TCG\Voyager\Services\ModelInlineUpdateService;
use Throwable;

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
        } catch (Throwable $e) {
            Log::error("Voyager Tree Order Error: " . $e->getMessage());
            return $this->apiErrorResponse($e, __('voyager::generic.internal_error'), 500);
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

    public function update_field(Request $request, $id)
    {
        return app(ModelInlineUpdateService::class)->updateField($request, $id);
    }

    /**
     * Search related records for adv_related form field
     */
    public function searchRelatedRecords(Request $request)
    {
        $query = $request->get('query');
        $slug = $request->get('slug');
        $searchField = $request->get('search_field');
        $displayField = $request->get('display_field');
        $fields = explode(',', $request->get('fields'));

        // GET THE DataType based on the slug
        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        if (!$dataType) {
            return response()->json(['status' => 'error', 'message' => 'DataType not found'], 404);
        }

        // Load model and search
        $model = app($dataType->model_name);
        $this->authorize('browse', $model);

        $records = $model::where($searchField, 'like', "%{$query}%")
            ->limit(10)
            ->get()
            ->toArray();

        $suggestions = [];
        foreach ($records as $record) {
            $item = ['id' => $record['id']];
            foreach ($fields as $field) {
                $item[$field] = $record[$field] ?? null;
            }
            $suggestions[] = [
                'value' => $record[$displayField],
                'data' => $item
            ];
        }

        return response()->json([
            'status' => 'success',
            'suggestions' => $suggestions
        ]);
    }

    /**
     * Clone a record
     */
    public function clone(Request $request, $id)
    {
        $slug = $this->getSlug($request);

        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();
        if (!$dataType) {
            return redirect()->back()->with(['message' => __('voyager::generic.error_cloning', ['type' => 'Record']), 'alert-type' => 'error']);
        }

        // Check permission
        try {
            $this->authorize('add', app($dataType->model_name));
        } catch (\Exception $e) {
            return redirect()->back()->with(['message' => __('voyager::generic.unauthorized_action'), 'alert-type' => 'error']);
        }

        try {
            // Load source and replicate
            $source = app($dataType->model_name)->findOrFail($id);
            $cloned = $source->replicate();

            // Get clone config from model
            $cloneConfig = property_exists($source, 'clone') ? $source->clone : [];

            // Process each attribute
            foreach ($cloned->getAttributes() as $field => $value) {
                if (array_key_exists($field, $cloneConfig)) {
                    $action = $cloneConfig[$field];

                    if ($action === null) {
                        // Reset field
                        $cloned->{$field} = null;
                    } elseif (is_string($action)) {
                        // Append suffix
                        $cloned->{$field} = $value . $action;
                    }
                }
                // else: field not in config, keep cloned value as-is
            }

            // Save
            $res = $cloned->save();

            if ($res) {
                return redirect()
                    ->route("voyager.{$dataType->slug}.index")
                    ->with(['message' => __('voyager::generic.successfully_cloned', ['type' => $dataType->display_name_singular]), 'alert-type' => 'success']);
            }

            return redirect()->back()->with(['message' => __('voyager::generic.error_cloning', ['type' => $dataType->display_name_singular]), 'alert-type' => 'error']);
        } catch (Throwable $e) {
            Log::error("Voyager Clone Error: " . $e->getMessage());
            return redirect()->back()->with([
                'message' => $this->apiExceptionMessage($e, __('voyager::generic.error_cloning', ['type' => 'Record'])),
                'alert-type' => 'error',
            ]);
        }
    }
}
