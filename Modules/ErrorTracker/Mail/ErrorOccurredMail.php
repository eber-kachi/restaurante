<?php

namespace Modules\ErrorTracker\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * Sent synchronously on purpose (no ShouldQueue): if the queue is what is broken,
 * a queued alert would never arrive.
 */
class ErrorOccurredMail extends Mailable
{
    public function __construct(
        public array $payload,
        public string $reason,
        public int $occurrences,
        public int $sinceNotified,
        public ?int $eventId = null,
    ) {
    }

    public function build()
    {
        $label = match ($this->reason) {
            'regression' => 'Regresión',
            'repeat' => 'Repetido x' . $this->sinceNotified,
            default => 'Nuevo error',
        };

        $title = $this->payload['exception_class']
            ? class_basename($this->payload['exception_class'])
            : strtoupper($this->payload['level']);

        $subject = sprintf(
            '[%s][%s] %s: %s',
            config('app.name', 'App'),
            $label,
            $title,
            Str::limit(preg_replace('/\s+/', ' ', $this->payload['message']), 120)
        );

        $url = null;
        if ($this->eventId && Route::has('superadmin.error-tracker.show')) {
            try {
                $url = route('superadmin.error-tracker.show', $this->eventId);
            } catch (\Throwable $e) {
                $url = null;
            }
        }

        $mail = $this->subject($subject)
            ->view('errortracker::emails.error', [
                'p' => $this->payload,
                'label' => $label,
                'title' => $title,
                'occurrences' => $this->occurrences,
                'url' => $url,
            ]);

        $fromAddress = config('mail.from.address');
        if ($fromAddress) {
            $mail->from($fromAddress, config('mail.from.name') ?: config('app.name'));
        }

        return $mail;
    }
}
