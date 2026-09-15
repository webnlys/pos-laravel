<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BusinessSettingRequest;
use App\Http\Resources\BusinessSettingResource;
use App\Models\BusinessSetting;
use Illuminate\Support\Facades\Storage;

class BusinessSettingController extends Controller
{
    public function show()
    {
        return new BusinessSettingResource($this->settings());
    }

    public function update(BusinessSettingRequest $request)
    {
        $settings = $this->settings();
        $data = $request->safe()->except('logo');

        if ($request->hasFile('logo')) {
            if ($settings->logo) {
                Storage::disk('public')->delete($settings->logo);
            }
            $data['logo'] = $request->file('logo')->store('logos', 'public');
        }

        $settings->fill($data)->save();

        return new BusinessSettingResource($settings);
    }

    private function settings(): BusinessSetting
    {
        return BusinessSetting::query()->firstOrCreate([], [
            'name' => config('app.name'),
        ]);
    }
}
