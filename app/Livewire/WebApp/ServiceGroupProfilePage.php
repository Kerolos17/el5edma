<?php

declare(strict_types=1);

namespace App\Livewire\WebApp;

use App\Livewire\WebApp\Concerns\ManagesServiceGroups;
use App\Models\ServiceGroup;
use App\Services\RegistrationLinkService;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('web-app.layouts.app')]
class ServiceGroupProfilePage extends Component
{
    use ManagesServiceGroups {
        saveServiceGroup as traitSaveServiceGroup;
    }
    use WithFileUploads;

    #[Locked]
    public ServiceGroup $serviceGroup;

    public function mount(ServiceGroup $serviceGroup): void
    {
        $user = auth()->user();
        abort_unless($user->can('view', $serviceGroup), 403);
        $this->serviceGroup = $serviceGroup->load([
            'leader',
            'serviceLeader',
            'servants',
            'beneficiaries',
        ]);
    }

    /**
     * رابط انضمام الخادمين — يُدار فقط لمن يملك صلاحية
     * manageRegistrationLink على هذه الأسرة (نفس سياسة الفلانت).
     */
    public function getCanManageRegistrationLinkProperty(): bool
    {
        return auth()->user()->can('manageRegistrationLink', $this->serviceGroup);
    }

    public function getRegistrationUrlProperty(): ?string
    {
        if (! $this->canManageRegistrationLink) {
            return null;
        }

        $token = $this->serviceGroup->fresh()->registration_token;

        return $token ? route('registration.show', ['token' => $token]) : null;
    }

    public function getRegistrationWhatsAppShareUrlProperty(): ?string
    {
        if (! $this->registrationUrl) {
            return null;
        }

        $text = __('service_groups.share_wa_text', [
            'group' => $this->serviceGroup->name,
            'url'   => $this->registrationUrl,
        ]);

        return 'https://wa.me/?text=' . rawurlencode($text);
    }

    /** إنشاء الرابط إن لم يكن موجودًا أو انتهت صلاحيته (72 ساعة). */
    public function ensureRegistrationLink(): void
    {
        abort_unless($this->canManageRegistrationLink, 403);

        app(RegistrationLinkService::class)->getOrCreateToken($this->serviceGroup->fresh());

        $this->dispatch('toast', message: __('service_groups.link_created'), type: 'success');
    }

    /** إعادة توليد الرابط — الرابط القديم يصبح غير صالح فورًا. */
    public function regenerateRegistrationLink(): void
    {
        abort_unless($this->canManageRegistrationLink, 403);

        app(RegistrationLinkService::class)->regenerateToken($this->serviceGroup->fresh());

        $this->dispatch('toast', message: __('service_groups.link_regenerated'), type: 'success');
    }

    public function render(): View
    {
        $user = auth()->user();

        return view('livewire.web-app.service-group-profile-page', [
            'serviceGroupLeaderOptions'        => $this->serviceGroupLeaderOptions($user),
            'serviceGroupServiceLeaderOptions' => $this->serviceGroupServiceLeaderOptions($user),
        ]);
    }

    public function saveServiceGroup(): void
    {
        $this->traitSaveServiceGroup();
        $this->redirect(route('app.service-group-profile', $this->serviceGroup->id), navigate: true);
    }
}
