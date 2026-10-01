<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * بريد استعادة كلمة المرور بالعربية — يحل محل رسالة Laravel الإنجليزية
 * الافتراضية. الرابط يحمل التوكن الموقّع من broker كلمة المرور.
 */
class ResetPasswordArabic extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $expire = (int) config('auth.passwords.' . config('auth.defaults.passwords') . '.expire', 60);

        return (new MailMessage)
            ->subject(__('auth.reset.mail_subject'))
            ->greeting(__('auth.reset.mail_greeting', ['name' => $notifiable->name]))
            ->line(__('auth.reset.mail_line1'))
            ->action(__('auth.reset.mail_action'), $this->resetUrl($notifiable))
            ->line(__('auth.reset.mail_expire', ['minutes' => $expire]))
            ->line(__('auth.reset.mail_ignore'));
    }

    protected function resetUrl($notifiable): string
    {
        return url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]));
    }
}
