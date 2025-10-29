<?php
// app/Policies/WorkHistoryPolicy.php

namespace App\Policies;

use App\Models\WorkHistory;
use Illuminate\Contracts\Auth\Authenticatable; // ★ 追加（User/Adminどちらでも可）

class WorkHistoryPolicy
{
    /**
     * 一覧閲覧可否
     */
    public function viewAny(Authenticatable $user): bool
    {
        // UserモデルにisAdminがある場合
        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return true;
        }

        // Adminモデルなど、管理者ガードでのログイン時
        if ($user instanceof \App\Models\Admin) {
            return true;
        }

        return false;
    }

    /**
     * 単体閲覧可否
     */
    public function view(Authenticatable $user, WorkHistory $record): bool
    {
        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return true;
        }

        if ($user instanceof \App\Models\Admin) {
            return true;
        }

        return false;
    }

    public function create(Authenticatable $user): bool { return false; }
    public function update(Authenticatable $user, WorkHistory $record): bool { return false; }
    public function delete(Authenticatable $user, WorkHistory $record): bool { return false; }
    public function restore(Authenticatable $user, WorkHistory $record): bool { return false; }
    public function forceDelete(Authenticatable $user, WorkHistory $record): bool { return false; }
}
