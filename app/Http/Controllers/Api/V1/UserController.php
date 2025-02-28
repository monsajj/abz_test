<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\RegistrationToken;
use App\Models\User;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\UserResource;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'count' => 'integer|min:1',
            'page' => 'integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'fails' => $validator->errors(),
            ], 422);
        }

        $perPage = $request->input('count', 5);
        $currentPage = $request->input('page', 1);
        $users = User::orderBy('id', 'asc')->paginate($perPage);

        if ($users->currentPage() > $users->lastPage()) {
            return response()->json([
                'success' => false,
                'message' => 'Page not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'page' => $currentPage,
            'total_pages' => $users->lastPage(),
            'total_users' => $users->total(),
            'count' => $users->count(),
            'links' => [
                'next_url' => $users->hasMorePages() ? $users->nextPageUrl() : null,
                'prev_url' => $users->previousPageUrl(),
            ],
            'users' => UserResource::collection($users->items()),
        ]);
    }

    public function show($id)
    {
        $validator = Validator::make(['userId' => $id], [
            'userId' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'The user with the requested id does not exist',
                'fails' => $validator->errors(),
            ], 422);
        }

        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'user' => new UserResource($user),
        ]);
    }

    public function register(Request $request)
    {
        $token = $request->header('Token');
        $registrationToken = RegistrationToken::where('token', $token)
            ->where('expires_at', '>', now())
            ->first();

        if (!$registrationToken) {
            return response()->json([
                'success' => false,
                'message' => 'The token expired.',
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:2|max:60',
            'email' => 'required|email|email:rfc,dns', // |unique:users
            'phone' => 'required|string|regex:/^\+380\d{9}$/', // |unique:users
            'position_id' => 'required|integer|exists:positions,id',
            'photo' => 'required|mimes:jpg,jpeg|dimensions:min_width=70,min_height=70|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'fails' => $validator->errors(),
            ], 422);
        }

        if (User::where('email', $request->email)->exists() || User::where('phone', $request->phone)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'User with this phone or email already exist',
            ], 409);
        }

        $imageFile = $request->file('photo');
        $filename = uniqid() . '.' . $imageFile->getClientOriginalExtension();

        $photoPath = $this->processImage($imageFile, $filename);

        Log::info($photoPath);

        //add another return to test image saving backend part without creating a user
        //ToDo delete this return and uncomment token expiration
        return response()->json([
            'success' => true,
            'user_id' => 'time 0',
            'message' => 'New user successfully registered',
        ], 201);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'position_id' => $request->position_id,
            'photo' => $photoPath,
            'password' => Hash::make($token),
        ]);

        // Here we can delete the used token from the database if needed (for example, to avoid increasing their number)
        // $registrationToken->delete();
//        $registrationToken->update(['expires_at' => now()]);

        return response()->json([
            'success' => true,
            'user_id' => $user->id,
            'message' => 'New user successfully registered',
        ], 201);
    }

    protected function processImage($imageFile, $filename)
    {
        $image = Image::make($imageFile->getRealPath());
        $image->fit(70, 70);
        $croppedPath = public_path('storage/cropped_photos/' . $filename);
        $image->save($croppedPath);


        $optimizedImagePath = $this->optimizeImageWithTinyPng($croppedPath, $filename);

        return $optimizedImagePath;
    }

    protected function optimizeImageWithTinyPng($imagePath, $filename)
    {
        Log::info($imagePath);
        Log::info($filename);
        $apiKey = config('services.tiny_png.api_key');
        $url = config('services.tiny_png.api_url');

        $response = Http::withBasicAuth('api', $apiKey)
            ->attach('file', fopen($imagePath, 'r'))
            ->post($url);

        if ($response->successful()) {
            $optimizedUrl = $response->json()['output']['url'];

            $optimizedImage = file_get_contents($optimizedUrl);
            $optimizedImagePath = 'optimized_photos/' . $filename;

            file_put_contents($optimizedImagePath, $optimizedImage);

            return $optimizedImagePath;
        } else {
            throw new \Exception('Image optimization failed: ' . $response->body());
        }
    }
}
