<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function storeContact(Request $request)
    {
        // contacts তো সরাসরি array আকারে আসবে
        $contacts = $request->contacts;

        // arr যেহেতু stringified JSON, তাই সেটা decode করতে হবে
        $arrDecoded = json_decode($request->arr, true); // true = associative array

        return response()->json([
            'status' => true,
            'user_id' => $request->user_id,
            'contacts' => $contacts,
            'arr' => $arrDecoded
        ]);
    }

    public function searchContact(Request $request)
    {
        return $request->all();
    }
}
