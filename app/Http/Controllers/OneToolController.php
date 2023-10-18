<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Facades\App\Services\Util;
use App\Enums\ErrorTypesEnum as ErrorTypes;
use App\Enums\SignupSourcesEnum;
use App\Enums\RolesEnum;
use App\Events\OneToolUserRegistered;
use App\Events\ProUserRegistered;
use App\User;
use App\OneToolUser;
use App\Plan;
use App\Role;
use DB;
use Log;
use Str;
use Arr;
use Validator;

class OneToolController extends Controller
{
    public function createUser(Request $request)
    {
        if (!$this->validateSecret($request)) {
            return response()->json([
                'message' => 'api-key header is missing or invalid',
            ], 400);
        }

        $data = $request->all();

        $validator = Validator::make($data, [
            'first_name' => 'required',
            'last_name' => 'required',
            'plan_id' => [
                'required',
                Rule::in(['basic_lite', 'pro_lite', 'pro'])
            ],
            'status' => 'required',
            'role_type' => 'required',
            'in_trial' => 'required|boolean',
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Invalid data submitted',
                'errors' => $validator->errors()->toArray()
            ], 400);
        }

        $user = User::where('email', $data['email'])->first();

        if (!empty($user)) {
            return response()->json([
                'message' => 'The email has already been taken.',
            ], 400);
        }

        try {
            DB::beginTransaction();

            $data = $request->all();

            $plan = Plan::where('onetool_plan_id', $data['plan_id'])->first();

            $user = User::create([
                'name'                 => $data['first_name'] . ' ' . $data['last_name'],
                'email'                => $data['email'],
                'password'             => bcrypt(Str::random(12)),
                'agree_terms'          => 0,
                'subscribe_newsletter' => 0,
                'source'               => SignupSourcesEnum::ONETOOL,
            ]);

            $user->active = 1;
            $user->save();

            $customerRole = Role::where('name', RolesEnum::CUSTOMER)->first();

            $user->roles()->attach($customerRole);

            $oneToolUser = OneToolUser::create([
                'first_name' => $data['first_name'],
                'last_name'  => $data['last_name'],
                'role_type'  => $data['role_type'],
                'in_trial'   => $data['in_trial'],
                'plan_id'    => $plan->id,
                'user_id'    => $user->id,
                'status'     => empty($data['status']) ? 'active' : $data['status'],
            ]);

            $oneToolUser->save();

            DB::commit();

            event(new OneToolUserRegistered($user));
        } catch (\Exception $e) {
            DB::rollback();

            app('sentry')->captureException($e);

            return response()->json([
                'message' => 'Unexpected internal error',
            ], 500);
        }

