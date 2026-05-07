<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Beneficiary;
use App\Models\ServiceGroup;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BeneficiarySeeder extends Seeder
{
    public function run(): void
    {
        // Clear previous dev data before re-seeding
        $isSqlite = DB::getDriverName() === 'sqlite';
        if ($isSqlite) {
            DB::statement('PRAGMA foreign_keys = OFF');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        }

        foreach (['prayer_requests', 'medical_files', 'medications', 'scheduled_visits', 'visit_servants', 'visits', 'beneficiaries'] as $table) {
            DB::table($table)->truncate();
        }

        if ($isSqlite) {
            DB::statement('PRAGMA foreign_keys = ON');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        $groups   = ServiceGroup::with('leader')->get()->keyBy('name');
        $servants = User::where('role', 'servant')->get()->keyBy('email');
        $admin    = User::where('email', 'admin@ministry.local')->first();

        if ($groups->isEmpty() || $servants->isEmpty()) {
            $this->command->error('❌ Run ServiceGroupSeeder first.');
            return;
        }

        $group1 = $groups['أسرة النور'];
        $group2 = $groups['أسرة الرجاء'];
        $group3 = $groups['أسرة الفرح'];

        $s1 = $servants['servant@ministry.local'];   // كيرلس
        $s2 = $servants['servant2@ministry.local'];  // مارك
        $s3 = $servants['servant3@ministry.local'];  // صموئيل
        $s4 = $servants['servant4@ministry.local'];  // ماريان
        $s5 = $servants['servant5@ministry.local'];  // ميلاد
        $s6 = $servants['servant6@ministry.local'];  // ريهام

        // ═══════════════════════════════════════════════════════════════════════
        // أسرة النور — 10 مخدومين
        // ═══════════════════════════════════════════════════════════════════════
        $nourBeneficiaries = [
            // كيرلس (s1) — 5 مخدومين
            [
                'full_name'         => 'بطرس ماهر إبراهيم',
                'gender'            => 'male',
                'birth_date'        => '2008-03-15',
                'phone'             => '01012345678',
                'address_text'      => 'مصر الجديدة، شارع أحمد تيسير، بجوار المسجد الكبير',
                'area'              => 'مصر الجديدة',
                'governorate'       => 'القاهرة',
                'financial_status'  => 'poor',
                'status'            => 'active',
                'guardian_name'     => 'ماهر إبراهيم',
                'guardian_phone'    => '01098765432',
                'guardian_relation' => 'والد',
                'father_status'     => 'alive',
                'mother_status'     => 'deceased',
                'siblings_count'    => 3,
                'siblings_note'     => 'أخوه الأكبر يعمل في محل بقالة',
                'health_status'     => 'يعاني من أنيميا مزمنة، يحتاج متابعة دورية',
                'disability_type'   => null,
                'disability_degree' => null,
                'assigned_servant_id' => $s1->id,
                'service_group_id'    => $group1->id,
                'created_by'          => $s1->id,
                'visits' => [
                    ['type' => 'home_visit',     'beneficiary_status' => 'needs_follow', 'days_ago' => 5,  'feedback' => 'الطفل يحتاج دعم مادي لشراء الكتب المدرسية، والدته توفيت منذ عام'],
                    ['type' => 'phone_call',     'beneficiary_status' => 'good',         'days_ago' => 20, 'feedback' => 'أخبرني أن الأوضاع تتحسن قليلاً'],
                    ['type' => 'church_meeting', 'beneficiary_status' => 'great',        'days_ago' => 45, 'feedback' => 'حضر ولأول مرة الاجتماع الشبابي وسعيد'],
                ],
            ],
            [
                'full_name'         => 'أم النور فارس',
                'gender'            => 'female',
                'birth_date'        => '1955-07-22',
                'phone'             => '01123456789',
                'address_text'      => 'مصر الجديدة، شارع المطار القديم',
                'area'              => 'مصر الجديدة',
                'governorate'       => 'القاهرة',
                'financial_status'  => 'moderate',
                'status'            => 'active',
                'guardian_name'     => null,
                'father_status'     => 'deceased',
                'mother_status'     => 'deceased',
                'siblings_count'    => 2,
                'health_status'     => 'ضغط دم وسكر تحت السيطرة',
                'doctor_name'       => 'د. هاني سمير',
                'disability_type'   => null,
                'disability_degree' => null,
                'assigned_servant_id' => $s1->id,
                'service_group_id'    => $group1->id,
                'created_by'          => $s1->id,
                'visits' => [
                    ['type' => 'home_visit', 'beneficiary_status' => 'good',    'days_ago' => 3,  'feedback' => 'تبدو بخير، طلبت تذكرة للطبيب الشهر القادم'],
                    ['type' => 'home_visit', 'beneficiary_status' => 'good',    'days_ago' => 35, 'feedback' => 'زرتها مع الأسرة، كانت فرحانة بزيارتنا'],
                    ['type' => 'phone_call', 'beneficiary_status' => 'needs_follow', 'days_ago' => 60, 'feedback' => 'أبلغتني أن الطبيب قرر تغيير العلاج'],
                ],
                'medications' => [
                    ['name' => 'ميتفورمين 500mg', 'dosage' => 'مرتين يومياً', 'frequency' => 2, 'timing' => 'with_food', 'is_active' => true],
                    ['name' => 'أملوديبين 5mg',   'dosage' => 'مرة يومياً',    'frequency' => 1, 'timing' => 'morning',   'is_active' => true],
                ],
            ],
            [
                'full_name'         => 'رامي سمير غالي',
                'gender'            => 'male',
                'birth_date'        => '1995-11-08',
                'phone'             => '01234567890',
                'address_text'      => 'الزيتون، شارع النزهة',
                'area'              => 'الزيتون',
                'governorate'       => 'القاهرة',
                'financial_status'  => 'poor',
                'status'            => 'active',
                'father_status'     => 'deceased',
                'mother_status'     => 'alive',
                'siblings_count'    => 4,
                'health_status'     => 'سليم ظاهرياً لكن يعاني من اكتئاب',
                'disability_type'   => null,
                'disability_degree' => null,
                'assigned_servant_id' => $s1->id,
                'service_group_id'    => $group1->id,
                'created_by'          => $admin->id,
                'visits' => [
                    ['type' => 'home_visit', 'beneficiary_status' => 'needs_follow', 'is_critical' => true,  'days_ago' => 7,  'feedback' => 'حالته النفسية سيئة جداً، يحتاج متابعة عاجلة من أمين الأسرة'],
                    ['type' => 'phone_call', 'beneficiary_status' => 'needs_follow', 'days_ago' => 25, 'feedback' => 'رفض الرد في البداية ثم تكلم قليلاً'],
                ],
            ],
            [
                'full_name'         => 'مريم يوسف منصور',
                'gender'            => 'female',
                'birth_date'        => '2005-04-18',
                'phone'             => '01145678901',
                'address_text'      => 'عين شمس، شارع التحرير الفرعي',
                'area'              => 'عين شمس',
                'governorate'       => 'القاهرة',
                'financial_status'  => 'moderate',
                'status'            => 'active',
                'guardian_name'     => 'يوسف منصور',
                'guardian_phone'    => '01056789012',
                'guardian_relation' => 'والد',
                'father_status'     => 'alive',
                'mother_status'     => 'alive',
                'siblings_count'    => 1,
                'disability_type'   => 'بصرية',
                'disability_degree' => 'moderate',
                'health_status'     => 'ضعف شديد في النظر، ترتدي نظارة طبية',
                'assigned_servant_id' => $s1->id,
                'service_group_id'    => $group1->id,
                'created_by'          => $s1->id,
                'visits' => [
                    ['type' => 'church_meeting', 'beneficiary_status' => 'great', 'days_ago' => 10, 'feedback' => 'حضرت قداس الأحد وبدت سعيدة'],
                    ['type' => 'home_visit',     'beneficiary_status' => 'good',  'days_ago' => 40, 'feedback' => 'الأسرة محتاجة مساعدة في شراء النظارة الجديدة'],
                ],
                'prayer_requests' => [
                    ['title' => 'شفاء البصر', 'body' => 'تحتاج لعملية جراحية لتحسين النظر، نطلب الصلاة والمساعدة المادية'],
                ],
            ],
            [
                'full_name'         => 'داود حنا زكريا',
                'gender'            => 'male',
                'birth_date'        => '1978-09-30',
                'phone'             => '01267890123',
                'address_text'      => 'المطرية، شارع المصانع',
                'area'              => 'المطرية',
                'governorate'       => 'القاهرة',
                'financial_status'  => 'poor',
                'status'            => 'active',
                'father_status'     => 'deceased',
                'mother_status'     => 'alive',
                'siblings_count'    => 6,
                'disability_type'   => 'جسدية',
                'disability_degree' => 'severe',
                'health_status'     => 'فاقد الذراع الأيسر نتيجة حادث عمل منذ 10 سنوات',
                'assigned_servant_id' => $s1->id,
                'service_group_id'    => $group1->id,
                'created_by'          => $admin->id,
                'visits' => [
                    ['type' => 'home_visit', 'beneficiary_status' => 'needs_follow', 'days_ago' => 12, 'feedback' => 'يعاني من عدم انتظام في الدخل، زوجته تعمل خياطة'],
                    ['type' => 'home_visit', 'beneficiary_status' => 'good',         'days_ago' => 50, 'feedback' => 'حالته أفضل بعد مساعدة الأسرة'],
                ],
            ],
            // مارك (s2) — 5 مخدومين
            [
                'full_name'         => 'إيرين نصحي فريد',
                'gender'            => 'female',
                'birth_date'        => '1962-01-14',
                'phone'             => '01378901234',
                'address_text'      => 'الوايلي، شارع صلاح الدين',
                'area'              => 'الوايلي',
                'governorate'       => 'القاهرة',
                'financial_status'  => 'poor',
                'status'            => 'active',
                'father_status'     => 'deceased',
                'mother_status'     => 'deceased',
                'siblings_count'    => 0,
                'health_status'     => 'ربو وحساسية مزمنة',
                'doctor_name'       => 'د. مجدي فوزي',
                'disability_type'   => null,
                'disability_degree' => null,
                'assigned_servant_id' => $s2->id,
                'service_group_id'    => $group1->id,
                'created_by'          => $s2->id,
                'visits' => [
                    ['type' => 'home_visit', 'beneficiary_status' => 'good',         'days_ago' => 8,  'feedback' => 'تحسنت حالتها، أخذت البخاخ الجديد'],
                    ['type' => 'phone_call', 'beneficiary_status' => 'needs_follow', 'days_ago' => 30, 'feedback' => 'تشكو من ضيق تنفس شديد هذه الأيام'],
                ],
                'medications' => [
                    ['name' => 'فينتولين بخاخ', 'dosage' => 'عند الحاجة', 'frequency' => 0, 'timing' => 'as_needed', 'is_active' => true],
                ],
            ],
            [
                'full_name'         => 'أبانوب فكري طنطاوي',
                'gender'            => 'male',
                'birth_date'        => '2010-06-25',
                'phone'             => '01489012345',
                'address_text'      => 'مصر الجديدة، شارع المريوطية',
                'area'              => 'مصر الجديدة',
                'governorate'       => 'القاهرة',
                'financial_status'  => 'moderate',
                'status'            => 'active',
                'guardian_name'     => 'فكري طنطاوي',
                'guardian_phone'    => '01590123456',
                'guardian_relation' => 'والد',
                'father_status'     => 'alive',
                'mother_status'     => 'alive',
                'siblings_count'    => 2,
                'disability_type'   => 'ذهنية',
                'disability_degree' => 'mild',
                'health_status'     => 'صعوبات تعلم خفيفة، يدرس في مدرسة عادية',
                'assigned_servant_id' => $s2->id,
                'service_group_id'    => $group1->id,
                'created_by'          => $s2->id,
                'visits' => [
                    ['type' => 'home_visit',     'beneficiary_status' => 'good',  'days_ago' => 15, 'feedback' => 'تقدم ملحوظ في المدرسة هذا الفصل'],
                    ['type' => 'church_meeting', 'beneficiary_status' => 'great', 'days_ago' => 28, 'feedback' => 'يشارك في فريق التسبيح بانتظام'],
                ],
                'prayer_requests' => [
                    ['title' => 'نجاح في الدراسة', 'body' => 'يواجه صعوبة في القراءة والكتابة، نطلب دعم الأسرة والصلاة'],
                ],
            ],
            [
                'full_name'         => 'سوزان عماد رزق',
                'gender'            => 'female',
                'birth_date'        => '1940-12-03',
                'phone'             => '01601234567',
                'address_text'      => 'الزيتون، بجوار كنيسة مارجرجس',
                'area'              => 'الزيتون',
                'governorate'       => 'القاهرة',
                'financial_status'  => 'poor',
                'status'            => 'active',
                'father_status'     => 'deceased',
                'mother_status'     => 'deceased',
                'siblings_count'    => 1,
                'health_status'     => 'مشاكل في القلب وفشل كلوي جزئي',
                'doctor_name'       => 'د. رمزي ميخائيل',
                'hospital_name'     => 'مستشفى معهد ناصر',
                'disability_type'   => 'جسدية',
                'disability_degree' => 'moderate',
                'assigned_servant_id' => $s2->id,
                'service_group_id'    => $group1->id,
                'created_by'          => $admin->id,
                'visits' => [
                    ['type' => 'home_visit', 'beneficiary_status' => 'critical', 'is_critical' => true, 'days_ago' => 2,  'feedback' => 'حالة صحية سيئة جداً، تنتظر دورها في غسيل الكلى، تحتاج تدخل عاجل'],
                    ['type' => 'home_visit', 'beneficiary_status' => 'needs_follow',                    'days_ago' => 20, 'feedback' => 'حالتها متذبذبة'],
                ],
                'medications' => [
                    ['name' => 'أسبرين 100mg',    'dosage' => 'مرة يومياً', 'frequency' => 1, 'timing' => 'morning',   'is_active' => true],
                    ['name' => 'فروسيميد 40mg',   'dosage' => 'مرة يومياً', 'frequency' => 1, 'timing' => 'morning',   'is_active' => true],
                    ['name' => 'أوميبرازول 20mg', 'dosage' => 'مرة يومياً', 'frequency' => 1, 'timing' => 'with_food', 'is_active' => true],
                ],
            ],
            [
                'full_name'         => 'ماهر جرجس يعقوب',
                'gender'            => 'male',
                'birth_date'        => '1985-08-11',
                'phone'             => '01712345678',
                'address_text'      => 'عين شمس، شارع التعاون الخلفي',
                'area'              => 'عين شمس',
                'governorate'       => 'القاهرة',
                'financial_status'  => 'moderate',
                'status'            => 'active',
                'father_status'     => 'alive',
                'mother_status'     => 'alive',
                'siblings_count'    => 3,
                'disability_type'   => null,
                'disability_degree' => null,
                'health_status'     => 'سليم',
                'assigned_servant_id' => $s2->id,
                'service_group_id'    => $group1->id,
                'created_by'          => $s2->id,
                'visits' => [
                    ['type' => 'church_meeting', 'beneficiary_status' => 'great', 'days_ago' => 7,  'feedback' => 'يقود فريق الشباب، نشيط جداً في الخدمة'],
                    ['type' => 'home_visit',     'beneficiary_status' => 'great', 'days_ago' => 45, 'feedback' => 'أسرة متماسكة، تعيش بشكل كريم'],
                ],
            ],
            [
                'full_name'         => 'فيبي كمال أسعد',
                'gender'            => 'female',
                'birth_date'        => '2012-02-28',
                'phone'             => '01823456789',
                'address_text'      => 'المطرية، شارع السلام',
                'area'              => 'المطرية',
                'governorate'       => 'القاهرة',
                'financial_status'  => 'poor',
                'status'            => 'active',
                'guardian_name'     => 'كمال أسعد',
                'guardian_phone'    => '01934567890',
                'guardian_relation' => 'والد',
                'father_status'     => 'alive',
                'mother_status'     => 'deceased',
                'siblings_count'    => 4,
                'siblings_note'     => 'أربعة أخوة، أكبرهم 16 سنة',
                'disability_type'   => null,
                'disability_degree' => null,
                'health_status'     => 'بصحة جيدة',
                'assigned_servant_id' => $s2->id,
                'service_group_id'    => $group1->id,
                'created_by'          => $s2->id,
                'visits' => [
                    ['type' => 'home_visit', 'beneficiary_status' => 'needs_follow', 'days_ago' => 18, 'feedback' => 'الأسرة تعاني بعد وفاة الأم، الأب يعمل بأجر يومي'],
                    ['type' => 'home_visit', 'beneficiary_status' => 'good',         'days_ago' => 60, 'feedback' => 'الأحوال أفضل نسبياً في هذا الشهر'],
                ],
            ],
        ];

        // ═══════════════════════════════════════════════════════════════════════
        // أسرة الرجاء — 10 مخدومين
        // ═══════════════════════════════════════════════════════════════════════
        $rajaBeneficiaries = [
            [
                'full_name'         => 'مينا ثروت حداد',
                'gender'            => 'male',
                'birth_date'        => '1970-05-19',
                'phone'             => '01045678901',
                'address_text'      => 'شبرا، شارع المنيل الفرعي',
                'area'              => 'شبرا',
                'governorate'       => 'القاهرة',
                'financial_status'  => 'poor',
                'status'            => 'active',
                'father_status'     => 'deceased',
                'mother_status'     => 'alive',
                'siblings_count'    => 5,
                'disability_type'   => 'جسدية',
                'disability_degree' => 'mild',
                'health_status'     => 'تعب في الركبتين، يصعب عليه السير لمسافات طويلة',
                'assigned_servant_id' => $s3->id,
                'service_group_id'    => $group2->id,
                'created_by'          => $s3->id,
                'visits' => [
                    ['type' => 'home_visit', 'beneficiary_status' => 'good',         'days_ago' => 6,  'feedback' => 'يحتاج كرسي متحرك خفيف للتنقل'],
                    ['type' => 'phone_call', 'beneficiary_status' => 'needs_follow', 'days_ago' => 22, 'feedback' => 'يشعر بإحباط بسبب عدم القدرة على العمل'],
                ],
                'prayer_requests' => [
                    ['title' => 'شفاء الركبتين', 'body' => 'يحتاج عملية جراحية مكلفة، يطلب الصلاة والمساعدة'],
                ],
            ],
            [
                'full_name'         => 'نانسي فؤاد عزيز',
                'gender'            => 'female',
                'birth_date'        => '1958-10-07',
                'phone'             => '01156789012',
                'address_text'      => 'شبرا الخيمة، شارع الأحرار',
                'area'              => 'شبرا الخيمة',
                'governorate'       => 'القاهرة',
                'financial_status'  => 'moderate',
                'status'            => 'active',
                'father_status'     => 'deceased',
                'mother_status'     => 'deceased',
                'siblings_count'    => 2,
                'disability_type'   => null,
                'disability_degree' => null,
                'health_status'     => 'سكر نوع ثاني تحت السيطرة',
                'doctor_name'       => 'د. ليلى منصور',
                'assigned_servant_id' => $s3->id,
                'service_group_id'    => $group2->id,
                'created_by'          => $s3->id,
                'visits' => [
                    ['type' => 'home_visit',     'beneficiary_status' => 'great', 'days_ago' => 9,  'feedback' => 'حالتها ممتازة وهي فرحانة بزيارتنا'],
                    ['type' => 'church_meeting', 'beneficiary_status' => 'great', 'days_ago' => 23, 'feedback' => 'تحضر القداس بانتظام كل أسبوع'],
                ],
                'medications' => [
                    ['name' => 'جلوكوفاج 850mg', 'dosage' => 'مرتين يومياً', 'frequency' => 2, 'timing' => 'with_food', 'is_active' => true],
                ],
            ],
            [
                'full_name'         => 'ميخائيل رمزي فهمي',
                'gender'            => 'male',
                'birth_date'        => '2003-07-16',
                'phone'             => '01267890123',
                'address_text'      => 'شبرا، حارة الديناميت',
                'area'              => 'شبرا',
                'governorate'       => 'القاهرة',
                'financial_status'  => 'poor',
                'status'            => 'active',
                'guardian_name'     => 'رمزي فهمي',
                'guardian_phone'    => '01378901234',
                'guardian_relation' => 'والد',
                'father_status'     => 'alive',
                'mother_status'     => 'alive',
                'siblings_count'    => 3,
                'disability_type'   => null,
                'disability_degree' => null,
                'health_status'     => 'بصحة جيدة',
                'assigned_servant_id' => $s3->id,
                'service_group_id'    => $group2->id,
                'created_by'          => $s3->id,
                'visits' => [
                    ['type' => 'home_visit', 'beneficiary_status' => 'good', 'days_ago' => 11, 'feedback' => 'شاب متعلم وطموح، يبحث عن فرصة عمل'],
                    ['type' => 'phone_call', 'beneficiary_status' => 'good', 'days_ago' => 35, 'feedback' => 'أخبرني أنه مسجل في معهد تقني'],
                ],
            ],
            [
                'full_name'         => 'كلير طارق مسيح',
                'gender'            => 'female',
                'birth_date'        => '2007-03-22',
                'phone'             => '01489012345',
                'address_text'      => 'المرج، شارع الوحدة',
                'area'              => 'المرج',
                'governorate'       => 'القاهرة',
                'financial_status'  => 'poor',
                'status'            => 'active',
                'guardian_name'     => 'طارق مسيح',
                'guardian_phone'    => '01590123456',
                'guardian_relation' => 'والد',
                'father_status'     => 'alive',
                'mother_status'     => 'deceased',
                'siblings_count'    => 2,
                'disability_type'   => 'ذهنية',
                'disability_degree' => 'moderate',
                'health_status'     => 'متلازمة داون، تحتاج عناية خاصة ودمج اجتماعي',
                'assigned_servant_id' => $s3->id,
                'service_group_id'    => $group2->id,
                'created_by'          => $admin->id,
                'visits' => [
                    ['type' => 'home_visit', 'beneficiary_status' => 'good',  'days_ago' => 14, 'feedback' => 'فرحانة جداً بالزيارة، تحب التفاعل مع الناس'],
                    ['type' => 'home_visit', 'beneficiary_status' => 'great', 'days_ago' => 55, 'feedback' => 'تشارك في أنشطة الكنيسة بانتظام'],
                ],
                'prayer_requests' => [
                    ['title' => 'دمج اجتماعي', 'body' => 'تحتاج بيئة آمنة وداعمة، نطلب الصلاة من أجل استقرارها النفسي'],
                ],
            ],
            [
                'full_name'         => 'فيليب صفوت بقطر',
                'gender'            => 'male',
                'birth_date'        => '1948-11-30',
                'phone'             => '01601234567',
                'address_text'      => 'شبرا، شارع الثورة القديم',
                'area'              => 'شبرا',
                'governorate'       => 'القاهرة',
                'financial_status'  => 'poor',
                'status'            => 'active',
                'father_status'     => 'deceased',
                'mother_status'     => 'deceased',
                'siblings_count'    => 1,
                'disability_type'   => 'جسدية',
                'disability_degree' => 'severe',
                'health_status'     => 'مقعد بالكامل نتيجة جلطة دماغية منذ 5 سنوات',
                'doctor_name'       => 'د. أسامة البدوي',
                'hospital_name'     => 'مستشفى القصر العيني',
                'assigned_servant_id' => $s3->id,
                'service_group_id'    => $group2->id,
                'created_by'          => $admin->id,
                'visits' => [
                    ['type' => 'home_visit', 'beneficiary_status' => 'needs_follow', 'is_critical' => true, 'days_ago' => 4,  'feedback' => 'يحتاج كرسي متحرك جديد عاجلاً، لا أحد يرعاه بشكل كافٍ'],
                    ['type' => 'home_visit', 'beneficiary_status' => 'needs_follow',                        'days_ago' => 30, 'feedback' => 'يعيش وحده بمساعدة جارته المسنة'],
                ],
                'medications' => [
                    ['name' => 'أسبرين 75mg',   'dosage' => 'مرة يومياً', 'frequency' => 1, 'timing' => 'morning', 'is_active' => true],
                    ['name' => 'كلوبيدوجريل',   'dosage' => 'مرة يومياً', 'frequency' => 1, 'timing' => 'morning', 'is_active' => true],
                    ['name' => 'أتورفاستاتين', 'dosage' => 'مرة يومياً', 'frequency' => 1, 'timing' => 'evening', 'is_active' => true],
                ],
            ],
            // ماريان (s4) — 5 مخدومين
            [
                'full_name'         => 'ساندرا إيهاب شنودة',
                'gender'            => 'female',
                'birth_date'        => '1990-04-05',
                'phone'             => '01712345678',
                'address_text'      => 'شبرا الخيمة، شارع السكة الحديد',
                'area'              => 'شبرا الخيمة',
                'governorate'       => 'القاهرة',
                'financial_status'  => 'moderate',
                'status'            => 'active',
                'father_status'     => 'alive',
                'mother_status'     => 'alive',
                'siblings_count'    => 2,
                'disability_type'   => null,
                'disability_degree' => null,
                'health_status'     => 'بصحة جيدة، ترعى أمها المريضة',
                'assigned_servant_id' => $s4->id,
                'service_group_id'    => $group2->id,
                'created_by'          => $s4->id,
                'visits' => [
                    ['type' => 'home_visit', 'beneficiary_status' => 'good', 'days_ago' => 16, 'feedback' => 'تبدو متعبة نفسياً من رعاية أمها، تحتاج دعم'],
                    ['type' => 'phone_call', 'beneficiary_status' => 'good', 'days_ago' => 40, 'feedback' => 'تسأل عن الأنشطة الاجتماعية للكنيسة'],
                ],
            ],
            [
                'full_name'         => 'جرجس عصام طلعت',
                'gender'            => 'male',
                'birth_date'        => '2015-08-17',
                'phone'             => '01823456789',
                'address_text'      => 'المرج الجديدة، شارع النصر',
                'area'              => 'المرج الجديدة',
                'governorate'       => 'القاهرة',
                'financial_status'  => 'poor',
                'status'            => 'active',
                'guardian_name'     => 'عصام طلعت',
                'guardian_phone'    => '01934567890',
                'guardian_relation' => 'والد',
                'father_status'     => 'alive',
                'mother_status'     => 'alive',
                'siblings_count'    => 5,
                'disability_type'   => null,
                'disability_degree' => null,
                'health_status'     => 'بصحة جيدة لكن يعاني من سوء تغذية',
                'assigned_servant_id' => $s4->id,
                'service_group_id'    => $group2->id,
                'created_by'          => $s4->id,
                'visits' => [
                    ['type' => 'home_visit', 'beneficiary_status' => 'needs_follow', 'days_ago' => 5,  'feedback' => 'الأسرة مكونة من 7 أفراد في غرفتين، وضع صعب جداً'],
                    ['type' => 'home_visit', 'beneficiary_status' => 'needs_follow', 'days_ago' => 42, 'feedback' => 'حضرنا بمساعدة غذائية، الأسرة ممتنة'],
                ],
            ],
            [
                'full_name'         => 'مارينا أيمن رفعت',
                'gender'            => 'female',
                'birth_date'        => '1975-12-09',
                'phone'             => '01045678902',
                'address_text'      => 'شبرا، شارع الشهيد عمر',
                'area'              => 'شبرا',
                'governorate'       => 'القاهرة',
                'financial_status'  => 'moderate',
                'status'            => 'active',
                'father_status'     => 'deceased',
                'mother_status'     => 'alive',
                'siblings_count'    => 3,
                'disability_type'   => null,
                'disability_degree' => null,
                'health_status'     => 'سليمة ظاهرياً',
                'assigned_servant_id' => $s4->id,
                'service_group_id'    => $group2->id,
                'created_by'          => $s4->id,
                'visits' => [
                    ['type' => 'church_meeting', 'beneficiary_status' => 'great', 'days_ago' => 3,  'feedback' => 'متحمسة وتساعد في تنظيم فعاليات الأسرة'],
                    ['type' => 'home_visit',     'beneficiary_status' => 'great', 'days_ago' => 33, 'feedback' => 'أسرتها متماسكة وملتزمة بالكنيسة'],
                ],
            ],
            [
                'full_name'         => 'بولا نبيل ظريف',
                'gender'            => 'female',
                'birth_date'        => '2009-01-20',
                'phone'             => '01156789013',
                'address_text'      => 'شبرا الخيمة، شارع كمال زكي',
                'area'              => 'شبرا الخيمة',
                'governorate'       => 'القاهرة',
                'financial_status'  => 'poor',
                'status'            => 'active',
                'guardian_name'     => 'نبيل ظريف',
                'guardian_phone'    => '01267890124',
                'guardian_relation' => 'والد',
                'father_status'     => 'deceased',
                'mother_status'     => 'alive',
                'siblings_count'    => 2,
                'disability_type'   => 'بصرية',
                'disability_degree' => 'severe',
                'health_status'     => 'كفيفة منذ الولادة، تحتاج تعليم خاص',
                'assigned_servant_id' => $s4->id,
                'service_group_id'    => $group2->id,
                'created_by'          => $admin->id,
                'visits' => [
                    ['type' => 'home_visit', 'beneficiary_status' => 'good',  'days_ago' => 13, 'feedback' => 'ملتحقة بمدرسة للمكفوفين، تقدم ممتاز'],
                    ['type' => 'home_visit', 'beneficiary_status' => 'great', 'days_ago' => 50, 'feedback' => 'تحفظ التسابيح وتشارك في الكنيسة'],
                ],
                'prayer_requests' => [
                    ['title' => 'استمرار التعليم', 'body' => 'تحتاج مساعدة مادية لاستمرار تعليمها في مدرسة المكفوفين'],
                ],
            ],
            [
                'full_name'         => 'ألبير رجا مقار',
                'gender'            => 'male',
                'birth_date'        => '1932-06-14',
                'phone'             => '01378901235',
                'address_text'      => 'شبرا، شارع الضاهر القديم',
                'area'              => 'شبرا',
                'governorate'       => 'القاهرة',
                'financial_status'  => 'poor',
                'status'            => 'active',
                'father_status'     => 'deceased',
                'mother_status'     => 'deceased',
                'siblings_count'    => 0,
                'disability_type'   => 'جسدية',
                'disability_degree' => 'severe',
                'health_status'     => 'شيخوخة متقدمة، زهايمر في مرحلة مبكرة',
                'doctor_name'       => 'د. نادية حلمي',
                'assigned_servant_id' => $s4->id,
                'service_group_id'    => $group2->id,
                'created_by'          => $admin->id,
                'visits' => [
                    ['type' => 'home_visit', 'beneficiary_status' => 'needs_follow', 'days_ago' => 9,  'feedback' => 'ذاكرته تتدهور، يحتاج من يعيش معه'],
                    ['type' => 'home_visit', 'beneficiary_status' => 'needs_follow', 'days_ago' => 38, 'feedback' => 'وجدناه وحيداً ولم يتذكرنا في البداية'],
                ],
            ],
        ];

        // ═══════════════════════════════════════════════════════════════════════
        // أسرة الفرح — 10 مخدومين
        // ═══════════════════════════════════════════════════════════════════════
        $farahBeneficiaries = [
            [
                'full_name'         => 'إيفلين رمزي مسعود',
                'gender'            => 'female',
                'birth_date'        => '1965-09-28',
                'phone'             => '01489012346',
                'address_text'      => 'الجيزة، شارع السودان، بلوك 15',
                'area'              => 'الجيزة',
                'governorate'       => 'الجيزة',
                'financial_status'  => 'poor',
                'status'            => 'active',
                'father_status'     => 'deceased',
                'mother_status'     => 'deceased',
                'siblings_count'    => 1,
                'disability_type'   => null,
                'disability_degree' => null,
                'health_status'     => 'ضغط دم مرتفع لم يُعالج بانتظام',
                'assigned_servant_id' => $s5->id,
                'service_group_id'    => $group3->id,
                'created_by'          => $s5->id,
                'visits' => [
                    ['type' => 'home_visit', 'beneficiary_status' => 'needs_follow', 'days_ago' => 10, 'feedback' => 'لا تأخذ دواء الضغط بانتظام بسبب التكلفة'],
                    ['type' => 'phone_call', 'beneficiary_status' => 'needs_follow', 'days_ago' => 27, 'feedback' => 'شكت من صداع متكرر وهي في حاجة لمتابعة طبية'],
                ],
                'medications' => [
                    ['name' => 'أتينولول 50mg', 'dosage' => 'مرة يومياً', 'frequency' => 1, 'timing' => 'morning', 'is_active' => true],
                ],
            ],
            [
                'full_name'         => 'صموئيل جرجس أمين',
                'gender'            => 'male',
                'birth_date'        => '2001-02-14',
                'phone'             => '01590123457',
                'address_text'      => 'الهرم، شارع الهرم الأول',
                'area'              => 'الهرم',
                'governorate'       => 'الجيزة',
                'financial_status'  => 'moderate',
                'status'            => 'active',
                'father_status'     => 'alive',
                'mother_status'     => 'alive',
                'siblings_count'    => 2,
                'disability_type'   => null,
                'disability_degree' => null,
                'health_status'     => 'بصحة جيدة',
                'assigned_servant_id' => $s5->id,
                'service_group_id'    => $group3->id,
                'created_by'          => $s5->id,
                'visits' => [
                    ['type' => 'church_meeting', 'beneficiary_status' => 'great', 'days_ago' => 6,  'feedback' => 'شاب نشيط، يساعد في الخدمة اجتماعياً'],
                    ['type' => 'home_visit',     'beneficiary_status' => 'great', 'days_ago' => 29, 'feedback' => 'أسرة منضبطة ومحبة للخدمة'],
                ],
            ],
            [
                'full_name'         => 'فرح يحنس منقريوس',
                'gender'            => 'female',
                'birth_date'        => '2013-11-05',
                'phone'             => '01601234568',
                'address_text'      => 'الدقي، شارع التحرير',
                'area'              => 'الدقي',
                'governorate'       => 'الجيزة',
                'financial_status'  => 'poor',
                'status'            => 'active',
                'guardian_name'     => 'يحنس منقريوس',
                'guardian_phone'    => '01712345679',
                'guardian_relation' => 'والد',
                'father_status'     => 'alive',
                'mother_status'     => 'deceased',
                'siblings_count'    => 3,
                'disability_type'   => 'ذهنية',
                'disability_degree' => 'mild',
                'health_status'     => 'تأخر في النطق، تخضع لجلسات علاج نطق',
                'assigned_servant_id' => $s5->id,
                'service_group_id'    => $group3->id,
                'created_by'          => $s5->id,
                'visits' => [
                    ['type' => 'home_visit', 'beneficiary_status' => 'good', 'days_ago' => 17, 'feedback' => 'تحسن في النطق ملحوظ جداً هذا الشهر'],
                    ['type' => 'home_visit', 'beneficiary_status' => 'good', 'days_ago' => 52, 'feedback' => 'الأب منتظم في اصطحابها للجلسات'],
                ],
            ],
            [
                'full_name'         => 'إسحق أندراوس دانيال',
                'gender'            => 'male',
                'birth_date'        => '1952-04-23',
                'phone'             => '01823456790',
                'address_text'      => 'الجيزة، شارع الفلاح',
                'area'              => 'الجيزة',
                'governorate'       => 'الجيزة',
                'financial_status'  => 'poor',
                'status'            => 'active',
                'father_status'     => 'deceased',
                'mother_status'     => 'deceased',
                'siblings_count'    => 3,
                'disability_type'   => 'جسدية',
                'disability_degree' => 'moderate',
                'health_status'     => 'بتر القدم اليسرى بسبب مضاعفات السكر',
                'doctor_name'       => 'د. فاطمة العجمي',
                'hospital_name'     => 'مستشفى جامعة القاهرة',
                'assigned_servant_id' => $s5->id,
                'service_group_id'    => $group3->id,
                'created_by'          => $admin->id,
                'visits' => [
                    ['type' => 'home_visit', 'beneficiary_status' => 'needs_follow', 'days_ago' => 8,  'feedback' => 'يحتاج اطراف صناعية والتغطية الطبية غير كافية'],
                    ['type' => 'home_visit', 'beneficiary_status' => 'needs_follow', 'days_ago' => 46, 'feedback' => 'حالة صحية تستدعي المتابعة المستمرة'],
                ],
                'medications' => [
                    ['name' => 'إنسولين نوفورابيد', 'dosage' => '3 جرعات يومياً', 'frequency' => 3, 'timing' => 'with_food', 'is_active' => true],
                    ['name' => 'إنسولين لانتوس',    'dosage' => 'مرة يومياً',        'frequency' => 1, 'timing' => 'evening',   'is_active' => true],
                ],
            ],
            [
                'full_name'         => 'كريستين وهبي إبراهيم',
                'gender'            => 'female',
                'birth_date'        => '1998-07-31',
                'phone'             => '01934567891',
                'address_text'      => 'العجوزة، شارع النيل الجانبي',
                'area'              => 'العجوزة',
                'governorate'       => 'الجيزة',
                'financial_status'  => 'moderate',
                'status'            => 'active',
                'father_status'     => 'alive',
                'mother_status'     => 'alive',
                'siblings_count'    => 1,
                'disability_type'   => null,
                'disability_degree' => null,
                'health_status'     => 'بصحة جيدة',
                'assigned_servant_id' => $s5->id,
                'service_group_id'    => $group3->id,
                'created_by'          => $s5->id,
                'visits' => [
                    ['type' => 'church_meeting', 'beneficiary_status' => 'great', 'days_ago' => 4,  'feedback' => 'تقود فريق الشبابيات في الكنيسة'],
                    ['type' => 'home_visit',     'beneficiary_status' => 'great', 'days_ago' => 37, 'feedback' => 'أسرة نموذجية وملتزمة روحياً'],
                ],
            ],
            // ريهام (s6) — 5 مخدومين
            [
                'full_name'         => 'منير شفيق باخوم',
                'gender'            => 'male',
                'birth_date'        => '1943-03-12',
                'phone'             => '01045678903',
                'address_text'      => 'إمبابة، شارع الجامعة',
                'area'              => 'إمبابة',
                'governorate'       => 'الجيزة',
                'financial_status'  => 'poor',
                'status'            => 'active',
                'father_status'     => 'deceased',
                'mother_status'     => 'deceased',
                'siblings_count'    => 2,
                'disability_type'   => 'جسدية',
                'disability_degree' => 'moderate',
                'health_status'     => 'مشاكل في الرؤية وحركة محدودة',
                'assigned_servant_id' => $s6->id,
                'service_group_id'    => $group3->id,
                'created_by'          => $s6->id,
                'visits' => [
                    ['type' => 'home_visit', 'beneficiary_status' => 'good',  'days_ago' => 7,  'feedback' => 'رجل طيب ومحبوب، يحتاج مساعدة في التسوق الأسبوعي'],
                    ['type' => 'home_visit', 'beneficiary_status' => 'great', 'days_ago' => 41, 'feedback' => 'حالته أفضل بعد تغيير نظارته الطبية'],
                ],
            ],
            [
                'full_name'         => 'نرمين سامي برسوم',
                'gender'            => 'female',
                'birth_date'        => '1980-10-25',
                'phone'             => '01156789014',
                'address_text'      => 'الجيزة، شارع مصطفى محمود',
                'area'              => 'الجيزة',
                'governorate'       => 'الجيزة',
                'financial_status'  => 'moderate',
                'status'            => 'active',
                'father_status'     => 'alive',
                'mother_status'     => 'deceased',
                'siblings_count'    => 3,
                'disability_type'   => null,
                'disability_degree' => null,
                'health_status'     => 'تعافت من سرطان الثدي، في مرحلة المتابعة',
                'doctor_name'       => 'د. سحر رجب',
                'hospital_name'     => 'المركز القومي للأورام',
                'assigned_servant_id' => $s6->id,
                'service_group_id'    => $group3->id,
                'created_by'          => $s6->id,
                'visits' => [
                    ['type' => 'home_visit', 'beneficiary_status' => 'good',  'days_ago' => 12, 'feedback' => 'تتابع عند الطبيب كل شهرين، روحها قوية ومتفائلة'],
                    ['type' => 'home_visit', 'beneficiary_status' => 'great', 'days_ago' => 44, 'feedback' => 'تشارك في خدمة مرضى السرطان لمساعدة غيرها'],
                ],
                'prayer_requests' => [
                    ['title' => 'استمرار التعافي', 'body' => 'نطلب الصلاة من أجل استمرار تعافيها وأن تظل النتائج سلبية'],
                ],
            ],
            [
                'full_name'         => 'توفيق نادر كامل',
                'gender'            => 'male',
                'birth_date'        => '2004-05-18',
                'phone'             => '01267890125',
                'address_text'      => 'إمبابة، شارع العشرين',
                'area'              => 'إمبابة',
                'governorate'       => 'الجيزة',
                'financial_status'  => 'poor',
                'status'            => 'active',
                'guardian_name'     => 'نادر كامل',
                'guardian_phone'    => '01378901236',
                'guardian_relation' => 'والد',
                'father_status'     => 'alive',
                'mother_status'     => 'alive',
                'siblings_count'    => 6,
                'disability_type'   => null,
                'disability_degree' => null,
                'health_status'     => 'بصحة جيدة لكن متسرب من المدرسة',
                'assigned_servant_id' => $s6->id,
                'service_group_id'    => $group3->id,
                'created_by'          => $s6->id,
                'visits' => [
                    ['type' => 'home_visit', 'beneficiary_status' => 'needs_follow', 'days_ago' => 19, 'feedback' => 'تسرب من المدرسة ويعمل عامل في ورشة، أسرته تعاني'],
                    ['type' => 'phone_call', 'beneficiary_status' => 'needs_follow', 'days_ago' => 48, 'feedback' => 'يظهر اهتماماً بالكنيسة رغم ظروفه'],
                ],
                'prayer_requests' => [
                    ['title' => 'العودة للتعليم', 'body' => 'نطلب الصلاة والمساعدة لتمكينه من الالتحاق بمحو الأمية والتدريب المهني'],
                ],
            ],
            [
                'full_name'         => 'إيريس أديب لطيف',
                'gender'            => 'female',
                'birth_date'        => '1938-08-04',
                'phone'             => '01489012347',
                'address_text'      => 'العجوزة، حارة الكنيسة',
                'area'              => 'العجوزة',
                'governorate'       => 'الجيزة',
                'financial_status'  => 'poor',
                'status'            => 'active',
                'father_status'     => 'deceased',
                'mother_status'     => 'deceased',
                'siblings_count'    => 0,
                'disability_type'   => 'جسدية',
                'disability_degree' => 'severe',
                'health_status'     => 'ضعف عام، لا تستطيع الحركة، تنام أغلب الوقت',
                'assigned_servant_id' => $s6->id,
                'service_group_id'    => $group3->id,
                'created_by'          => $admin->id,
                'visits' => [
                    ['type' => 'home_visit', 'beneficiary_status' => 'critical', 'is_critical' => true, 'days_ago' => 1,  'feedback' => 'حالتها تدهورت كثيراً، تحتاج دخول مستشفى فوراً'],
                    ['type' => 'home_visit', 'beneficiary_status' => 'needs_follow',                    'days_ago' => 15, 'feedback' => 'كانت أفضل قليلاً هذه المرة'],
                ],
                'medications' => [
                    ['name' => 'فيتامين د',    'dosage' => 'مرة أسبوعياً', 'frequency' => 1, 'timing' => 'morning',   'is_active' => true],
                    ['name' => 'كالسيوم + د3', 'dosage' => 'مرة يومياً',   'frequency' => 1, 'timing' => 'with_food', 'is_active' => true],
                ],
            ],
            [
                'full_name'         => 'أنطوان سعد بشاي',
                'gender'            => 'male',
                'birth_date'        => '1988-12-21',
                'phone'             => '01590123458',
                'address_text'      => 'الجيزة، شارع الأهرام',
                'area'              => 'الجيزة',
                'governorate'       => 'الجيزة',
                'financial_status'  => 'moderate',
                'status'            => 'active',
                'father_status'     => 'alive',
                'mother_status'     => 'alive',
                'siblings_count'    => 2,
                'disability_type'   => null,
                'disability_degree' => null,
                'health_status'     => 'سليم',
                'assigned_servant_id' => $s6->id,
                'service_group_id'    => $group3->id,
                'created_by'          => $s6->id,
                'visits' => [
                    ['type' => 'church_meeting', 'beneficiary_status' => 'great', 'days_ago' => 3,  'feedback' => 'نشيط جداً في أنشطة الأسرة'],
                    ['type' => 'home_visit',     'beneficiary_status' => 'great', 'days_ago' => 31, 'feedback' => 'أسرته سعيدة ومتماسكة'],
                ],
            ],
        ];

        // ── Create all beneficiaries ───────────────────────────────────────────
        $allGroups = [
            $nourBeneficiaries,
            $rajaBeneficiaries,
            $farahBeneficiaries,
        ];

        $totalBeneficiaries = 0;
        $totalVisits        = 0;

        foreach ($allGroups as $groupData) {
            foreach ($groupData as $data) {
                $visits          = $data['visits']          ?? [];
                $medications     = $data['medications']     ?? [];
                $prayerRequests  = $data['prayer_requests'] ?? [];

                unset($data['visits'], $data['medications'], $data['prayer_requests']);

                $beneficiary = Beneficiary::create($data);
                $totalBeneficiaries++;

                // Visits
                foreach ($visits as $v) {
                    $daysAgo  = $v['days_ago'] ?? 14;
                    $visitDate = Carbon::now()->subDays($daysAgo)->setTime(
                        rand(9, 17), rand(0, 59)
                    );

                    $visit = Visit::create([
                        'beneficiary_id'       => $beneficiary->id,
                        'type'                 => $v['type'],
                        'visit_date'           => $visitDate,
                        'duration_minutes'     => rand(20, 90),
                        'beneficiary_status'   => $v['beneficiary_status'],
                        'feedback'             => $v['feedback'] ?? null,
                        'is_critical'          => $v['is_critical'] ?? false,
                        'needs_family_leader'  => ($v['beneficiary_status'] === 'critical') || ($v['is_critical'] ?? false),
                        'needs_service_leader' => false,
                        'created_by'           => $beneficiary->assigned_servant_id,
                    ]);

                    $visit->servants()->attach($beneficiary->assigned_servant_id);
                    $totalVisits++;
                }

                // Medications
                foreach ($medications as $med) {
                    $beneficiary->medications()->create($med);
                }

                // Prayer requests
                foreach ($prayerRequests as $pr) {
                    $beneficiary->prayerRequests()->create([
                        'title'      => $pr['title'],
                        'body'       => $pr['body'],
                        'status'     => 'open',
                        'created_by' => $beneficiary->assigned_servant_id,
                    ]);
                }
            }
        }

        $this->command->info("✅ Beneficiary data seeded:");
        $this->command->table(
            ['', 'العدد'],
            [
                ['مخدومين', $totalBeneficiaries],
                ['زيارات',  $totalVisits],
            ],
        );
    }
}
