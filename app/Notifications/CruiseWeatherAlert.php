<?php

namespace App\Notifications;

use App\Models\Cruise;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CruiseWeatherAlert extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  list<array{day: int, from: string, to: string, label: string, detail: string, score: int}>  $legs
     */
    public function __construct(
        public readonly Cruise $cruise,
        public readonly array $legs,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Météo dégradée sur « '.$this->cruise->title.' »')
            ->greeting('Bonjour '.($notifiable->name ?? '').',')
            ->line('La prévision marine s\'est dégradée sur votre croisière '.$this->cruise->title.'.');

        foreach ($this->legs as $leg) {
            $message->line(sprintf(
                'J%d — %s → %s : %s (%s)',
                $leg['day'],
                $leg['from'],
                $leg['to'],
                $leg['label'],
                $leg['detail'],
            ));
        }

        return $message
            ->action('Revoir mon itinéraire', url('/croisieres/'.$this->cruise->slug))
            ->line('Cette alerte est indicative : consultez les bulletins officiels avant toute décision de départ.');
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'weather_alert',
            'cruise_id' => $this->cruise->id,
            'cruise_slug' => $this->cruise->slug,
            'cruise_title' => $this->cruise->title,
            'legs' => $this->legs,
        ];
    }
}
