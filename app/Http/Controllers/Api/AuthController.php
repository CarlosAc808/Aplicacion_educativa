<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Subject;
use App\Models\Activity;
use Stripe\StripeClient;

class AuthController extends Controller
{
    private function refreshedUser(User $user): User
    {
        if (! $user->is_admin && ! $user->has_paid_lives) {
            $startedAt = $user->lives_reset_at ?: now();
            $minutes = $startedAt->diffInMinutes(now());
            $periods = (int) floor($minutes / 5);
            $newLives = min(7, $user->lives + $periods);

            if ($periods > 0 || ! $user->lives_reset_at) {
                $user->forceFill([
                    'lives' => $newLives,
                    'lives_reset_at' => $periods > 0 ? $startedAt->addMinutes($periods * 5) : $startedAt,
                ])->save();
            }
        }

        return $user->fresh();
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ])->validate();

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'message' => 'Las credenciales no son válidas.',
            ], 401);
        }

        return response()->json([
            'token' => $user->createToken('quest-academy')->plainTextToken,
            'user' => $this->refreshedUser($user),
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        $user = User::create($data);

        return response()->json(['message' => 'Cuenta creada correctamente.'], 201);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $this->refreshedUser($request->user())]);
    }

    public function loseLife(Request $request): JsonResponse
    {
        $user = $this->refreshedUser($request->user());
        if ($user->is_admin) {
            return response()->json(['user' => $user]);
        }
        if ($user->lives < 1) {
            return response()->json(['message' => 'Te quedaste sin vidas.', 'user' => $user], 429);
        }
        $user->decrement('lives');
        return response()->json(['user' => $this->refreshedUser($user)]);
    }

    public function createCheckoutSession(Request $request): JsonResponse
    {
        abort_if(blank(config('services.stripe.secret')), 503, 'Stripe no está configurado.');
        $user = $this->refreshedUser($request->user());
        $stripe = new StripeClient(config('services.stripe.secret'));
        $frontendUrl = rtrim(config('app.frontend_url'), '/');
        $session = $stripe->checkout->sessions->create([
            'mode' => 'payment',
            'client_reference_id' => (string) $user->id,
            'customer_email' => $user->email,
            'line_items' => [[
                'price_data' => [
                    'currency' => config('services.stripe.currency', 'usd'),
                    'product_data' => ['name' => 'Quest Academy Premium'],
                    'unit_amount' => (int) config('services.stripe.price_cents', 12000),
                ],
                'quantity' => 1,
            ]],
            'success_url' => $frontendUrl . '/payment-return.html?stripe_session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $frontendUrl . '/payment-return.html?cancelled=1',
            'metadata' => ['user_id' => (string) $user->id],
        ]);

        return response()->json(['url' => $session->url]);
    }

    public function confirmCheckout(Request $request): JsonResponse
    {
        $data = $request->validate(['session_id' => ['required', 'string']]);
        $user = $this->refreshedUser($request->user());
        $stripe = new StripeClient(config('services.stripe.secret'));
        $session = $stripe->checkout->sessions->retrieve($data['session_id']);
        abort_unless((string) $session->client_reference_id === (string) $user->id && $session->payment_status === 'paid', 422, 'Pago no confirmado.');

        if ($user->last_stripe_session_id !== $session->id) {
            $user->forceFill([
                'lives' => 10,
                'has_paid_lives' => true,
                'lives_reset_at' => now(),
                'premium_until' => null,
                'last_stripe_session_id' => $session->id,
            ])->save();
        }

        return response()->json(['user' => $user->fresh()]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $user->update($data);

        return response()->json(['user' => $user->fresh()]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Sesión cerrada correctamente.']);
    }

    public function updateProgress(Request $request): JsonResponse
    {
        $progress = $request->validate([
            'xp' => ['required', 'integer', 'min:0'],
            'completed_missions' => ['required', 'integer', 'min:0'],
            'streak' => ['required', 'integer', 'min:0'],
        ]);

        $request->user()->update($progress);

        return response()->json(['user' => $request->user()->fresh()]);
    }

    public function ranking(): JsonResponse
    {
        $ranking = User::query()
            ->select(['id', 'name', 'xp', 'completed_missions'])
            ->orderByDesc('xp')
            ->orderByDesc('completed_missions')
            ->orderBy('name')
            ->get()
            ->values()
            ->map(fn (User $user, int $index) => [
                'rank' => $index + 1,
                'id' => $user->id,
                'name' => $user->name,
                'xp' => $user->xp,
                'completed_missions' => $user->completed_missions,
            ]);

        return response()->json(['ranking' => $ranking]);
    }

    public function subjects(): JsonResponse
    {
        return response()->json(['subjects' => Subject::with('activities')->get()]);
    }

    public function storeSubject(Request $request): JsonResponse
    {
        $subject = Subject::create($request->validate([
            'name' => ['required', 'string', 'max:120'], 'short' => ['required', 'string', 'max:40'],
            'icon' => ['nullable', 'string', 'max:20'], 'color' => ['required', 'string', 'max:30'],
            'description' => ['nullable', 'string'],
        ]));

        return response()->json(['subject' => $subject->load('activities')], 201);
    }

    public function updateSubject(Request $request, Subject $subject): JsonResponse
    {
        $subject->update($request->validate([
            'name' => ['required', 'string', 'max:120'], 'short' => ['required', 'string', 'max:40'],
            'icon' => ['nullable', 'string', 'max:20'], 'color' => ['required', 'string', 'max:30'],
            'description' => ['nullable', 'string'],
        ]));

        return response()->json(['subject' => $subject->load('activities')]);
    }

    public function destroySubject(Subject $subject): JsonResponse
    {
        $subject->delete();
        return response()->json(['message' => 'Materia eliminada.']);
    }

    public function storeActivity(Request $request, Subject $subject): JsonResponse
    {
        $activity = $subject->activities()->create($this->activityData($request));
        return response()->json(['activity' => $activity], 201);
    }

    public function updateActivity(Request $request, Activity $activity): JsonResponse
    {
        $activity->update($this->activityData($request));
        return response()->json(['activity' => $activity]);
    }

    public function destroyActivity(Activity $activity): JsonResponse
    {
        $activity->delete();
        return response()->json(['message' => 'Actividad eliminada.']);
    }

    private function activityData(Request $request): array
    {
        return $request->validate([
            'label' => ['required', 'string', 'max:120'], 'title' => ['required', 'string'],
            'hint' => ['nullable', 'string'], 'answers' => ['required', 'array', 'min:2'],
            'answers.*' => ['required', 'string'], 'correct' => ['required', 'integer', 'min:0'],
            'icon' => ['nullable', 'string', 'max:20'], 'format' => ['required', 'string', 'max:30'],
            'format_type' => ['required', 'string', 'max:30'], 'instruction' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
    }
}