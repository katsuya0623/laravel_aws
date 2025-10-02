<?php
return [
    // ここに運営アカウントを列挙（後でENVでもOK）
    'admins' => [
        'katsuya@nibi.co.jp',
    ],
    // 企業アカ判定の暫定条件（既存データを尊重）
    // 例）usersにcompany_idがある/企業側フラグがある/メールドメインで判定 など
    'is_company_resolver' => [
        'by_column' => 'company_id',  // 存在すれば「企業側」とみなす（無ければ自動スキップ）
        'by_domain' => null,          // 例: 'example.co.jp' を入れるとそのドメインを企業側扱い
    ],
];
