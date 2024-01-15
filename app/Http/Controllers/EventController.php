<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EventController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'description' => 'required',
            'start_date' => 'required',
            'end_date' => 'required',
            'start_time' => 'required',
            'end_time' => 'required',
            'location' => 'nullable',
            'image' => 'nullable|image|max:2048',
            'images.*' => 'image|max:2048',
            'user_id' => 'required',
        ]);

        $event = new Event();
        $event->name = $request->name;
        $event->description = $request->description;
        $event->start_date = $request->start_date;
        $event->end_date = $request->end_date;
        $event->start_time = $request->start_time;
        $event->end_time = $request->end_time;
        $event->location = $request->location;
        $event->user_id = $request->user_id;
        $event->save(); // Save the event to generate an ID

        if ($request->hasFile('image')) {
            $imagePath = 'public/users/' . $request->user_id . '/events/' . $event->id;
            $profileImageName = 'profileImage.jpg';
            $request->image->storeAs($imagePath, $profileImageName);
            $event->image = $profileImageName;
            $event->save(); // Update the event with the image name
        }

        $additionalImages = [];
        if ($request->has('images')) {
            $images = $request->file('images');
            foreach ($images as $key => $image) {
                $imageName = $event->id . '_image_' . $key . '_' . time() . '.' . $image->extension();
                $imagePath = 'public/users/' . $request->user_id . '/events/' . $event->id;
                $image->storeAs($imagePath, $imageName);

                $additionalImages[] = $imageName;
            }
        }

        if (!empty($additionalImages)) {
            $event->additional_images = json_encode($additionalImages); // Assuming 'additional_images' is a JSON field in your Event model
            $event->save(); // Save the event with additional images
        }

        return response()->json(['message' => 'Event created successfully'], 200);
    }

    public function update(Request $request)
    {
        Log::info("Update");

        $request->validate([
            'name' => 'required',
            'description' => 'required',
            'start_date' => 'required',
            'end_date' => 'required',
            'start_time' => 'required',
            'end_time' => 'required',
            'location' => 'nullable',
            'image' => 'nullable|image|max:2048',
            'images.*' => 'image|max:2048',
            'user_id' => 'required',
            'event_id' => 'required',
        ]);
        $event = Event::find($request->event_id);
        if (!$event) {
            return response()->json(['message' => 'Event not found'], 404);
        }

        $event->name = $request->name;
        $event->description = $request->description;
        $event->start_date = $request->start_date;
        $event->end_date = $request->end_date;
        $event->start_time = $request->start_time;
        $event->end_time = $request->end_time;
        $event->location = $request->location;

        $imagePath = 'public/users/' . $request->user_id . '/events/' . $event->id;
        $profileImageName = 'profileImage.jpg';

        if ($request->hasFile('image')) {
            $request->image->storeAs($imagePath, $profileImageName);
            $event->image = $profileImageName;
        } else {
            if ($event->image && Storage::exists($imagePath . '/' . $profileImageName)) {
                Storage::delete($imagePath . '/' . $profileImageName);
                $event->image = null;
            }
        }


        $additionalImages = [];
        if ($request->has('images')) {
            $images = $request->file('images');
            foreach ($images as $key => $image) {
                $imageName = $event->id . '_image_' . $key . '_' . time() . '.' . $image->extension();
                $imagePath = 'public/users/' . $request->user_id . '/events/' . $event->id;
                $image->storeAs($imagePath, $imageName);

                $additionalImages[] = $imageName;
            }
        } else {
            // If no new event images and old images exist, delete them
            if (!empty(json_decode($event->additional_images))) {
                foreach (json_decode($event->additional_images) as
                    $oldImage) {
                    $oldImagePath = 'path_to_old_event_images_directory/' . $oldImage;
                    if (Storage::exists($oldImagePath)) {
                        Storage::delete($oldImagePath);
                    }
                }
                $event->additional_images = null; // Set the additional_images field to null
            }
        }

        if (!empty($additionalImages)) {
            $event->additional_images = json_encode($additionalImages); // Update the additional_images field
        }

        $event->save();

        return response()->json(['message' => 'Event updated successfully'], 200);
    }

    public function toggleGoing(Request $request)
    {
        $isGoing = filter_var($request->input('is_going'), FILTER_VALIDATE_BOOLEAN);
        try {
            $request->validate([
                'user_id' => 'required|integer',
                'event_id' => 'required|integer',
            ]);

            if ($isGoing) {
                DB::table('event_user')->updateOrInsert(
                    ['user_id' => $request->user_id, 'event_id' => $request->event_id],
                );
                $message = 'Successfully marked as going';
            } else {
                DB::table('event_user')->where([
                    ['user_id', '=', $request->user_id],
                    ['event_id', '=', $request->event_id]
                ])->delete();
                $message = 'Successfully removed from the event';
            }

            return response()->json(['message' => $message], 200);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Invalid data provided', 'errors' => $e->errors()], 400);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['message' => 'An error occurred'], 500);
        }
    }

    public function getEvents()
    {
        return response()->json(Event::with(['user', 'goingToUsers'])->get());
    }

    public function getEventsOrganizedByUser($user_id)
    {
        return response()->json(Event::where('user_id', $user_id)->with(['user', 'goingToUsers'])->get());
    }

    public function getEventsToWhichUserIsGoing($user_id)
    {
        return response()->json(User::find($user_id)->goingToEvents()->with(['user', 'goingToUsers'])->get());
    }

    public function search(Request $request)
    {
        $query = $request->input('q');
        return response()->json(
            Event::with(['user', 'goingToUsers'])
                ->where('name', 'LIKE', '%' . $query . '%')
                ->orWhere('description', 'LIKE', '%' . $query . '%')
                ->get()
        );
    }
}
