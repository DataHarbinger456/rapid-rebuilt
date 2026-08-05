<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class SettingsController extends Controller
{
    /**
     * Settings dashboard
     */
    public function index(Request $request): View|JsonResponse
    {
        $user = $request->user();

        if (!$user->isStaff()) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            abort(403);
        }

        $settings = Setting::all()->pluck('value', 'key');

        $data = [
            'settings' => $settings,
            'setting_groups' => $this->getSettingGroups(),
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        }

        return view('admin.settings.index', $data);
    }

    /**
     * Update settings
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'nullable',
        ]);

        foreach ($request->input('settings') as $setting) {
            Setting::set(
                $setting['key'],
                $setting['value'],
                $setting['group'] ?? 'general'
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully',
        ]);
    }

    /**
     * Get specific setting
     */
    public function show(Request $request, string $key): JsonResponse
    {
        $user = $request->user();

        if (!$user->isStaff()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $setting = Setting::where('key', $key)->first();

        if (!$setting) {
            return response()->json([
                'success' => false,
                'message' => 'Setting not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $setting,
        ]);
    }

    /**
     * Pricing settings
     */
    public function pricing(Request $request): View|JsonResponse
    {
        $user = $request->user();

        if (!$user->isStaff()) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            abort(403);
        }

        $pricingSettings = Setting::where('group', 'pricing')->get()->pluck('value', 'key');

        $data = [
            'pricing' => $pricingSettings,
            'defaults' => [
                'base_price' => 350.00,
                'rush_fee' => 100.00,
                'component_price' => 25.00,
                'mobile_inspection_fee' => 75.00,
            ],
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        }

        return view('admin.settings.pricing', $data);
    }

    /**
     * Update pricing
     */
    public function updatePricing(Request $request): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $user = $request->user();

        if (!$user->isAdmin()) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            abort(403);
        }

        $request->validate([
            'base_price' => 'required|numeric|min:0',
            'rush_fee' => 'required|numeric|min:0',
            'component_price' => 'required|numeric|min:0',
            'mobile_inspection_fee' => 'required|numeric|min:0',
        ]);

        Setting::set('base_price', $request->input('base_price'), 'pricing');
        Setting::set('rush_fee', $request->input('rush_fee'), 'pricing');
        Setting::set('component_price', $request->input('component_price'), 'pricing');
        Setting::set('mobile_inspection_fee', $request->input('mobile_inspection_fee'), 'pricing');

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Pricing updated successfully',
            ]);
        }

        return redirect()->route('admin.settings.pricing')
            ->with('success', 'Pricing updated successfully.');
    }

    /**
     * User management
     */
    public function users(Request $request): View|JsonResponse
    {
        $user = $request->user();

        if (!$user->isAdmin()) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            abort(403);
        }

        $query = User::query();

        if ($request->filled('role')) {
            $query->where('user_type', $request->input('role'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 20));

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $users,
            ]);
        }

        return view('admin.settings.users', ['users' => $users]);
    }

    /**
     * Create user
     */
    public function createUser(Request $request): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $user = $request->user();

        if (!$user->isAdmin()) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            abort(403);
        }

        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', Password::defaults()],
            'user_type' => 'required|in:user,dealer,inspector,staff,admin',
            'phone' => 'nullable|string|max:255',
        ]);

        $newUser = User::create([
            'first_name' => $request->input('first_name'),
            'last_name' => $request->input('last_name'),
            'email' => $request->input('email'),
            'username' => $this->generateUsername(
                $request->input('first_name'),
                $request->input('last_name')
            ),
            'password' => Hash::make($request->input('password')),
            'user_type' => $request->input('user_type'),
            'phone' => $request->input('phone') ?? '',
            // Columns below are NOT NULL without defaults; admin-created
            // accounts fill them in later from their profile page.
            'address_1' => '',
            'city' => '',
            'states' => '',
            'zip' => '',
            // Admin-created accounts skip the email verification flow.
            'email_verified_at' => now(),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'User created successfully',
                'data' => $newUser,
            ], 201);
        }

        return redirect()->route('admin.settings.users')
            ->with('success', 'User created successfully.');
    }

    /**
     * Build a unique username from a person's name, e.g. "ebenezer.mensah".
     * Collisions get a numeric suffix: ebenezer.mensah2, ebenezer.mensah3...
     */
    private function generateUsername(string $firstName, string $lastName): string
    {
        $base = Str::slug($firstName . ' ' . $lastName, '.');

        if ($base === '') {
            $base = 'user';
        }

        $username = $base;
        $suffix = 1;

        while (User::withTrashed()->where('username', $username)->exists()) {
            $suffix++;
            $username = $base . $suffix;
        }

        return $username;
    }

    /**
     * Update user
     */
    public function updateUser(Request $request, User $targetUser): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $user = $request->user();

        if (!$user->isAdmin()) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            abort(403);
        }

        $request->validate([
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $targetUser->id,
            'password' => ['sometimes', Password::defaults()],
            'user_type' => 'sometimes|in:user,dealer,inspector,staff,admin',
        ]);

        $data = $request->only(['first_name', 'last_name', 'email', 'user_type']);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->input('password'));
        }

        $targetUser->update($data);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'User updated successfully',
                'data' => $targetUser->fresh(),
            ]);
        }

        return redirect()->route('admin.settings.users')
            ->with('success', 'User updated successfully.');
    }

    /**
     * Delete user
     */
    public function deleteUser(Request $request, User $targetUser): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $user = $request->user();

        if (!$user->isAdmin()) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            abort(403);
        }

        // Prevent self-deletion
        if ($targetUser->id === $user->id) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete your own account',
                ], 422);
            }
            return redirect()->route('admin.settings.users')
                ->with('error', 'Cannot delete your own account.');
        }

        // Check if user has active cases
        if ($targetUser->cases()->active()->exists()) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete user with active cases',
                ], 422);
            }
            return redirect()->route('admin.settings.users')
                ->with('error', 'Cannot delete user with active cases.');
        }

        $targetUser->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully',
            ]);
        }

        return redirect()->route('admin.settings.users')
            ->with('success', 'User deleted successfully.');
    }

    /**
     * Notification settings
     */
    public function notifications(Request $request): View|JsonResponse
    {
        $user = $request->user();

        if (!$user->isStaff()) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            abort(403);
        }

        $notificationSettings = Setting::where('group', 'notifications')->get()->pluck('value', 'key');

        $data = [
            'settings' => $notificationSettings,
            'defaults' => [
                'email_enabled' => true,
                'sms_enabled' => false,
                'reminder_hours_before' => 24,
                'admin_email' => config('mail.from.address'),
            ],
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        }

        return view('admin.settings.notifications', $data);
    }

    /**
     * Update notification settings
     */
    public function updateNotifications(Request $request): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $user = $request->user();

        if (!$user->isAdmin()) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            abort(403);
        }

        $request->validate([
            'reminder_hours_before' => 'required|integer|min:1|max:72',
            'admin_email' => 'required|email',
        ]);

        // Checkboxes send 'on' when checked, nothing when unchecked
        Setting::set('email_enabled', $request->has('email_enabled'), 'notifications');
        Setting::set('sms_enabled', $request->has('sms_enabled'), 'notifications');
        Setting::set('reminder_hours_before', $request->input('reminder_hours_before'), 'notifications');
        Setting::set('admin_email', $request->input('admin_email'), 'notifications');

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Notification settings updated successfully',
            ]);
        }

        return redirect()->route('admin.settings.notifications')
            ->with('success', 'Notification settings updated successfully.');
    }

    /**
     * Get setting groups
     */
    protected function getSettingGroups(): array
    {
        return [
            'general' => [
                'label' => 'General Settings',
                'settings' => ['site_name', 'site_description', 'timezone'],
            ],
            'pricing' => [
                'label' => 'Pricing',
                'settings' => ['base_price', 'rush_fee', 'component_price', 'mobile_inspection_fee'],
            ],
            'notifications' => [
                'label' => 'Notifications',
                'settings' => ['email_enabled', 'sms_enabled', 'reminder_hours_before', 'admin_email'],
            ],
            'scheduling' => [
                'label' => 'Scheduling',
                'settings' => ['business_hours_start', 'business_hours_end', 'appointment_duration', 'max_daily_appointments'],
            ],
        ];
    }
}
