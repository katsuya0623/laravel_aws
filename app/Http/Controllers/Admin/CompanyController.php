<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyProfile;   // ダッシュボードの企業フォームで使っているテーブル
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function index()
    {
        // company_profiles から一覧を取得（まずは表示が出ることを優先）
        $companies = CompanyProfile::orderByDesc('updated_at')->paginate(20);
        return view('admin.companies.index', compact('companies'));
    }

    // ここから先は必要になったら追記（create/store/edit/update/destroy など）
}
