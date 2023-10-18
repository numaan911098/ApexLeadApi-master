<?php

namespace App\Services;

use App\Models\Package;
use App\Models\FeatureProperty;
use App\Models\PlanFeature;
use App\Models\PlanFeatureProperty;
use Illuminate\Support\Facades\DB;
use Sentry;

class PackageBuilderService
{
    /**
     * @var Package
     */
    protected $packageModel;

    /**
     * @var FeatureProperty
     */
    protected $featurePropertyModel;

    /**
     * @var PlanFeature
     */
    protected $planFeatureModel;

    /**
     * @var PlanFeatureProperty
     */
    protected $planFeaturePropertyModel;

    /**
     * records per page
     */
    protected const PER_PAGE = 15;

    /**
     * PackageBuilderService constructor.
     * @param Package $packagebuilder
     * @param FeatureProperty $featureProperty
     * @param PlanFeature $planFeature,
     * @param PlanFeatureProperty $planFeatureProperty
     */
    public function __construct(
        Package $packagebuilder,
        FeatureProperty $featureProperty,
        PlanFeature $planFeature,
        PlanFeatureProperty $planFeatureProperty
    ) {
        $this->packageModel = $packagebuilder;
        $this->featurePropertyModel = $featureProperty;
        $this->planFeatureModel = $planFeature;
        $this->planFeaturePropertyModel = $planFeatureProperty;
    }

    /**
     * Get all packages.
     * @param array $data
     * @return array
     */
    public function getList(array $data): array
    {
        $packageBuilderQuery = $this->packageModel->with('plan');

        $sortField = $data['sortField'];
        $sortDirection = $data['sortDirection'];

        foreach ($data['search'] as $key => $value) {
            if (isset($key) && !empty($value)) {
                $packageBuilderQuery = $packageBuilderQuery->where($key, 'LIKE', '%' . $value . '%');
            }
        }

        $pagination = $packageBuilderQuery
            ->orderBy($sortField, $sortDirection)
            ->paginate(PackageBuilderService::PER_PAGE, ['*'], 'page', $data['page']);

        $packages = $pagination->items();
        $pagination = $pagination->toArray();
        unset($pagination['data']);

        return  [
            'data' => $packages,
            'pagination' => $pagination
        ];
    }

    /**
     * Get detail of particular package
     * @param int $id
     * @return null|object
     */
    public function getPackageData(int $id): ?Package
    {
        $package = $this->packageModel->with([
            'plan' => function ($query) use ($id) {
                $query->with(['planFeatures.featureProperties' => function ($query) use ($id) {
                    $query->join(
                        'plan_feature_properties',
                        'feature_properties.id',
                        '=',
                        'plan_feature_properties.feature_property_id'
                    );
                    $query->join(
                        'plan_features',
                        'plan_features.id',
                        '=',
                        'plan_feature_properties.plan_feature_id'
                    );
                    $query->where('plan_features.plan_id', '=', function ($query) use ($id) {
                        $query->select('plan_id')
                            ->from('packages')
                            ->where('id', $id);
                    });
                }]);
            }
        ])->find($id);

        return $package;
    }

    /**
     * Save particular package details
     * @param array $data
     * @return null|array
     */
    public function savePackageData(array $data): ?array
    {
        try {
            DB::beginTransaction();

            // Save package detail to the `packages` table
            $package = $this->packageModel->make();
            $package->title = $data['packageTitle'];
            $package->description = $data['packageDescription'];
            $package->plan_id = $data['packagePlan'];
            $package->save();

            // Prepare data to be saved to the `plan_features` and `plan_feature_properties` tables
            foreach ($data['packageData'] as $packageData) {
                $planFeature = $this->planFeatureModel->updateOrCreate(
                    ['plan_id' => $package->plan_id, 'feature_id' => $packageData['featureId']],
                    ['plan_id' => $package->plan_id, 'feature_id' => $packageData['featureId']]
                );

                $this->planFeaturePropertyModel->updateOrCreate(
                    ['plan_feature_id' => $planFeature->id, 'feature_property_id' => $packageData['propertyId']],
                    [
                        'plan_feature_id' => $planFeature->id,
                        'feature_property_id' => $packageData['propertyId'],
                        'value' => $packageData['propertyValue'],
                        'reset_period' => $packageData['propertyResetPeriod']
                    ]
                );
            }

            DB::commit();
            return $package->toArray();
        } catch (\Exception $e) {
            DB::rollback();
            Sentry\captureException($e);
            return null;
        }
    }

    /**
     * Update particular package details
     * @param array $data
     * @param int $packageId
     * @return null|array
     */
    public function updatePackageData(array $data, int $packageId): ?array
    {
        try {
            DB::beginTransaction();

            // Update package detail in the `packages` table
            $package = $this->packageModel->findOrFail($packageId);
            $package->title = $data['packageTitle'];
            $package->description = $data['packageDescription'];
            $package->save();

            // Delete existing package details from `plan_features` and `plan_feature_properties` tables
            $planFeatureIds = $this->planFeatureModel
                ->where('plan_id', $package->plan_id)
                ->pluck('id');

            $this->planFeaturePropertyModel
                ->whereIn('plan_feature_id', $planFeatureIds)
                ->delete();

            $this->planFeatureModel
                ->whereIn('id', $planFeatureIds)
                ->delete();

            // Prepare data to be saved to the `plan_features` and `plan_feature_properties` tables
            foreach ($data['packageData'] as $packageData) {
                $planFeature = $this->planFeatureModel->updateOrCreate(
                    ['plan_id' => $package->plan_id, 'feature_id' => $packageData['featureId']],
                    ['plan_id' => $package->plan_id, 'feature_id' => $packageData['featureId']]
                );

                $this->planFeaturePropertyModel->updateOrCreate(
                    ['plan_feature_id' => $planFeature->id, 'feature_property_id' => $packageData['propertyId']],
                    [
                        'plan_feature_id' => $planFeature->id,
                        'feature_property_id' => $packageData['propertyId'],
                        'value' => $packageData['propertyValue'],
                        'reset_period' => $packageData['propertyResetPeriod']
                    ]
                );
            }

            DB::commit();
            return $package->toArray();
        } catch (\Exception $e) {
            DB::rollback();
            Sentry\captureException($e);
            return null;
        }
    }
}
