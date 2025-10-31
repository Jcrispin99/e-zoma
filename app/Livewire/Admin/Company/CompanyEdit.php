<?php

namespace App\Livewire\Admin\Company;

use Livewire\Component;
use App\Models\Company;
use App\Models\UbigeoDepartment;
use App\Models\UbigeoProvince;
use App\Models\UbigeoDistrict;

class CompanyEdit extends Component
{
    public Company $company;

    public $departments = [];
    public $provinces = [];
    public $districts = [];

    public $department_id = null;
    public $province_id = null;
    public $district_id = null;

    public $provincesAll = [];
    public $districtsAll = [];

    public function mount(Company $company)
    {
        $this->company = $company;

        $this->departments = UbigeoDepartment::select('id', 'name')
            ->orderBy('name')
            ->get()
            ->toArray();

        $this->provincesAll = UbigeoProvince::select('id', 'name', 'department_id')
            ->orderBy('name')
            ->get()
            ->toArray();

        $this->districtsAll = UbigeoDistrict::select('id', 'name', 'province_id', 'department_id')
            ->orderBy('name')
            ->get()
            ->toArray();

        // Precargar selección desde la compañía
        $this->district_id = $this->company->district_id;
        if ($this->district_id) {
            $district = UbigeoDistrict::find($this->district_id);
            if ($district) {
                $this->province_id = $district->province_id;
                $this->department_id = $district->department_id;
            }
        }

        $this->syncProvinces();
        $this->syncDistricts();
    }

    public function updated($name, $value)
    {
        if ($name === 'department_id') {
            $this->province_id = null;
            $this->district_id = null;
            $this->syncProvinces();
            $this->districts = [];
        } elseif ($name === 'province_id') {
            $this->district_id = null;
            $this->syncDistricts();
        }
    }

    private function syncProvinces(): void
    {
        if (!$this->department_id) {
            $this->provinces = [];
            return;
        }

        $deptId = (string) $this->department_id;
        $this->provinces = array_values(array_filter($this->provincesAll, function ($p) use ($deptId) {
            return (string)($p['department_id']) === $deptId;
        }));
    }

    private function syncDistricts(): void
    {
        if (!$this->province_id) {
            $this->districts = [];
            return;
        }

        $provId = (string) $this->province_id;
        $this->districts = array_values(array_filter($this->districtsAll, function ($d) use ($provId) {
            return (string)($d['province_id']) === $provId;
        }));
    }

    public function render()
    {
        return view('livewire.admin.company.company-edit');
    }
}