<?php

declare(strict_types=1);

namespace App\Livewire\WebApp;

use App\Models\JoinRequest;
use App\Models\User;
use App\Support\WebAppScope;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * البحث العام الموحد: نتيجة واحدة مصنّفة (مخدومون / زيارات / خدام / طلبات
 * انضمام). كل قسم يمر عبر نفس نطاقات الصلاحيات المستخدمة في قوائمه، فلا
 * يُظهر البحث أي سجل لا يستطيع المستخدم فتحه أصلًا.
 */
#[Layout('web-app.layouts.app')]
#[Title('نتائج البحث')]
class GlobalSearchPage extends Component
{
    #[Url(as: 'q', except: '')]
    public string $search = '';

    public function render(): View
    {
        $user = auth()->user();
        $term = trim($this->search);

        return view('livewire.web-app.global-search-page', [
            'beneficiaries' => $term === '' ? collect() : $this->searchBeneficiaries($user, $term),
            'visits'        => $term === '' ? collect() : $this->searchVisits($user, $term),
            'users'         => ($term === '' || ! $user->can('viewAny', User::class))
                ? collect()
                : $this->searchUsers($user, $term),
            'joinRequests' => ($term === '' || ! $user->can('viewAny', JoinRequest::class))
                ? collect()
                : $this->searchJoinRequests($user, $term),
            'term' => $term,
        ]);
    }

    private function searchBeneficiaries($user, string $term)
    {
        return WebAppScope::beneficiaries($user)
            ->where(fn ($q) => $q
                ->where('full_name', 'like', "%{$term}%")
                ->orWhere('code', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%"))
            ->orderBy('full_name')
            ->limit(6)
            ->get(['id', 'full_name', 'code', 'status']);
    }

    private function searchVisits($user, string $term)
    {
        return WebAppScope::visits($user)
            ->where(fn ($q) => $q
                ->where('feedback', 'like', "%{$term}%")
                ->orWhere('type', 'like', "%{$term}%")
                ->orWhereHas('beneficiary', fn ($b) => $b->where('full_name', 'like', "%{$term}%")))
            ->latest('visit_date')
            ->limit(6)
            ->get(['id', 'beneficiary_id', 'type', 'visit_date', 'beneficiary_status']);
    }

    private function searchUsers($user, string $term)
    {
        return User::query()
            ->where(fn ($q) => $q
                ->where('name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%"))
            ->orderBy('name')
            ->limit(5)
            ->get(['id', 'name', 'email', 'role', 'is_active']);
    }

    private function searchJoinRequests($user, string $term)
    {
        return JoinRequest::query()
            ->with('user:id,name,email')
            ->whereHas('user', fn ($q) => $q
                ->where('name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%"))
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();
    }
}
