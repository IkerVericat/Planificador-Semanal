<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Correo de restablecimiento con enlace a la pantalla del frontend (no hay vistas en la API).
 */
class ResetPasswordNotification extends ResetPassword
{
    protected function resetUrl($notifiable): string
    {
        $query = http_build_query([
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        return rtrim((string) config('app.frontend_url'), '/').'/reset-password?'.$query;
    }

    protected function buildMailMessage($url): MailMessage
    {
        $minutes = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        return (new MailMessage)
            ->subject('Restablece tu contraseña')
            ->greeting('¡Hola!')
            ->line('Recibes este correo porque se ha pedido restablecer la contraseña de tu cuenta.')
            ->action('Restablecer contraseña', $url)
            ->line("El enlace caduca en {$minutes} minutos.")
            ->line('Si no lo has pedido tú, puedes ignorar este correo.')
            ->salutation('El equipo de Planificador');
    }
}