        return response()->json([
            'id' => $user->id
        ]);
    }

    public function loginUser(Request $request)
    {
        if (!$this->validateSecret($request)) {
            return response()->json([
                'message' => 'api-key header is missing or invalid',
            ], 400);
        }

        $id = $request->query('id');

        if (empty($id)) {
            return response()->json([
                'message' => 'id param is missing in the query string.'
            ], 400);
        }

        $user = User::find($id);

        if (empty($user)) {
            return response()->json([
                'message' => 'no record found with the id = ' . $id,
            ], 404);
        }

        if (!$user->isOneToolUser()) {
            return response()->json([
                'message' => 'user didn\'t signup from onetool',
            ], 400);
        }

        if (!$user->hasActiveOneToolSubscription()) {
            return response()->json([
                'message' => 'This user account is not active.',
            ], 400);
        }

        $token = auth()->login($user);

        if (empty($token)) {
            return response()->json([
                'message' => 'Unable to generate token for the user with id = ' . $id,
            ], 400);
        }

        return response()->json([
            'url' => Util::config('leadgen.client_app_token_login_url') . '?token=' . $token,
        ], 200);
    }

    public function getUser(Request $request)
    {
        try {
            if (!$this->validateSecret($request)) {
                return response()->json([
                    'message' => 'api-key header is missing or invalid',
                ], 400);
            }

            $id    = $request->query('id');
            $email = $request->query('email');

            if (empty($id) && empty($email)) {
                return response()->json([
                    'message' => 'id or email param is required in the query string.',
                ], 400);
            }

            $key   = 'id';
            $value = $id;

            if (empty($id)) {
                $key   = 'email';
                $value = $email;
            }

            $user = User::where($key, $value)->first();

            if (empty($user)) {
                return response()->json([
                    'message' => 'no record found for the given id or email',
                ], 404);
            }

            if (!$user->isOneToolUser()) {
                return response()->json([
                    'message' => 'This user didn\'t signup from onetool.',
                ], 401);
            }

            return response()->json($user->oneToolUser->toOneToolUserArray());
        } catch (\Exception $e) {
            app('sentry')->captureException($e);

            return response()->json([
                'message' => 'Unexpected internal error',
            ], 500);
        }
    }

    public function updateUser(Request $request)
    {
        try {
            if (!$this->validateSecret($request)) {
                return response()->json([
                    'message' => 'api-key header is missing or invalid',
                ], 400);
            }

            DB::beginTransaction();

            $data = $request->all();

            $validator = Validator::make($data, [
                'id' => 'required',
                'plan_id' => [
                    Rule::in(['basic_lite', 'pro_lite'])
                ],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Invalid data submitted',
                    'errors' => $validator->errors()->toArray()
                ], 400);
            }

            $addInActivecampaign = false;

            if (!empty($data['email'])) {
                $user = User::where([
                    ['email', '=', $data['email']],
                    ['id', '!=', $data['id']]
                ])->first();

                if (!empty($user)) {
                    return response()->json([
                        'message' => 'The email has already been taken.'
                    ], 400);
                }

                $addInActivecampaign = User::where([
                    ['email', '!=', $data['email']],
                    ['id', '=', $data['id']]
                ])->count() > 0;
            }

            $user = User::find($data['id']);

            if (empty($user)) {
                return response()->json([
                    'message' => 'Invalid user id.',
                ], 401);
            }

            if (!$user->isOneToolUser()) {
                return response()->json([
                    'message' => 'This user didn\'t signup from onetool.',
                ], 401);
            }

            $oneToolUser = $user->oneToolUser;

            if (!empty($data['email'])) {
                $user->email = $data['email'];
            }

            if (!empty($data['plan_id'])) {
                $plan = Plan::where('onetool_plan_id', $data['plan_id'])->first();

                $oneToolUser->plan_id = $plan->id;
            }

            if (!empty($data['first_name'])) {
                $oneToolUser->first_name = $data['first_name'];
                $user->name              = $data['first_name'];
            }

            if (!empty($data['last_name'])) {
                $oneToolUser->last_name = $data['last_name'];
                $user->name            .= ' ' . $data['last_name'];
                $user->name            = trim($user->name);
            }

            if (isset($data['in_trial'])) {
                $oneToolUser->in_trial = $data['in_trial'];
            }

            if (!empty($data['role_type'])) {
                $oneToolUser->role_type = $data['role_type'];
            }

            if (!empty($data['status'])) {
                $oneToolUser->status = $data['status'];
                $user->active        = $data['status'] === 'active';
            }

            $user->save();
            $oneToolUser->save();

            DB::commit();

            if ($addInActivecampaign) {
                event(new OneToolUserRegistered($user));
            }

            if (!$addInActivecampaign && $user->active && !$user->oneToolUser->in_trial) {
                event(new ProUserRegistered($user));
            }

            return response()->json([
                'message' => 'ok',
            ]);
        } catch (\Exception $e) {
            DB::rollback();

            app('sentry')->captureException($e);

            return response()->json([
                'message' => 'Unexpected internal error',
            ], 500);
        }
    }

    public function deleteUser(Request $request)
    {
        try {
            if (!$this->validateSecret($request)) {
                return response()->json([
                    'message' => 'api-key header is missing or invalid',
                ], 400);
            }

            DB::beginTransaction();

            $id = $request->query('id');

            if (empty($id)) {
                return response()->json([
                    'message' => 'id param is missing in the query string.'
                ], 400);
            }

            $user = User::find($id);

            if (empty($user)) {
                return response()->json([
                    'message' => 'no record found for user with id = ' . $id,
                ], 404);
            }

            if (!$user->isOneToolUser()) {
                return response()->json([
                    'message' => 'This user didn\'t signup from onetool.',
                ], 401);
            }

            $user->forceDelete();

            DB::commit();

            return response()->json([
                'message' => 'ok',
            ]);
        } catch (\Exception $e) {
            DB::rollback();

            app('sentry')->captureException($e);

            return response()->json([
                'message' => 'Unexpected internal error',
            ], 500);
        }
    }

    private function validateSecret(Request $request)
    {
        if (!$request->hasHeader('api-key')) {
            return false;
        }

        return $request->header('api-key') === config('leadgen.onetool_secret');
    }
}
