<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Resources\TaxResource;
use App\Http\Resources\UserResource;
use App\Models\Tax;
use App\Services\TaxService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, true)) {
            throw ValidationException::withMessages([
                'email' => 'Invalid credentials.',
            ]);
        }

        if (! $request->user()?->isAdmin()) {
            Auth::guard('web')->logout();

            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            throw ValidationException::withMessages([
                'email' => 'Invalid credentials.',
            ]);
        }

        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        return new UserResource($request->user());
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logged out']);
    }

    public function me(Request $request)
    {
        return new UserResource($request->user());
    }

    public function updateProfile(ProfileRequest $request)
    {
        $user = $request->user();
        $user->fill($request->validated())->save();

        return new UserResource($user);
    }

    public function updatePassword(UpdatePasswordRequest $request)
    {
        $request->user()->update(['password' => $request->validated('password')]);

        return response()->json(['message' => 'Password updated']);
    }

    public function taxes()
    {
        return TaxResource::collection(Tax::query()->orderBy('name')->get());
    }

    public function taxPreview(Request $request, TaxService $taxes)
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.quantity' => ['required', 'numeric', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_id' => ['nullable', 'exists:taxes,id'],
            'tax_mode' => ['nullable', 'in:none,per_item,overall'],
            'overall_discount' => ['nullable', 'numeric', 'min:0'],
            'overall_tax_id' => ['nullable', 'exists:taxes,id'],
        ]);

        return response()->json($taxes->quotationTotals(
            $data['items'],
            $data['tax_mode'] ?? 'per_item',
            (float) ($data['overall_discount'] ?? 0),
            isset($data['overall_tax_id']) ? (int) $data['overall_tax_id'] : null,
        ));
    }
}
