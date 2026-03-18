<?php

use Illuminate\Support\Facades\Schedule;

// تذكيرات أعياد الميلاد — كل يوم الساعة 8 صباحاً
Schedule::command('reminders:birthdays')
    ->dailyAt('08:00')
    ->withoutOverlapping()
    ->runInBackground();

// تذكيرات الزيارات المجدولة — كل يوم الساعة 9 صباحاً
Schedule::command('reminders:scheduled-visits')
    ->dailyAt('09:00')
    ->withoutOverlapping()
    ->runInBackground();

// تنبيهات المخدومين غير المزارين — كل جمعة الساعة 10 صباحاً
Schedule::command('reminders:unvisited')
    ->weeklyOn(5, '10:00')
    ->withoutOverlapping()
    ->runInBackground();
