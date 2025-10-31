# Company Invitation Expiry（招待メール有効期限仕様）

## 概要
本ドキュメントは、**企業ユーザー招待メールの有効期限設定** と  
**テスト検証（短縮検証）および復旧手順** を記録するもの。

この内容だけで、運用・再現・復旧すべてを把握できる構成。

---

## 現行仕様（本番環境）

### 有効期限設定
- **有効期限**：7日間  
- **対象ファイル**：`app/Http/Controllers/Admin/CompanyInvitationController.php`  
- **該当箇所（抜粋）**
  ```php
  // ③ 招待レコード
  $expiresDays = 7; // ← メール文面などでも使用
  $invitation = CompanyInvitation::create([
      'email'        => $data['email'],
      'company_name' => $data['company_name'],
      'company_id'   => $company->id,
      'token'        => (string) Str::uuid(),
      'expires_at'   => now()->addDays($expiresDays), // ← 本番仕様（7日）
      'status'       => 'pending',
      'invited_by'   => $request->user()?->id,
  ]);


## テスト検証
- **有効期限**：1分  

---
