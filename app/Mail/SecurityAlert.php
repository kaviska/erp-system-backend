<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SecurityAlert extends Mailable
{
    use Queueable, SerializesModels;

    public $userName;
    public $currentBrowser;
    public $currentDevice;
    public $currentPlatform;
    public $currentIp;
    public $lastBrowser;
    public $lastDevice;
    public $lastPlatform;
    public $lastIp;
    public $loginTime;

    /**
     * Create a new message instance.
     */
    public function __construct(
        string $userName,
        string $currentBrowser,
        string $currentDevice,
        string $currentPlatform,
        string $currentIp,
        string $loginTime,
        ?string $lastBrowser = null,
        ?string $lastDevice = null,
        ?string $lastPlatform = null,
        ?string $lastIp = null
    ) {
        $this->userName = $userName;
        $this->currentBrowser = $currentBrowser;
        $this->currentDevice = $currentDevice;
        $this->currentPlatform = $currentPlatform;
        $this->currentIp = $currentIp;
        $this->loginTime = $loginTime;
        $this->lastBrowser = $lastBrowser;
        $this->lastDevice = $lastDevice;
        $this->lastPlatform = $lastPlatform;
        $this->lastIp = $lastIp;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Security Alert - New Device Login Detected',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.security-alert',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
