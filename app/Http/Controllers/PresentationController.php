<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Presentation;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class PresentationController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $presentations = $user->presentations;

        return response()->json(['presentations' => $presentations]);
    }

    public function store(Request $request)
    {
       // Validate the request data
        $validatedData = $request->validate([
            'topic' => 'required|string',
            'background' => 'nullable|string',
            'color' => 'nullable|string',
            'num_of_slides' => 'nullable|integer',
            'slides' => 'required', // Assuming slides is an array of JSON data
        ]);

        // Create a new presentation
        $presentation = new Presentation([
            'topic' => $validatedData['topic'],
            'background' => $validatedData['background'],
            'color' => $validatedData['color'],
            'num_of_slides' => $validatedData['num_of_slides'],
            'slides' => $validatedData['slides'],
        ]);

        // Associate the presentation with the authenticated user
        $user = auth()->user();
        $user->presentations()->save($presentation);

        // Return a response, e.g., a success message or a redirect
        return response()->json(['message' => 'Presentation created successfully']); 
    }

    public function saveImageFromUrl(Request $request)
    {
        $imageUrl = $request->input('url'); // URL of the image you want to fetch
        // Fetch the image from the URL
        $response = Http::get($imageUrl);

        if ($response->successful()) {
            // Generate a unique filename (e.g., using a timestamp)
            $filename = time() . '_' . uniqid() . '.jpg';

            // Save the image to your local storage
            Storage::disk('public')->put('presentations_image/' . $filename, $response->body());
                        
            $imagePath = 'presentations_image/' . $filename;
            return response()->json(['message' => 'Image saved successfully', 'path' => $imagePath], 200);
        } else {
            return response()->json(['message' => 'Failed to fetch and save the image'], 500);
        }
    }


}
