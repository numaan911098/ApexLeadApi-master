<?php

namespace App\Services\Lists;

use App\User;
use App\Enums\RolesEnum;
use App\Modules\Security\Services\AuthService;

class UserListService
{
    /**
     * @var User
     */
    protected User $userModel;

    /**
     * AuthService instance.
     */
    protected AuthService $authService;

    /**
     * records per page
     */
    protected const PER_PAGE = 15;

    /**
     * UserListService constructor.
     * @param User $user
     * @param AuthService $authService
     */
    public function __construct(User $user, AuthService $authService)
    {
        $this->userModel = $user;
        $this->authService = $authService;
    }

    /**
     * List all users.
     * @param array $data
     * @return array
     */
    public function getList(array $data): array
    {
        $query = $this->userModel->withCount('forms', 'userLeads')
            ->with(['roles', 'oneToolUser'])
            ->whereHas('roles', function ($query) {
                $query->whereNotIn('name', [RolesEnum::ADMIN]);
            });

        $query = $query->orWhere('users.id', $this->authService->getUser());

        $sortField = $data['sortField'];
        $sortDirection = $data['sortDirection'];
        $userData = $this->userModel->withCount('userLeads', 'forms')
            ->with(['roles', 'oneToolUser']);

        foreach ($data['search'] as $key => $value) {
            if (isset($key) && !empty($value)) {
                $usersPagination = $userData
                    ->where('users.id', '!=', $this->authService->getUserId())
                    ->where($key, 'LIKE', '%' . trim($value) . '%')
                    ->orWhere($key, 'LIKE', '%' . trim($value) . '%')
                    ->paginate(UserListService::PER_PAGE, ['*'], 'page', $data['page']);
            }
        }

        $usersPagination = $userData
            ->where('users.id', '!=', $this->authService->getUserId())
            ->orderBy($sortField, $sortDirection)
            ->paginate(UserListService::PER_PAGE, ['*'], 'page', $data['page']);

        $users = $usersPagination->items();

        foreach ($users as $user) {
            $user->plan = $user->plan();
        }

        $pagination = $usersPagination->toArray();

        unset($pagination['data']);

        return [
            'data' => $users,
            'pagination' => $pagination
        ];
    }
}
