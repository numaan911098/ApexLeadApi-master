<?php

namespace App\Services\Lists;

use App\ExternalCheckout;

class ExternalCheckoutListService
{
    /**
     * @var ExternalCheckout
     */
    protected ExternalCheckout $externalCheckoutModel;

    /**
     * records per page
     */
    protected const PER_PAGE = 15;

    /**
     * ExternalCheckoutListService constructor.
     * @param ExternalCheckout $externalCheckout
     */
    public function __construct(
        ExternalCheckout $externalCheckout
    ) {
        $this->externalCheckoutModel = $externalCheckout;
    }

    /**
     * List all external checkouts.
     * @param array $data
     * @return array
     */
    public function getList(array $data): array
    {
        $checkoutsQuery = $this->externalCheckoutModel;
        $sortField = $data['sortField'];
        $sortDirection = $data['sortDirection'];

        foreach ($data['search'] as $key => $value) {
            if (isset($key) && !empty($value)) {
                $checkoutsQuery = $checkoutsQuery->where($key, 'LIKE', '%' . $value . '%');
            }
        }
        $pagination = $checkoutsQuery
            ->orderBy($sortField, $sortDirection)
            ->paginate(ExternalCheckoutListService::PER_PAGE, ['*'], 'page', $data['page']);

        $checkouts = $pagination->items();
        $pagination = $pagination->toArray();
        unset($pagination['data']);

        return  [
            'data' => $checkouts,
            'pagination' => $pagination
        ];
    }
}
