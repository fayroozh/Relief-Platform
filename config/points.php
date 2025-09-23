<?php
return [
    // نقاط لكل 1 وحدة عملة (TRY)
    'points_per_try' => env('POINTS_PER_TRY', 10), // 1 TRY => 10 نقاط => 1000 نقطة = 100 TRY

    // نسبة العمولة الكلية (3% = 0.03)
    'platform_fee_percent' => env('PLATFORM_FEE_PERCENT', 0.03),

    // من الـ platform_fee، كم يذهب لصندوق النقاط (مثال: 1/3 => 0.01)
    'points_percent' => env('PLATFORM_POINTS_PERCENT', 0.01),

    // الباقي من العمولة يذهب للدخل التشغيلي (مثال: 0.02)
    'system_percent' => env('PLATFORM_SYSTEM_PERCENT', 0.02),
];
