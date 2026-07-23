<?php

// Phase 20: Filament v4 action label fallback 翻译
// Filament 4 CreateAction::getLabel() 走 `__("Create")` 这种顶层 key (无 namespace) —
// 我们的 zh-CN 短横 locale 会找 lang/zh-CN/，所以这里加一份。

return [
    // Phase 20: Filament v4 action label 顶层 fallback (无 namespace) — HasLabel.php line 56
    // 实际 Filament action name 转 Title Case: "create" → "Create", "save" → "Save" 等
    'Create' => '保存',
    'Save' => '保存',
    'Save changes' => '保存',
    'Create & create another' => '保存并创建另一个',
    'Delete' => '删除',
    'Cancel' => '取消',
    'Back' => '返回',
    'Confirm' => '确认',
    'Submit' => '提交',
    'Reset' => '重置',
    'View' => '查看',
    'Edit' => '编辑',
    'Replicate' => '复制',
    'Import' => '导入',
    'Export' => '导出',
    'Force delete' => '强制删除',
    'Restore' => '恢复',
    'Attach' => '关联',
    'Detach' => '解除关联',
    'Associate' => '关联',
    'Dissociate' => '解除关联',
    'New :label' => '创建 :label',
    'Edit :label' => '编辑 :label',
    'create' => '保存',
    'create_another' => '保存并创建另一个',
    'create & create another' => '保存并创建另一个',
    'edit' => '编辑',
    'save' => '保存',
    'delete' => '删除',
    'cancel' => '取消',
    'back' => '返回',
    'confirm' => '确认',
    'submit' => '提交',
    'reset' => '重置',
    'view' => '查看',
    'replicate' => '复制',
    'import' => '导入',
    'export' => '导出',
    'force_delete' => '强制删除',
    'restore' => '恢复',
    'attach' => '关联',
    'detach' => '解除关联',
    'associate' => '关联',
    'dissociate' => '解除关联',
];
