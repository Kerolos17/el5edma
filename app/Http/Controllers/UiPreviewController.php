<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class UiPreviewController extends Controller
{
    public function servant(): View|RedirectResponse
    {
        if (! Auth::check()) {
            return redirect()->route('filament.admin.auth.login');
        }

        return view('ui-preview.servant', $this->demoData());
    }

    public function fullDemo(): View|RedirectResponse
    {
        if (! Auth::check()) {
            return redirect()->route('filament.admin.auth.login');
        }

        return view('ui-preview.full-demo', $this->demoData());
    }

    private function demoData(): array
    {
        return [
            'servant' => [
                'name'     => 'مينا سامح',
                'greeting' => 'صباح الخدمة الجميل',
                'team'     => 'أسرة مارمرقس',
            ],
            'familyLeader' => [
                'name'  => 'ماريان فادي',
                'team'  => 'أسرة مارمرقس',
                'focus' => 'متابعة يومية أسرع وقرارات أوضح',
            ],
            'stats' => [
                ['label' => 'مخدوم في مجموعتي', 'value' => '24'],
                ['label' => 'زيارات اليوم', 'value' => '3'],
                ['label' => 'حالات متابعة', 'value' => '5'],
                ['label' => 'تنبيهات مهمة', 'value' => '2'],
            ],
            'quickActions' => [
                ['label' => 'سجل زيارة', 'icon' => 'plus'],
                ['label' => 'قائمة المخدومين', 'icon' => 'people'],
                ['label' => 'الزيارات المجدولة', 'icon' => 'calendar'],
                ['label' => 'الإشعارات', 'icon' => 'bell'],
            ],
            'beneficiaries' => [
                [
                    'name'         => 'مريم نبيل',
                    'status'       => 'نشط',
                    'lastVisit'    => 'منذ 3 أيام',
                    'priority'     => 'تحتاج متابعة',
                    'priorityTone' => 'gold',
                    'note'         => 'أقرب زيارة مساء الخميس',
                ],
                [
                    'name'         => 'يوسف عادل',
                    'status'       => 'حالة حساسة',
                    'lastVisit'    => 'منذ 8 أيام',
                    'priority'     => 'أولوية عالية',
                    'priorityTone' => 'red',
                    'note'         => 'الأسرة تنتظر رد سريع',
                ],
                [
                    'name'         => 'سارة مجدي',
                    'status'       => 'نشط',
                    'lastVisit'    => 'أمس',
                    'priority'     => 'عيد ميلاد قريب',
                    'priorityTone' => 'teal',
                    'note'         => 'اقتراح مكالمة قصيرة',
                ],
            ],
            'schedule' => [
                ['time' => '10:00', 'title' => 'زيارة منزلية', 'person' => 'مريم نبيل'],
                ['time' => '12:30', 'title' => 'مكالمة متابعة', 'person' => 'يوسف عادل'],
                ['time' => '05:00', 'title' => 'تأكيد الموعد', 'person' => 'سارة مجدي'],
            ],
            'alerts' => [
                ['title' => 'يوسف يحتاج تدخل سريع', 'body' => 'تم تسجيل ملاحظة حرجة من آخر زيارة.', 'tone' => 'red'],
                ['title' => 'زيارة مريم متأخرة', 'body' => 'آخر زيارة مضى عليها 8 أيام.', 'tone' => 'gold'],
            ],
            'timeline' => [
                ['time' => 'منذ 3 أيام', 'title' => 'زيارة منزلية', 'body' => 'الحالة النفسية مستقرة وتم الاتفاق على متابعة يوم الخميس.'],
                ['time' => 'منذ أسبوع', 'title' => 'طلب صلاة', 'body' => 'تم تسجيل طلب صلاة للأسرة مع إشعار أمين الأسرة.'],
                ['time' => 'منذ 10 أيام', 'title' => 'ملاحظة صحية', 'body' => 'تحديث بسيط في المتابعة الطبية ويحتاج مراجعة في الزيارة القادمة.'],
            ],
            'visitSteps' => [
                ['title' => 'اختيار المخدوم', 'hint' => 'ابحث بالاسم أو اختر من قائمة المجموعة'],
                ['title' => 'تفاصيل الزيارة', 'hint' => 'نوع الزيارة والتاريخ والوقت'],
                ['title' => 'الحالة والمتابعة', 'hint' => 'الحالة الحالية والملاحظات المهمة'],
                ['title' => 'تأكيد وحفظ', 'hint' => 'راجع المعلومات ثم احفظ الزيارة'],
            ],
            'familyBoard' => [
                'today' => [
                    ['label' => 'حالات تحتاج قرار', 'value' => '4'],
                    ['label' => 'متابعة هذا الأسبوع', 'value' => '11'],
                    ['label' => 'زيارات متأخرة', 'value' => '3'],
                ],
                'queues' => [
                    [
                        'title' => 'قرارات مطلوبة الآن',
                        'items' => [
                            'يوسف عادل: زيارة متأخرة وتحتاج قرار سريع',
                            'مريم نبيل: متابعة أسرية هذا الأسبوع',
                            'سارة مجدي: تنبيه عيد ميلاد ومكالمة قصيرة',
                        ],
                    ],
                    [
                        'title' => 'الخدام الأكثر نشاطًا',
                        'items' => [
                            'مينا سامح - 5 زيارات',
                            'أندرو مجدي - 4 زيارات',
                            'كارولين شريف - 3 زيارات',
                        ],
                    ],
                ],
            ],
        ];
    }
}
