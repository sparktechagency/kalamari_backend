<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServicePage;
use App\Models\ServicePageButton;
use App\Models\ServicePageButtonModal;
use App\Models\ServicePageField;
use App\Models\ServicePageSelection;
use App\Models\ServicePageSelectionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ServiceController extends Controller
{
    public function createService(Request $request)
    {
        // Validation Rules
        $validator = Validator::make($request->all(), [
            'service_id' => 'sometimes|numeric',
            'name' => 'required|string|unique:services,name',
        ]);

        // Validation Errors
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        $haveService = Service::where('id', $request->service_id)->first();

        if ($haveService) {
            $haveService->name = $request->name;
            $haveService->save();

            return response()->json([
                'status' => true,
                'message' => 'Service updated successful',
                'data' => $haveService
            ], 201);
        } else {
            $service = Service::create([
                'name' => $request->name
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Service created successful',
                'data' => $service
            ], 201);
        }
    }

    public function createPage(Request $request)
    {
        // Validation Rules
        $validator = Validator::make($request->all(), [
            'service_id' => 'required|numeric',
            'page' => 'required|numeric',
            'type' => 'required|string',
            'name' => 'required|string',
            'image' => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        // Validation Errors
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        if ($request->hasFile('image')) {
            $file      = $request->file('image');
            $filename  = time() . '_' . $file->getClientOriginalName();
            $filepath  = $file->storeAs('images', $filename, 'public');

            $bg_image = '/storage/' . $filepath;
        }

        $service_page = ServicePage::create([
            'service_id' => $request->service_id,
            'page' => $request->page,
            'type' => $request->type,
            'name' => $request->name,
            'image' => $bg_image ?? null
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Page created successful',
            'data' => $service_page
        ], 201);
    }

    public function addField(Request $request)
    {
        // Validation Rules
        $validator = Validator::make($request->all(), [
            'service_page_id' => 'required|numeric',
            'name' => 'required|string',
            'type' => 'required|string',
            'price' => 'sometimes|string',
        ]);

        // Validation Errors
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        $service_page_field = ServicePageField::create([


            'service_page_id' => $request->service_page_id,
            'name' => $request->name,
            'type' => $request->type,
            'price' => $request->price
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Field added successful',
            'data' => $service_page_field
        ], 201);
    }

    public function addButton(Request $request)
    {
        // Validation Rules
        $validator = Validator::make($request->all(), [
            // 'service_id' => 'required|numeric',
            'service_page_id' => 'required|numeric',
            'button_text' => 'required|string',
            'action' => 'required|string',
        ]);

        // Validation Errors
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 422);
        }


        $service_page_button = ServicePageButton::create([
            // 'user_id' => Auth::id(),
            // 'service_id' => $request->service_id,
            'service_page_id' => $request->service_page_id,
            'button_text' => $request->button_text,
            'action' => $request->action
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Button added successful',
            'data' => $service_page_button
        ], 201);
    }

    public function buttonAction(Request $request)
    {
        // Validation Rules
        $validator = Validator::make($request->all(), [
            // 'service_id' => 'required|numeric',
            // 'service_page_id' => 'required|numeric',
            'service_page_button_id' => 'required|numeric',
            'modal_name' => 'required|string',
            'fields' => 'required',
        ]);

        // Validation Errors
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 422);
        }



        $service_page_button_modal = ServicePageButtonModal::create([
            // 'user_id' => Auth::id(),
            // 'service_id' => $request->service_id,
            // 'service_page_id' => $request->service_page_id,
            'service_page_button_id' => $request->service_page_button_id,
            'modal_name' => $request->modal_name,
            'fields' => $request->fields
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Button modal added successful',
            'data' => $service_page_button_modal
        ], 201);
    }

    public function addSelection(Request $request)
    {
        // Validation Rules
        $validator = Validator::make($request->all(), [
            // 'service_id' => 'required|numeric',
            'service_page_id' => 'required|numeric',
            'select_area_name' => 'required|string',
        ]);

        // Validation Errors
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 422);
        }



        $service_page_selection = ServicePageSelection::create([
            // 'user_id' => Auth::id(),
            // 'service_id' => $request->service_id,
            'service_page_id' => $request->service_page_id,
            'select_area_name' => $request->select_area_name,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Selection added successful',
            'data' => $service_page_selection
        ], 201);
    }

    public function addSelectAreaItem(Request $request)
    {
        // Validation Rules
        $validator = Validator::make($request->all(), [
            // 'service_id' => 'required|numeric',
            // 'service_page_id' => 'required|numeric',
            'service_page_selection_id' => 'required|numeric',
            'selection_text' => 'required|string',
            'type' => 'required|string',
            'price' => 'required|string',
        ]);

        // Validation Errors
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 422);
        }




        $service_page_selection_item = ServicePageSelectionItem::create([
            // 'user_id' => Auth::id(),
            // 'service_id' => $request->service_id,
            // 'service_page_id' => $request->service_page_id,
            'service_page_selection_id' => $request->service_page_selection_id,
            'selection_text' => $request->selection_text,
            'type' => $request->type,
            'price' => $request->price,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Selection item added successful',
            'data' => $service_page_selection_item
        ], 201);
    }

    // public function getServices(Request $request)
    // {
    //     if ($request->service_id == true) {
    //         $services = Service::with(['servicePages.fields','servicePages.buttons','servicePages.selections','servicePages.buttons.modals','servicePages.selections.items'])->find($request->service_id);
    //         $services_name = Service::find($request->service_id)->name;
    //     } else {
    //         $services = Service::with('servicePages.fields')->get();
    //     }

    //     return response()->json([
    //         'status' => true,
    //         'message' => $request->service_id ? 'Get ' . $services_name . ' service' : 'Get all services',
    //         'data' => $services
    //     ], 200);
    // }

    public function getServices(Request $request)
    {
        if ($request->service_id == true) {
            $services = Service::with([
                'servicePages.fields',
                'servicePages.buttons.modals',
                'servicePages.selections.items'
            ])->find($request->service_id);

            // decode modal fields if string
            foreach ($services->servicePages as $page) {
                foreach ($page->buttons as $button) {
                    foreach ($button->modals as $modal) {
                        if (is_string($modal->fields)) {
                            $modal->fields = json_decode($modal->fields, true);
                        }
                    }
                }
            }

            $services_name = $services->name;
        } else {
            $services = Service::with(
                'servicePages.fields',
                'servicePages.buttons.modals',
                'servicePages.selections.items'
            )->get();
        }

        return response()->json([
            'status' => true,
            'message' => $request->service_id ? 'Get ' . $services_name . ' service' : 'Get all services',
            'data' => $services
        ], 200);
    }

    public function getServiceLists()
    {
        $serviceLists = Service::all()->pluck('name');
        return response()->json([
            'status' => true,
            'message' => 'Get service lists',
            'data' => $serviceLists
        ]);
    }
}
